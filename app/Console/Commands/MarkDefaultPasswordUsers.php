<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Marca para troca obrigatória as contas que ainda estão com a senha padrão 'mudar123'.
 *
 * Ficou fora da migration de propósito: identificar essas contas exige um Hash::check por
 * usuário, e bcrypt é lento por construção. Dentro da migration, num servidor
 * compartilhado, isso parecia travamento — e interromper deixava a coluna criada sem o
 * registro em `migrations`, quebrando a execução seguinte com "Duplicate column name".
 *
 * Aqui é seguro interromper e rodar de novo: o comando apenas relê e remarca.
 */
class MarkDefaultPasswordUsers extends Command
{
    protected $signature = 'usuarios:marcar-senha-padrao
                            {--senha=mudar123 : Senha padrão a procurar}
                            {--fix : Aplica a marcação. Sem esta flag o comando apenas conta.}';

    protected $description = 'Marca contas que ainda usam a senha padrão para troca obrigatória no login';

    public function handle(): int
    {
        $senhaPadrao = (string) $this->option('senha');
        $aplicar     = (bool) $this->option('fix');

        if (! \Illuminate\Support\Facades\Schema::hasColumn('users', 'must_change_password')) {
            $this->error('A coluna must_change_password ainda não existe. Rode "php artisan migrate" antes.');

            return self::FAILURE;
        }

        $total = DB::table('users')->whereNull('deleted_at')->count();

        $this->info("Verificando {$total} conta(s) ativa(s). Bcrypt é lento — isto pode levar alguns minutos.");
        $this->newLine();

        $barra = $this->output->createProgressBar($total);
        $barra->start();

        $encontradas = 0;
        $marcadas    = 0;
        $jaMarcadas  = 0;

        DB::table('users')
            ->select('id', 'name', 'email', 'password', 'must_change_password')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(100, function ($usuarios) use (&$encontradas, &$marcadas, &$jaMarcadas, $senhaPadrao, $aplicar, $barra) {
                $paraMarcar = [];

                foreach ($usuarios as $usuario) {
                    $barra->advance();

                    if (! $usuario->password || ! Hash::check($senhaPadrao, $usuario->password)) {
                        continue;
                    }

                    $encontradas++;

                    if ($usuario->must_change_password) {
                        $jaMarcadas++;

                        continue;
                    }

                    $paraMarcar[] = $usuario->id;
                }

                if ($aplicar && $paraMarcar) {
                    DB::table('users')->whereIn('id', $paraMarcar)->update(['must_change_password' => true]);
                    $marcadas += count($paraMarcar);
                }
            });

        $barra->finish();
        $this->newLine(2);

        $this->line("Contas ainda com a senha padrão .. <fg=yellow>{$encontradas}</>");
        $this->line("Já marcadas anteriormente ....... {$jaMarcadas}");

        if (! $aplicar) {
            $pendentes = $encontradas - $jaMarcadas;
            $this->newLine();
            $this->warn("{$pendentes} conta(s) ainda precisam ser marcadas. Nada foi alterado.");
            $this->line('Para aplicar:  php artisan usuarios:marcar-senha-padrao --fix');

            return self::SUCCESS;
        }

        $this->line("Marcadas agora .................. <fg=green>{$marcadas}</>");
        $this->newLine();
        $this->info('Concluído. Essas pessoas cairão na tela de troca de senha no próximo acesso.');

        return self::SUCCESS;
    }
}
