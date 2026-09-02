<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `created_at` marca quando o CADASTRO foi feito na plataforma, não quando o
     * profissional de fato começou a trabalhar — os dois podem divergir (cadastro feito
     * dias/semanas depois da contratação real). `contract_date` guarda a data real, e é
     * ela (não `created_at`) que conta pro reajuste automático por tempo de empresa
     * (ver `professional_payment_rules` e o comando `profissionais:aplicar-reajustes`).
     *
     * `formacao` é só informativo por enquanto (Estudante/Graduado/Pós-graduado) — não
     * entra em nenhum cálculo de pagamento ainda.
     */
    public function up(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            $table->date('contract_date')->nullable()->after('birth_date');
            $table->string('formacao')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            $table->dropColumn(['contract_date', 'formacao']);
        });
    }
};
