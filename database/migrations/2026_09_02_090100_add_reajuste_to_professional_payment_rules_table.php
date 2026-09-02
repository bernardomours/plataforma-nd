<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reajuste automático por tempo de empresa: exatamente 2 degraus, contados a partir
     * de `professionals.contract_date` — aos 9 meses soma `valor_reajuste` ao valor
     * vigente, aos 18 meses soma de novo. Sem recorrência depois disso (não é "a cada 9
     * meses pra sempre").
     *
     * `valor_base` congela o valor no momento em que a regra foi criada — não é usado no
     * cálculo em si (que sempre lê `amount`, o valor VIGENTE), só serve pra mostrar na
     * tela "começou em R$X, hoje R$Y" sem precisar reconstruir isso a partir do log de
     * atividade.
     *
     * As duas datas de aplicação são o estado (null = ainda não aplicado) — não um
     * booleano solto — porque também documentam QUANDO cada degrau foi aplicado, sem
     * precisar caçar no log de atividade pra saber.
     */
    public function up(): void
    {
        Schema::table('professional_payment_rules', function (Blueprint $table) {
            $table->decimal('valor_base', 10, 2)->nullable()->after('amount');
            $table->decimal('valor_reajuste', 10, 2)->nullable()->after('valor_base');
            $table->date('reajuste_9_meses_aplicado_em')->nullable()->after('valor_reajuste');
            $table->date('reajuste_18_meses_aplicado_em')->nullable()->after('reajuste_9_meses_aplicado_em');
        });
    }

    public function down(): void
    {
        Schema::table('professional_payment_rules', function (Blueprint $table) {
            $table->dropColumn(['valor_base', 'valor_reajuste', 'reajuste_9_meses_aplicado_em', 'reajuste_18_meses_aplicado_em']);
        });
    }
};
