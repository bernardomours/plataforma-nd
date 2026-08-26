<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\GlosaBatch;
use App\Models\GlosaItem;
use App\Models\GlosaReason;
use App\Models\GlosaReasonCode;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Lê o relatório de glosas da Unimed. Dois formatos, detectados pelo separador:
 *
 *   - o .xls original, que é TSV em ISO-8859-1, datas dd/mm/aaaa e competência mm/aaaa.
 *     Cobre um prestador em um mês;
 *   - o CSV consolidado do pipeline do Drive, separado por ";", datas ISO e carteira já
 *     sem zeros à esquerda. Cobre vários prestadores e vários meses no mesmo arquivo.
 *
 * O vínculo com o atendimento sai da coluna `Guia`, que casa 1:1 com `appointments.guide`;
 * o prestador sai de `units.unimed_code`.
 */
class GlosaReportImporter
{
    private const COLUNAS_OBRIGATORIAS = ['comp', 'prestador', 'dtitem', 'guia', 'vlapresentado', 'vlglosa'];

    /** Quantas linhas vão ao banco por vez. */
    private const TAMANHO_LOTE = 500;

    /**
     * Lê e resolve o arquivo sem gravar nada. Devolve uma remessa por combinação de
     * competência e prestador — o consolidado traz 22 delas.
     */
    public function analisar(string $caminho, ?string $competencia = null): array
    {
        if (! is_readable($caminho)) {
            throw new RuntimeException("Arquivo não encontrado ou sem permissão de leitura: {$caminho}");
        }

        $linhas = $this->lerTabela($caminho, $this->filtroDeCompetencia($competencia));

        if (empty($linhas)) {
            throw new RuntimeException('O arquivo não tem nenhuma linha de dados.');
        }

        $unidadesPorCodigo = Unit::whereNotNull('unimed_code')->get()->keyBy('unimed_code');

        $remessas = [];

        foreach ($this->agruparPorRemessa($linhas) as $grupo) {
            $unidade = $unidadesPorCodigo->get($grupo['prestador_codigo']);

            $itens = array_map(
                fn ($l) => $this->montarItem($l, $grupo['competencia'], $unidade?->id),
                $grupo['linhas']
            );

            $remessas[] = [
                'competencia'      => $grupo['competencia'],
                'prestador_codigo' => $grupo['prestador_codigo'],
                'prestador_nome'   => $grupo['prestador_nome'],
                'unidade'          => $unidade,
                'itens'            => $itens,
            ];
        }

        $this->resolverAtendimentos($remessas);

        foreach ($remessas as &$r) {
            $r['totais'] = $this->totais($r['itens']);
        }

        return [
            'arquivo'  => basename($caminho),
            'hash'     => hash_file('sha256', $caminho),
            'remessas' => $remessas,
            'totais'   => $this->totais(array_merge(...array_column($remessas, 'itens'))),
        ];
    }

    /**
     * Grava. Idempotente: o hash barra o mesmo arquivo, e cada dupla competência+prestador
     * é única. Com $substituir, a remessa anterior é apagada em cascata e regravada.
     *
     * @return Collection<int,GlosaBatch>
     */
    public function importar(
        string $caminho,
        ?int $userId = null,
        bool $substituir = false,
        ?string $competencia = null
    ): Collection {
        $dados = $this->analisar($caminho, $competencia);

        $semUnidade = collect($dados['remessas'])->whereNull('unidade');

        if ($semUnidade->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'Prestador(es) %s não correspondem a nenhuma unidade. Cadastre o código em ' .
                'units.unimed_code. Conhecidos: %s',
                $semUnidade->pluck('prestador_codigo')->unique()->implode(', '),
                Unit::whereNotNull('unimed_code')->get()
                    ->map(fn ($u) => "{$u->unimed_code} ({$u->name})")->implode(', ')
            ));
        }

        $conflitos = $this->conflitos($dados);

        if ($conflitos->isNotEmpty() && ! $substituir) {
            throw new RuntimeException(
                'Estas competências já foram importadas: ' .
                $conflitos->map(fn ($b) => $b->competencia->format('m/Y') . '/' . $b->prestador_codigo)
                    ->implode(', ') .
                '. Use --substituir para regravar.'
            );
        }

        return DB::transaction(function () use ($dados, $userId, $conflitos) {
            // Cascade leva itens e motivos junto — regravar não deixa resíduo da carga anterior.
            $conflitos->each->delete();

            $gravadas = collect();

            foreach ($dados['remessas'] as $remessa) {
                $gravadas->push($this->gravarRemessa($remessa, $dados, $userId));
            }

            $this->atualizarCatalogoDeMotivos($dados['remessas']);

            return $gravadas;
        });
    }

    private function conflitos(array $dados): Collection
    {
        // O hash gravado é derivado por remessa, então a busca também tem que derivar —
        // senão o consolidado nunca reconheceria a própria reimportação.
        $hashes = array_map(fn ($r) => $this->hashDaRemessa($dados['hash'], $r), $dados['remessas']);

        $porHash = GlosaBatch::whereIn('arquivo_hash', $hashes)->get();

        $porCompetencia = GlosaBatch::query()
            ->where(function ($q) use ($dados) {
                foreach ($dados['remessas'] as $r) {
                    $q->orWhere(fn ($sub) => $sub
                        ->where('competencia', $r['competencia'])
                        ->where('prestador_codigo', $r['prestador_codigo']));
                }
            })
            ->get();

        return $porHash->concat($porCompetencia)->unique('id')->values();
    }

    private function gravarRemessa(array $remessa, array $dados, ?int $userId): GlosaBatch
    {
        $t = $remessa['totais'];

        $batch = GlosaBatch::create([
            'competencia'      => $remessa['competencia'],
            'prestador_codigo' => $remessa['prestador_codigo'],
            'prestador_nome'   => $remessa['prestador_nome'],
            'unit_id'          => $remessa['unidade']->id,
            'arquivo_nome'     => $dados['arquivo'],
            // O hash é do arquivo; num consolidado várias remessas compartilham o mesmo.
            // A coluna é única, então só a primeira o carrega — as demais ficam com um
            // derivado, preservando a checagem de "este arquivo já entrou".
            'arquivo_hash'     => $this->hashDaRemessa($dados['hash'], $remessa),
            'total_itens'      => $t['itens'],
            'vl_apresentado'   => $t['apresentado'],
            'vl_liberado'      => $t['liberado'],
            'vl_glosa'         => $t['glosa'],
            'imported_by'      => $userId,
        ]);

        $agora = now();
        $paraInserir = [];
        $motivosPorPosicao = [];

        foreach ($remessa['itens'] as $posicao => $item) {
            $motivosPorPosicao[$posicao] = $item['motivos'];
            unset($item['motivos']);

            $item['glosa_batch_id'] = $batch->id;
            $item['competencia']    = $item['competencia']->toDateString();
            $item['dt_item']        = $item['dt_item']?->toDateString();
            $item['created_at']     = $agora;
            $item['updated_at']     = $agora;

            $paraInserir[] = $item;
        }

        foreach (array_chunk($paraInserir, self::TAMANHO_LOTE) as $bloco) {
            GlosaItem::insert($bloco);
        }

        $this->gravarMotivos($batch, $motivosPorPosicao, count($paraInserir), $agora);

        return $batch;
    }

    /**
     * Os ids saem por ordem de inserção. O par (guia, item) se repete dentro da remessa,
     * então não serve de chave — a posição na lista é o que liga o item ao seu motivo.
     * Seguro porque a remessa acabou de ser criada dentro da transação: ninguém mais
     * escreve nela.
     */
    private function gravarMotivos(GlosaBatch $batch, array $motivosPorPosicao, int $esperado, $agora): void
    {
        if (empty(array_filter($motivosPorPosicao))) {
            return;
        }

        $ids = GlosaItem::where('glosa_batch_id', $batch->id)->orderBy('id')->pluck('id')->all();

        if (count($ids) !== $esperado) {
            throw new RuntimeException(sprintf(
                'Gravou %d itens mas esperava %d na remessa %s/%s. Importação abortada.',
                count($ids), $esperado, $batch->competencia->format('m/Y'), $batch->prestador_codigo
            ));
        }

        $linhas = [];

        foreach ($motivosPorPosicao as $posicao => $motivos) {
            foreach ($motivos as $m) {
                $linhas[] = $m + [
                    'glosa_item_id' => $ids[$posicao],
                    'created_at'    => $agora,
                    'updated_at'    => $agora,
                ];
            }
        }

        foreach (array_chunk($linhas, self::TAMANHO_LOTE) as $bloco) {
            GlosaReason::insert($bloco);
        }
    }

    /**
     * O código é ASCII e nunca corrompe; a descrição, sim. Vence a grafia com menos
     * caractere de substituição — não a mais frequente, porque em vários códigos a
     * versão corrompida é justamente a que mais aparece.
     */
    private function atualizarCatalogoDeMotivos(array $remessas): void
    {
        $melhor = [];

        foreach ($remessas as $remessa) {
            foreach ($remessa['itens'] as $item) {
                foreach ($item['motivos'] as $m) {
                    if (! $m['codigo'] || ! $m['descricao']) {
                        continue;
                    }

                    $atual = $melhor[$m['codigo']] ?? null;

                    if ($atual === null || $this->qualidade($m['descricao']) > $this->qualidade($atual)) {
                        $melhor[$m['codigo']] = $m['descricao'];
                    }
                }
            }
        }

        foreach ($melhor as $codigo => $descricao) {
            $registro = GlosaReasonCode::firstOrNew(['codigo' => $codigo]);

            if (! $registro->exists || $this->qualidade($descricao) > $this->qualidade($registro->descricao ?? '')) {
                $registro->descricao = $descricao;
                $registro->save();
            }
        }
    }

    /** Menos "?" e menos caractere de substituição = melhor. */
    private function qualidade(string $texto): int
    {
        return -(substr_count($texto, '?') + substr_count($texto, "\u{FFFD}"));
    }

    // ---------------------------------------------------------------- leitura

    /**
     * Normaliza o filtro para "AAAA-MM". Aceita "2026-07", "07/2026" e "2026-07-01".
     */
    private function filtroDeCompetencia(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        return $this->competencia($valor)->format('Y-m');
    }

    /** @return array<int,array<string,string>> */
    private function lerTabela(string $caminho, ?string $somenteCompetencia = null): array
    {
        $bruto = file_get_contents($caminho);

        // O relatório vem em ISO-8859-1. Arquivo já convertido para UTF-8 passa direto.
        if (! mb_check_encoding($bruto, 'UTF-8')) {
            $bruto = mb_convert_encoding($bruto, 'UTF-8', 'ISO-8859-1');
        }

        $bruto = preg_replace('/^\xEF\xBB\xBF/', '', str_replace("\r\n", "\n", $bruto));
        $linhas = array_values(array_filter(explode("\n", $bruto), fn ($l) => trim($l) !== ''));

        if (count($linhas) < 2) {
            throw new RuntimeException('O arquivo não tem cabeçalho e linhas de dados.');
        }

        $separador = $this->separador($linhas[0]);
        $cabecalho = array_map([$this, 'chave'], str_getcsv(array_shift($linhas), $separador));

        $faltando = array_diff(self::COLUNAS_OBRIGATORIAS, $cabecalho);

        if ($faltando) {
            throw new RuntimeException(
                'O arquivo não parece o relatório de glosas: faltam as colunas ' .
                implode(', ', $faltando) . '.'
            );
        }

        $saida = [];

        foreach ($linhas as $linha) {
            $campos = str_getcsv($linha, $separador);

            if (count($campos) !== count($cabecalho)) {
                continue;
            }

            $linha = array_combine($cabecalho, array_map(fn ($c) => trim((string) $c), $campos));

            // Filtra na leitura, não depois: o consolidado tem 79 mil linhas e guardar todas
            // para descartar 73 mil consome 500 MB, o que não passa em hospedagem compartilhada.
            if ($somenteCompetencia !== null
                && $this->competencia($linha['comp'] ?? '')->format('Y-m') !== $somenteCompetencia) {
                continue;
            }

            $saida[] = $linha;
        }

        if ($somenteCompetencia !== null && empty($saida)) {
            throw new RuntimeException(
                "O arquivo não tem nenhuma linha da competência {$somenteCompetencia}."
            );
        }

        return $saida;
    }

    /** TSV do .xls original ou CSV com ";" do pipeline. */
    private function separador(string $cabecalho): string
    {
        return substr_count($cabecalho, "\t") >= substr_count($cabecalho, ';') ? "\t" : ';';
    }

    /** "Vl apresentado" -> "vlapresentado" */
    private function chave(string $nome): string
    {
        $sem = iconv('UTF-8', 'ASCII//TRANSLIT', trim($nome));

        return strtolower(preg_replace('/[^a-z0-9]/i', '', $sem ?: $nome));
    }

    /** @return array<int,array{competencia:Carbon,prestador_codigo:string,prestador_nome:string,linhas:array}> */
    private function agruparPorRemessa(array $linhas): array
    {
        $grupos = [];

        foreach ($linhas as $l) {
            $competencia = $this->competencia($l['comp'] ?? '');
            [$codigo, $nome] = $this->prestador($l['prestador'] ?? '');

            $chave = $competencia->format('Y-m') . '|' . $codigo;

            if (! isset($grupos[$chave])) {
                $grupos[$chave] = [
                    'competencia'      => $competencia,
                    'prestador_codigo' => $codigo,
                    'prestador_nome'   => $nome,
                    'linhas'           => [],
                ];
            }

            $grupos[$chave]['linhas'][] = $l;
        }

        ksort($grupos);

        return array_values($grupos);
    }

    // ---------------------------------------------------------------- conversão

    /** Aceita "06/2026" do .xls e "2026-06-01" do CSV consolidado. */
    private function competencia(string $valor): Carbon
    {
        $v = trim($valor);

        if (preg_match('/^(\d{4})-(\d{2})(?:-(\d{2}))?$/', $v, $m)) {
            return Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
        }

        if (preg_match('/^(\d{1,2})\/(\d{4})$/', $v, $m)) {
            return Carbon::create((int) $m[2], (int) $m[1], 1)->startOfDay();
        }

        throw new RuntimeException("Competência inválida no arquivo: '{$valor}'. Esperado MM/AAAA ou AAAA-MM-DD.");
    }

    /** "21000430 - MARTINS E LEAL PSICOLOGIA LTDA" -> ['21000430', 'MARTINS E LEAL...'] */
    private function prestador(string $valor): array
    {
        $valor = trim($valor);

        if (preg_match('/^(\S+)\s*-\s*(.+)$/', $valor, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        throw new RuntimeException("Prestador inválido no arquivo: '{$valor}'.");
    }

    /** "272,00" -> 272.00 · "1.234,56" -> 1234.56 */
    private function decimal(?string $valor): float
    {
        $v = trim((string) $valor);

        if ($v === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', str_replace('.', '', $v));
    }

    /** Aceita "24/04/2026" e "2026-04-24". */
    private function data(?string $valor): ?Carbon
    {
        $v = trim((string) $valor);

        if ($v === '') {
            return null;
        }

        // createFromFormat lança quando o formato não casa, em vez de devolver false —
        // então o teste de cada formato precisa ser protegido.
        foreach (['d/m/Y', 'Y-m-d'] as $formato) {
            try {
                $d = Carbon::createFromFormat($formato, $v);
            } catch (\Throwable) {
                continue;
            }

            if ($d && $d->format($formato) === $v) {
                return $d->startOfDay();
            }
        }

        throw new RuntimeException("Data inválida no arquivo: '{$valor}'.");
    }

    /** "50005821 - PSICOLOGIA - ABA" -> código + o resto inteiro. */
    private function codigoEDescricao(?string $valor): array
    {
        $v = trim((string) $valor);

        if ($v === '') {
            return [null, null];
        }

        return preg_match('/^(\S+)\s*-\s*(.+)$/s', $v, $m)
            ? [trim($m[1]), trim($m[2])]
            : [null, $v];
    }

    private function montarItem(array $l, Carbon $competencia, ?int $unitId): array
    {
        [$itemCodigo, $itemDescricao] = $this->codigoEDescricao($l['item'] ?? '');

        $apresentado = $this->decimal($l['vlapresentado'] ?? null);
        $glosa       = $this->decimal($l['vlglosa'] ?? null);

        return [
            'competencia'       => $competencia,
            'unit_id'           => $unitId,
            'dt_item'           => $this->data($l['dtitem'] ?? null),
            'item_codigo'       => $itemCodigo,
            'item_descricao'    => $itemDescricao,
            'conta'             => $l['conta'] ?? null,
            'guia'              => trim($l['guia'] ?? '') ?: null,
            // O .xls traz a carteira com zeros à esquerda; o cadastro guarda sem.
            'carteira'          => ltrim(trim($l['carteira'] ?? ''), '0') ?: null,
            'lote'              => $l['lote'] ?? null,
            'qt_item'           => $this->decimal($l['qtitem'] ?? null),
            'taxa'              => $this->decimal($l['taxa'] ?? null),
            'vl_apresentado'    => $apresentado,
            'vl_liberado'       => $this->decimal($l['vlliberado'] ?? null),
            'vl_glosa'          => $glosa,
            'prorata'           => $this->decimal($l['prorata'] ?? null),
            'beneficiario_nome' => trim($l['nome'] ?? '') ?: null,
            'medico_nome'       => trim($l['medico'] ?? '') ?: null,
            'status'            => $this->status($apresentado, $glosa),
            'appointment_id'    => null,
            'patient_id'        => null,
            'professional_id'   => null,
            'motivos'           => $this->motivos($l['glosas'] ?? ''),
        ];
    }

    private function status(float $apresentado, float $glosa): string
    {
        if ($glosa <= 0) {
            return GlosaItem::STATUS_LIBERADO;
        }

        return $glosa >= $apresentado ? GlosaItem::STATUS_GLOSADO : GlosaItem::STATUS_PARCIAL;
    }

    /**
     * Separa as anotações do campo "Glosas".
     *
     * Uma linha pode ter vários motivos: "3145 - NAO AUTORIZADO POR MOTIVO TECNICO, CM89 -
     * Guia sem execução cirúrgica". Não dá para quebrar em toda vírgula, porque a descrição
     * também tem vírgula ("Conforme prescrição e autorização, evidencias em conformidade").
     * O corte só acontece antes de um novo código (CM89, 3145, INTADM120) ou de um marcador
     * "Ocorrencia -"/"Parecer -", que é como o PDF anota.
     */
    private function motivos(string $texto): array
    {
        $texto = trim($texto);

        if ($texto === '') {
            return [];
        }

        $partes = preg_split(
            '/,\s*(?=(?:Ocorr[eê]ncia|Parecer)\s*-\s|[A-Z]{0,8}\d{2,6}\s*-\s)/ui',
            $texto,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $saida = [];

        foreach ($partes as $parte) {
            $parte = trim($parte);
            $tipo  = GlosaReason::TIPO_OCORRENCIA;

            if (preg_match('/^(Ocorr[eê]ncia|Parecer)\s*-\s*(.*)$/ui', $parte, $m)) {
                $tipo  = mb_strtolower($m[1], 'UTF-8') === 'parecer'
                    ? GlosaReason::TIPO_PARECER
                    : GlosaReason::TIPO_OCORRENCIA;
                $parte = trim($m[2]);
            }

            if ($parte === '') {
                continue;
            }

            [$codigo, $descricao] = $this->codigoEDescricao($parte);

            $saida[] = ['tipo' => $tipo, 'codigo' => $codigo, 'descricao' => $descricao];
        }

        return $saida;
    }

    // ---------------------------------------------------------------- resolução

    /**
     * Liga cada linha ao atendimento pela guia, para o arquivo inteiro de uma vez.
     * Guia repetida na base fica sem vínculo: atribuir a glosa ao atendimento errado
     * é pior que deixar não conciliado.
     */
    private function resolverAtendimentos(array &$remessas): void
    {
        $guias = collect($remessas)
            ->flatMap(fn ($r) => array_column($r['itens'], 'guia'))
            ->filter()->unique()->values();

        if ($guias->isEmpty()) {
            return;
        }

        $porGuia = [];

        foreach ($guias->chunk(1000) as $bloco) {
            foreach (Appointment::whereIn('guide', $bloco)->get(['id', 'guide', 'patient_id', 'professional_id']) as $a) {
                $porGuia[$a->guide][] = $a;
            }
        }

        foreach ($remessas as &$remessa) {
            foreach ($remessa['itens'] as &$item) {
                $achados = $item['guia'] ? ($porGuia[$item['guia']] ?? []) : [];

                if (count($achados) !== 1) {
                    continue;
                }

                $item['appointment_id']  = $achados[0]->id;
                $item['patient_id']      = $achados[0]->patient_id;
                $item['professional_id'] = $achados[0]->professional_id;
            }
        }
    }

    private function totais(array $itens): array
    {
        $c = collect($itens);

        return [
            'itens'           => $c->count(),
            'apresentado'     => round($c->sum('vl_apresentado'), 2),
            'liberado'        => round($c->sum('vl_liberado'), 2),
            'glosa'           => round($c->sum('vl_glosa'), 2),
            'glosados'        => $c->where('status', '!=', GlosaItem::STATUS_LIBERADO)->count(),
            'conciliados'     => $c->whereNotNull('appointment_id')->count(),
            'nao_conciliados' => $c->whereNull('appointment_id')->count(),
        ];
    }

    /** A coluna do hash é única, e um consolidado gera várias remessas do mesmo arquivo. */
    private function hashDaRemessa(string $hashDoArquivo, array $remessa): string
    {
        return hash('sha256', $hashDoArquivo . '|' . $remessa['competencia']->format('Y-m')
            . '|' . $remessa['prestador_codigo']);
    }
}
