<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda a carga horária planejada do MÊS, congelada no momento em que a CH é salva.
     *
     * Por que uma coluna nova em vez de reaproveitar planned_hours:
     *
     *   - planned_hours guarda o valor SEMANAL (e é varchar, apesar do nome falar em horas
     *     e o conteúdo ser quantidade de sessões). Mudar o significado dela invalidaria os
     *     500 registros existentes;
     *   - o valor mensal deixou de ser "semanal × 4": agora depende de quantas segundas,
     *     terças etc. o mês tem, e dos feriados. Não dá para derivar um do outro;
     *   - a tela precisa exibir os dois — o total do mês em destaque e o "x/semana" abaixo.
     *
     * Então os dois convivem: planned_hours = semanal, planned_sessions = mensal congelado.
     *
     * CONGELAMENTO: uma vez gravado, o valor do mês não muda mais sozinho. Se a agenda for
     * alterada em outubro, a CH de agosto permanece com o que foi apurado à época — que é
     * o comportamento esperado para competência fechada.
     */
    public function up(): void
    {
        Schema::table('requested_services', function (Blueprint $table) {
            $table->unsignedSmallInteger('planned_sessions')->nullable()->after('planned_hours');

            // Marca se o número veio da agenda ou foi digitado à mão. A tela usa isso para
            // sinalizar "ajustado manualmente" e para saber se pode oferecer o recálculo.
            $table->boolean('planned_from_schedule')->default(false)->after('planned_sessions');
        });

        // Backfill: converte o que existe pela regra antiga (semanal × 4), preservando o
        // comportamento atual do painel. Fica marcado como NÃO vindo da agenda, porque de
        // fato foi digitado manualmente na época.
        DB::statement("
            UPDATE requested_services
            SET planned_sessions = ROUND(COALESCE(NULLIF(planned_hours, '') + 0, 0) * 4)
            WHERE planned_hours IS NOT NULL
              AND planned_hours <> ''
              AND COALESCE(NULLIF(planned_hours, '') + 0, 0) > 0
        ");
    }

    public function down(): void
    {
        Schema::table('requested_services', function (Blueprint $table) {
            $table->dropColumn(['planned_sessions', 'planned_from_schedule']);
        });
    }
};
