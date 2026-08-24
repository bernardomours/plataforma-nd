<?php

namespace App\Console\Commands;

use App\Models\GlosaBatch;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Confere o que está importado contra a extração do PDF.
 *
 * O pipeline gera duas leituras independentes do mesmo relatório: o consolidado vindo do XLS
 * (que é o que a plataforma importa, porque traz o profissional e a carteira) e um CSV por
 * competência vindo do PDF. Se as duas concordam, o valor gravado não depende de uma única
 * conversão ter dado certo.
 */
class VerifyGlosaReport extends Command
{
    protected $signature = 'glosas:conferir
                            {caminho : Pasta com os CSV extraídos do PDF, ou um arquivo só}';

    protected $description = 'Confere as glosas importadas contra a extração independente do PDF';

    /** Tolerância em reais: abaixo disso é arredondamento, não divergência. */
    private const TOLERANCIA = 0.01;

    public function handle(): int
    {
        $caminho = $this->argument('caminho');

        $arquivos = is_dir($caminho)
            ? glob(rtrim($caminho, '/\\') . DIRECTORY_SEPARATOR . '*.csv')
            : [$caminho];

        if (empty($arquivos)) {
            $this->error("Nenhum .csv encontrado em {$caminho}");

            return self::FAILURE;
        }

        $doPdf = [];

        foreach ($arquivos as $arquivo) {
            foreach ($this->totalizar($arquivo) as $chave => $t) {
                if (isset($doPdf[$chave])) {
                    // Dois arquivos cobrindo a mesma competência e prestador é exatamente
                    // o defeito que inflou o BI antigo. Avisa em vez de somar.
                    $this->warn(sprintf(
                        '  %s aparece em dois arquivos: %s e %s. Contando só o primeiro.',
                        $chave, $doPdf[$chave]['arquivo'], basename($arquivo)
                    ));

                    continue;
                }

                $doPdf[$chave] = $t;
            }
        }

        return $this->comparar($doPdf);
    }

    /** @return array<string,array> chave "AAAA-MM|prestador" */
    private function totalizar(string $arquivo): array
    {
        $bruto = file_get_contents($arquivo);

        if (! mb_check_encoding($bruto, 'UTF-8')) {
            $bruto = mb_convert_encoding($bruto, 'UTF-8', 'ISO-8859-1');
        }

        $linhas = array_values(array_filter(
            explode("\n", str_replace("\r\n", "\n", $bruto)),
            fn ($l) => trim($l) !== ''
        ));

        if (count($linhas) < 2) {
            return [];
        }

        // A ordem das colunas muda de arquivo para arquivo — Ocorrencia_1 e Parecer_1 trocam
        // de lugar entre os meses. Só o nome é confiável.
        $cabecalho = array_map(
            fn ($c) => strtolower(preg_replace('/[^a-z0-9]/i', '', $c)),
            str_getcsv(array_shift($linhas), ';')
        );

        $totais = [];

        foreach ($linhas as $linha) {
            $campos = str_getcsv($linha, ';');

            if (count($campos) !== count($cabecalho)) {
                continue;
            }

            $l = array_combine($cabecalho, $campos);

            $comp = trim($l['competencia'] ?? '');

            if (! preg_match('#^(\d{1,2})/(\d{4})$#', $comp, $m)) {
                continue;
            }

            $chave = sprintf('%s-%02d', $m[2], $m[1]) . '|' . trim($l['cdprestador'] ?? '');

            $totais[$chave] ??= [
                'itens' => 0, 'apresentado' => 0.0, 'liberado' => 0.0, 'glosa' => 0.0,
                'arquivo' => basename($arquivo),
            ];

            $totais[$chave]['itens']++;
            $totais[$chave]['apresentado'] += $this->decimal($l['vlapresentado'] ?? null);
            $totais[$chave]['liberado']    += $this->decimal($l['vlliberado'] ?? null);
            $totais[$chave]['glosa']       += $this->decimal($l['vlglosa'] ?? null);
        }

        return $totais;
    }

    private function decimal(?string $valor): float
    {
        $v = trim((string) $valor);

        return $v === '' ? 0.0 : (float) str_replace(',', '.', str_replace('.', '', $v));
    }

    private function comparar(array $doPdf): int
    {
        $importadas = GlosaBatch::all()
            ->keyBy(fn ($b) => $b->competencia->format('Y-m') . '|' . $b->prestador_codigo);

        $linhas = [];
        $divergentes = 0;
        $semImportacao = 0;

        foreach ($doPdf as $chave => $pdf) {
            $batch = $importadas->get($chave);
            [$comp, $prestador] = explode('|', $chave);
            $rotulo = Carbon::parse($comp . '-01')->format('m/Y');

            if (! $batch) {
                $semImportacao++;
                $linhas[] = [$rotulo, $prestador, $pdf['itens'], '—', '—', 'NÃO IMPORTADA'];

                continue;
            }

            $difItens = $batch->total_itens - $pdf['itens'];
            $difValor = round((float) $batch->vl_apresentado - $pdf['apresentado'], 2);
            $difGlosa = round((float) $batch->vl_glosa - $pdf['glosa'], 2);

            $ok = $difItens === 0
                && abs($difValor) < self::TOLERANCIA
                && abs($difGlosa) < self::TOLERANCIA;

            if (! $ok) {
                $divergentes++;
            }

            $linhas[] = [
                $rotulo,
                $prestador,
                number_format($pdf['itens'], 0, ',', '.'),
                $difItens === 0 ? 'igual' : sprintf('%+d', $difItens),
                abs($difValor) < self::TOLERANCIA ? 'igual' : 'R$ ' . number_format($difValor, 2, ',', '.'),
                $ok ? 'OK' : 'DIVERGE',
            ];
        }

        $this->info('Conferência: importado (XLS) x extração do PDF');
        $this->newLine();

        $this->table(
            ['Comp.', 'Prestador', 'Itens no PDF', 'Dif. itens', 'Dif. apresentado', ''],
            $linhas
        );

        $this->naoConferidas($importadas, $doPdf);

        if ($divergentes > 0) {
            $this->error("  {$divergentes} remessa(s) divergem entre as duas extrações.");

            return self::FAILURE;
        }

        if ($semImportacao > 0) {
            $this->warn("  {$semImportacao} competência(s) existem no PDF mas não foram importadas.");

            return self::FAILURE;
        }

        $this->info(sprintf(
            '  As %d remessas conferidas batem nas duas extrações, item a item e no valor.',
            count($doPdf)
        ));

        return self::SUCCESS;
    }

    /** Remessa importada sem PDF correspondente fica sem conferência externa — precisa aparecer. */
    private function naoConferidas($importadas, array $doPdf): void
    {
        $orfas = $importadas->keys()->diff(array_keys($doPdf));

        if ($orfas->isEmpty()) {
            return;
        }

        $this->warn(sprintf(
            '  %d remessa(s) importadas sem PDF para conferir: %s',
            $orfas->count(),
            $orfas->map(fn ($k) => str_replace('|', ' / ', $k))->implode(', ')
        ));
        $this->newLine();
    }
}
