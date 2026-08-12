<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A tela de CH Solicitada cruza requested_services com appointments para apurar as
     * horas realizadas. A tabela appointments tem ~33 mil linhas e NENHUM índice nas
     * colunas de cruzamento — o EXPLAIN acusava "type=ALL / key=NENHUMA / rows=32134",
     * ou seja, varredura completa a cada consulta.
     *
     * Os índices abaixo são aditivos: não alteram dado nem comportamento, apenas o plano
     * de execução. down() remove todos, então a migration é totalmente reversível.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Índice principal da apuração: casa paciente + terapia + tipo de atendimento
            // e ainda cobre o recorte por data usado no agrupamento mensal.
            $table->index(
                ['patient_id', 'therapy_id', 'service_type_id', 'appointment_date'],
                'appointments_apuracao_ch_index'
            );

            // Filtros das telas de Terapias Realizadas e Produção.
            $table->index('appointment_date', 'appointments_appointment_date_index');
            $table->index('professional_id', 'appointments_professional_id_index');
        });

        Schema::table('patients', function (Blueprint $table) {
            // A trait IsolatesByUnit filtra por unit_id em TODA consulta de paciente, e o
            // soft delete adiciona deleted_at. O índice composto atende os dois casos
            // (o MySQL usa o prefixo mais à esquerda, então serve para unit_id sozinho).
            $table->index(['unit_id', 'deleted_at'], 'patients_unit_deleted_index');
        });

        Schema::table('requested_services', function (Blueprint $table) {
            // Recorte por competência (mês/ano) e junção com o paciente.
            $table->index(['patient_id', 'therapy_id', 'service_type_id'], 'requested_services_apuracao_index');
            $table->index('month_year', 'requested_services_month_year_index');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_apuracao_ch_index');
            $table->dropIndex('appointments_appointment_date_index');
            $table->dropIndex('appointments_professional_id_index');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_unit_deleted_index');
        });

        Schema::table('requested_services', function (Blueprint $table) {
            $table->dropIndex('requested_services_apuracao_index');
            $table->dropIndex('requested_services_month_year_index');
        });
    }
};
