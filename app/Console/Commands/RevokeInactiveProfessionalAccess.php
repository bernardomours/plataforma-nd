<?php

namespace App\Console\Commands;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Regulariza as contas de sistema de profissionais já inativados.
 *
 * Até a correção em Profissionais/Index::registrarSaida(), inativar um profissional não
 * desativava o User criado junto com o cadastro dele. O resultado é um conjunto de contas
 * de pessoas desligadas que continuam podendo entrar no sistema.
 *
 * Este comando é DIAGNÓSTICO por padrão: sem --fix apenas lista o que encontrou.
 */
class RevokeInactiveProfessionalAccess extends Command
{
    protected $signature = 'usuarios:revogar-inativos
                            {--fix : Aplica a revogação. Sem esta flag o comando apenas lista.}';

    protected $description = 'Lista (ou revoga, com --fix) contas ativas de profissionais já inativados';

    public function handle(): int
    {
        // Contas ativas cujo profissional está na lixeira.
        $candidatos = DB::table('users as u')
            ->join('professionals as pr', 'pr.user_id', '=', 'u.id')
            ->whereNotNull('pr.deleted_at')
            ->whereNull('u.deleted_at')
            ->select('u.id as user_id', 'u.name', 'u.email', 'pr.id as prof_id', 'pr.name as prof_name', 'pr.deleted_at')
            ->orderBy('pr.deleted_at')
            ->get();

        if ($candidatos->isEmpty()) {
            $this->info('Nenhuma conta pendente de revogação. Tudo certo.');
            return self::SUCCESS;
        }

        $this->warn($candidatos->count() . ' conta(s) de profissional inativado ainda com acesso ao sistema:');
        $this->newLine();

        $revogaveis = [];
        $linhas = [];

        foreach ($candidatos as $c) {
            // Mesmo cuidado do fluxo de saída: a conta pode ser compartilhada por outro
            // profissional que continua ativo (User::firstOrCreate por e-mail).
            $compartilhada = Professional::where('user_id', $c->user_id)->exists();

            $user = User::find($c->user_id);
            $papeis = $user ? $user->getRoleNames()->implode(', ') : '-';

            $situacao = $compartilhada
                ? 'MANTER (conta compartilhada com profissional ativo)'
                : 'revogar';

            if (! $compartilhada) {
                $revogaveis[] = $c;
            }

            $linhas[] = [
                $c->user_id,
                mb_substr($c->prof_name, 0, 28),
                mb_substr((string) $c->email, 0, 30),
                substr((string) $c->deleted_at, 0, 10),
                $papeis ?: '-',
                $situacao,
            ];
        }

        $this->table(['User', 'Profissional', 'E-mail', 'Inativado em', 'Papéis', 'Ação'], $linhas);

        if (! $this->option('fix')) {
            $this->newLine();
            $this->line('Nada foi alterado. Para aplicar:  php artisan usuarios:revogar-inativos --fix');
            return self::SUCCESS;
        }

        $revogadas = 0;

        foreach ($revogaveis as $c) {
            $user = User::find($c->user_id);

            if (! $user) {
                continue;
            }

            activity()
                ->performedOn($user)
                ->event('deleted')
                ->withProperties(['attributes' => [
                    'acao'         => 'Acesso revogado em regularização de contas órfãs',
                    'profissional' => $c->prof_name,
                    'email'        => $user->email,
                    'origem'       => 'artisan usuarios:revogar-inativos --fix',
                ]])
                ->log('Acesso revogado em regularização de contas de profissionais inativados');

            $user->delete();
            $revogadas++;
        }

        $this->newLine();
        $this->info("{$revogadas} conta(s) revogada(s). Registrado no controle de atividades.");
        $this->line('Para devolver o acesso de alguém: restaure o profissional pela tela de Profissionais.');

        return self::SUCCESS;
    }
}
