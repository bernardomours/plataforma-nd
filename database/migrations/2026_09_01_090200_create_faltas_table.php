<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de falta de paciente numa terapia agendada. Até aqui a única visão da
     * falta era indireta e sem motivo: CH Solicitada calculava "faltam N sessões pra
     * bater com o planejado" (diferença numérica), sem saber quando faltou nem por quê.
     * Fica fora de `appointments` de propósito — falta não é um atendimento que
     * aconteceu, é o registro de que ele NÃO aconteceu; misturar as duas coisas na mesma
     * tabela contaminaria toda soma de sessões realizadas.
     */
    public function up(): void
    {
        Schema::create('faltas', function (Blueprint $table) {
            $table->id();

            // Nullable: falta pode ser registrada mesmo sem um Schedule de origem exato
            // (ex.: profissional substituto registrando por um horário que não é seu).
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();

            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->foreignId('therapy_id')->constrained('therapies');
            $table->foreignId('service_type_id')->constrained('service_types');

            $table->date('date');

            // Lista fechada + 'outro' com texto livre — combinado com o usuário.
            $table->string('motivo'); // viagem | doenca | nao_informado | outro
            $table->text('observacao')->nullable();

            // Quem registrou (profissional, no fluxo normal; recepção, como reforço).
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // CH Solicitada consulta "faltas deste paciente+terapia neste mês" pro ícone
            // de detalhe; Agenda Diária consulta "falta deste horário fixo nesta data".
            $table->index(['patient_id', 'therapy_id', 'date'], 'faltas_patient_therapy_date_index');
            $table->index(['schedule_id', 'date'], 'faltas_schedule_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faltas');
    }
};
