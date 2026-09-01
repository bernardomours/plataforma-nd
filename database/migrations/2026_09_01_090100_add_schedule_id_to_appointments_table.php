<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Até aqui Schedule (grade fixa semanal) e Appointment (atendimento realizado) nunca
     * foram ligados — tabelas totalmente separadas, casadas só informalmente por
     * paciente+profissional+terapia+dia. Isso bastava pro faturamento/CH (que agregam por
     * mês), mas não basta pra Agenda Diária da Recepção saber, pra UM dia específico, se
     * aquele horário da grade já foi resolvido (atendido ou falta) — precisa de um vínculo
     * confiável, não uma coincidência de dados. Nullable: continua null pra todo
     * atendimento lançado pelas telas antigas (manual, importação CSV), que não têm
     * (nem precisam ter) horário fixo de origem.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('schedule_id')->nullable()->after('service_type_id')
                  ->constrained('schedules')->nullOnDelete();

            // Consulta típica da Agenda Diária: "existe atendimento deste horário fixo
            // nesta data?" — feita em lote pra todos os horários do dia de uma vez.
            $table->index(['schedule_id', 'appointment_date'], 'appointments_schedule_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_schedule_date_index');
            $table->dropConstrainedForeignId('schedule_id');
        });
    }
};
