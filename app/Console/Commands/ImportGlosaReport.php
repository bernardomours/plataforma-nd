<?php

namespace App\Console\Commands;

use App\Models\GlosaItem;
use App\Services\GlosaReportImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportGlosaReport extends Command
{
    protected $signature = 'glosas:importar
                            {arquivo : Relatório da Unimed — o .xls original (TSV) ou o CSV consolidado do pipeline}
                            {--competencia= : Importa só uma competência do arquivo (AAAA-MM ou MM/AAAA)}
                            {--fix : Grava de verdade; sem esta opção apenas simula}
                            {--substituir : Regrava competências que já tenham sido importadas}';

    protected $description = 'Importa o relatório de glosas da Unimed e concilia com os atendimentos';

    public function handle(GlosaReportImporter $importer): int
    {
        $arquivo = $this->argument('arquivo');

        try {
            $dados = $importer->analisar($arquivo, $this->option('competencia'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Relatório de glosas — ' . $dados['arquivo']);
        $this->newLine();

        $this->remessas($dados);
        $this->consolidado($dados);
        $this->motivos($dados);
        $this->naoConciliados($dados);

        $semUnidade = collect($dados['remessas'])->whereNull('unidade');

        if ($semUnidade->isNotEmpty()) {
            $this->error(
                '  Prestador(es) sem unidade correspondente: ' .
                $semUnidade->pluck('prestador_codigo')->unique()->implode(', ') .
                '. Cadastre o código em units.unimed_code antes de gravar.'
            );

            return self::FAILURE;
        }

        if (! $this->option('fix')) {
            $this->warn('  SIMULAÇÃO — nada foi gravado. Rode de novo com --fix para importar.');

            return self::SUCCESS;
        }

        try {
            $gravadas = $importer->importar(
                $arquivo, auth()->id(), $this->option('substituir'), $this->option('competencia')
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            '  Importado: %d remessa(s), %d itens.',
            $gravadas->count(),
            $gravadas->sum(fn ($b) => $b->items()->count())
        ));

        return self::SUCCESS;
    }

    private function remessas(array $dados): void
    {
        $linhas = [];

        foreach ($dados['remessas'] as $r) {
            $t = $r['totais'];

            $linhas[] = [
                $r['competencia']->format('m/Y'),
                $r['prestador_codigo'],
                $r['unidade']->name ?? '<sem unidade>',
                number_format($t['itens'], 0, ',', '.'),
                'R$ ' . number_format($t['apresentado'], 2, ',', '.'),
                'R$ ' . number_format($t['glosa'], 2, ',', '.'),
                $t['apresentado'] > 0
                    ? number_format($t['glosa'] / $t['apresentado'] * 100, 2, ',', '.') . '%'
                    : '—',
                $t['glosados'],
            ];
        }

        $this->table(
            ['Comp.', 'Prestador', 'Unidade', 'Itens', 'Apresentado', 'Glosa', '%', 'Glosados'],
            $linhas
        );
    }

    private function consolidado(array $dados): void
    {
        $t = $dados['totais'];

        $this->line('  Confira com o RESUMO no rodapé do PDF (quando for arquivo de um mês só):');
        $this->line(sprintf(
            '    %s itens | apresentado R$ %s | liberado R$ %s | glosa R$ %s (%s%%)',
            number_format($t['itens'], 0, ',', '.'),
            number_format($t['apresentado'], 2, ',', '.'),
            number_format($t['liberado'], 2, ',', '.'),
            number_format($t['glosa'], 2, ',', '.'),
            $t['apresentado'] > 0 ? number_format($t['glosa'] / $t['apresentado'] * 100, 2, ',', '.') : '0'
        ));

        // Apresentado - glosa tem que dar liberado; se não der, o parsing comeu algum valor.
        $diferenca = round($t['apresentado'] - $t['glosa'] - $t['liberado'], 2);

        if (abs($diferenca) > 0.01) {
            $this->warn(sprintf(
                '    Atenção: apresentado - glosa não fecha com liberado (diferença de R$ %s).',
                number_format($diferenca, 2, ',', '.')
            ));
        }

        $this->line(sprintf(
            '    conciliados por guia: %s de %s (%.1f%%)',
            number_format($t['conciliados'], 0, ',', '.'),
            number_format($t['itens'], 0, ',', '.'),
            $t['itens'] > 0 ? $t['conciliados'] / $t['itens'] * 100 : 0
        ));

        $this->newLine();
    }

    private function motivos(array $dados): void
    {
        $contagem = [];
        $descricoes = [];

        foreach ($dados['remessas'] as $remessa) {
            foreach ($remessa['itens'] as $item) {
                if ($item['status'] === GlosaItem::STATUS_LIBERADO) {
                    continue;
                }

                foreach ($item['motivos'] as $m) {
                    $codigo = $m['codigo'] ?? '?';
                    $contagem[$codigo] = ($contagem[$codigo] ?? 0) + 1;

                    // Guarda a grafia menos corrompida, igual ao catálogo.
                    $atual = $descricoes[$codigo] ?? null;
                    $nova  = $m['descricao'] ?? '';

                    if ($atual === null || substr_count($nova, '?') < substr_count($atual, '?')) {
                        $descricoes[$codigo] = $nova;
                    }
                }
            }
        }

        if (empty($contagem)) {
            return;
        }

        arsort($contagem);

        $this->line('  Motivos de glosa (' . count($contagem) . ' códigos distintos):');

        foreach (array_slice($contagem, 0, 15, true) as $codigo => $n) {
            $this->line(sprintf(
                '    %5dx  %-9s %s',
                $n, $codigo, mb_strimwidth($descricoes[$codigo] ?? '', 0, 58, '…')
            ));
        }

        $this->newLine();
    }

    private function naoConciliados(array $dados): void
    {
        $orfaos = 0;
        $valor = 0.0;

        foreach ($dados['remessas'] as $remessa) {
            foreach ($remessa['itens'] as $item) {
                if ($item['status'] !== GlosaItem::STATUS_LIBERADO && $item['appointment_id'] === null) {
                    $orfaos++;
                    $valor += $item['vl_glosa'];
                }
            }
        }

        if ($orfaos === 0) {
            return;
        }

        $this->warn(sprintf(
            '  %d itens glosados (R$ %s) não têm atendimento correspondente na plataforma.',
            $orfaos, number_format($valor, 2, ',', '.')
        ));
        $this->line('  Não somem do total, mas não podem ser atribuídos a um profissional.');
        $this->newLine();
    }
}
