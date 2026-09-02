<?php

namespace App\Console\Commands;

use App\Models\ProfessionalPaymentRule;
use Illuminate\Console\Command;

/**
 * Reajuste automático por tempo de empresa: exatamente 2 degraus, contados a partir de
 * `professionals.contract_date` — aos 9 meses soma `valor_reajuste` ao valor vigente
 * (`amount`), aos 18 meses soma de novo. Não é recorrente além disso.
 *
 * Roda sozinho todo dia (`routes/console.php`), igual `app:send-birthday-emails` — mas
 * continua funcionando como comando manual normal, DIAGNÓSTICO por padrão: sem --fix
 * apenas lista o que aplicaria.
 */
class ApplyPaymentRuleRaises extends Command
{
    protected $signature = 'profissionais:aplicar-reajustes
                            {--fix : Aplica os reajustes. Sem esta flag o comando apenas lista.}';

    protected $description = 'Lista (ou aplica, com --fix) os reajustes de 9 e 18 meses vencidos';

    public function handle(): int
    {
        // Só profissionais ativos (SoftDeletes exclui trashed por padrão), com
        // contract_date preenchida e valor_reajuste configurado — sem isso não há como
        // nem faz sentido calcular o reajuste.
        $regras = ProfessionalPaymentRule::query()
            ->whereNotNull('valor_reajuste')
            ->whereHas('professional', fn ($q) => $q->whereNotNull('contract_date'))
            ->with('professional')
            ->get();

        $pendentes = [];

        foreach ($regras as $regra) {
            $meses = $regra->professional->mesesDeEmpresa();

            $devidoNove = $meses >= 9 && ! $regra->reajuste_9_meses_aplicado_em;
            $devidoDezoito = $meses >= 18 && ! $regra->reajuste_18_meses_aplicado_em;

            if ($devidoNove || $devidoDezoito) {
                $pendentes[] = [
                    'regra' => $regra,
                    'meses' => $meses,
                    'nove' => $devidoNove,
                    'dezoito' => $devidoDezoito,
                ];
            }
        }

        if (empty($pendentes)) {
            $this->info('Nenhum reajuste pendente. Tudo em dia.');
            return self::SUCCESS;
        }

        $this->warn(count($pendentes) . ' regra(s) de pagamento com reajuste vencido:');
        $this->newLine();

        $linhas = [];
        foreach ($pendentes as $p) {
            $degraus = collect([
                $p['nove'] ? '9 meses' : null,
                $p['dezoito'] ? '18 meses' : null,
            ])->filter()->implode(' + ');

            $novoValor = (float) $p['regra']->amount
                + ($p['nove'] ? (float) $p['regra']->valor_reajuste : 0)
                + ($p['dezoito'] ? (float) $p['regra']->valor_reajuste : 0);

            $linhas[] = [
                $p['regra']->professional->name,
                $p['meses'] . ' meses',
                $degraus,
                'R$ ' . number_format($p['regra']->amount, 2, ',', '.'),
                'R$ ' . number_format($novoValor, 2, ',', '.'),
            ];
        }

        $this->table(['Profissional', 'Tempo de casa', 'Degrau vencido', 'Valor atual', 'Novo valor'], $linhas);

        if (! $this->option('fix')) {
            $this->newLine();
            $this->line('Nada foi alterado. Para aplicar:  php artisan profissionais:aplicar-reajustes --fix');
            return self::SUCCESS;
        }

        $aplicados = 0;

        foreach ($pendentes as $p) {
            $regra = $p['regra'];
            $valorAntes = $regra->amount;
            $acrescimo = 0;

            if ($p['nove']) {
                $acrescimo += (float) $regra->valor_reajuste;
                $regra->reajuste_9_meses_aplicado_em = now()->toDateString();
            }

            if ($p['dezoito']) {
                $acrescimo += (float) $regra->valor_reajuste;
                $regra->reajuste_18_meses_aplicado_em = now()->toDateString();
            }

            $regra->amount = (float) $regra->amount + $acrescimo;
            $regra->save();

            activity()
                ->performedOn($regra)
                ->event('updated')
                ->withProperties(['attributes' => [
                    'acao' => 'Reajuste automático por tempo de empresa',
                    'profissional' => $regra->professional->name,
                    'degrau' => collect([$p['nove'] ? '9 meses' : null, $p['dezoito'] ? '18 meses' : null])->filter()->implode(' + '),
                    'valor_antes' => $valorAntes,
                    'valor_depois' => $regra->amount,
                    'origem' => 'artisan profissionais:aplicar-reajustes --fix',
                ]])
                ->log('Reajuste automático aplicado à regra de pagamento');

            $aplicados++;
        }

        $this->newLine();
        $this->info("{$aplicados} regra(s) reajustada(s). Registrado no controle de atividades.");

        return self::SUCCESS;
    }
}
