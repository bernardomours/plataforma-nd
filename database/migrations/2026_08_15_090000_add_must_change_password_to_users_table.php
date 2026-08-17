<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca as contas que ainda estão com a senha padrão e precisam trocá-la no login.
     *
     * O cadastro de profissional cria o User com a senha fixa 'mudar123'. Como ela é
     * pública e previsível, qualquer pessoa que saiba o e-mail de um profissional recém
     * cadastrado consegue entrar. Esta coluna, somada ao middleware
     * EnsurePasswordIsChanged, obriga a definição de uma senha nova no primeiro acesso.
     *
     * O backfill identifica as contas afetadas testando o hash — assim só é marcado quem
     * de fato ainda está com 'mudar123'. Quem já trocou não é incomodado.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });

        // Hash::check é uma operação cara por natureza (bcrypt). Com algumas centenas de
        // usuários a varredura leva alguns segundos — é execução única, na migration.
        $marcados = 0;

        DB::table('users')->select('id', 'password')->orderBy('id')->chunk(200, function ($usuarios) use (&$marcados) {
            $ids = [];

            foreach ($usuarios as $usuario) {
                if ($usuario->password && Hash::check('mudar123', $usuario->password)) {
                    $ids[] = $usuario->id;
                }
            }

            if ($ids) {
                DB::table('users')->whereIn('id', $ids)->update(['must_change_password' => true]);
                $marcados += count($ids);
            }
        });

        if ($marcados > 0) {
            echo "  -> {$marcados} conta(s) ainda com a senha padrão foram marcadas para troca obrigatória.\n";
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
