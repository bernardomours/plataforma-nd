<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
     * ESTA MIGRATION É PROPOSITALMENTE RÁPIDA E IDEMPOTENTE.
     *
     * A identificação de quem ainda usa 'mudar123' exige um Hash::check por conta, e
     * bcrypt é lento por construção — em produção isso levava minutos e parecia travado.
     * Como o MySQL faz commit implícito no ALTER TABLE (DDL não tem rollback), interromper
     * o comando deixava a coluna criada mas SEM o registro na tabela `migrations`, e a
     * execução seguinte falhava com "Duplicate column name".
     *
     * Por isso: aqui só a coluna, protegida por hasColumn. O backfill virou o comando
     * `php artisan usuarios:marcar-senha-padrao`, que pode ser interrompido e repetido
     * sem consequência.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'must_change_password')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'must_change_password')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
