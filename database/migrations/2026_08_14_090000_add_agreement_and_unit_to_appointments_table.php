<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Congela no atendimento o convênio e a unidade sob os quais ele efetivamente ocorreu.
     *
     * MOTIVO: até aqui essas duas informações existiam apenas em `patients`, ou seja, todo
     * relatório respondia "qual o convênio/unidade deste paciente AGORA" em vez de "sob qual
     * convênio/unidade este atendimento ACONTECEU". Consequências reais já observadas na
     * base de produção:
     *
     *   - paciente #492 transferida de Natal para Santa Cruz em 27/05/2026: os 6 atendimentos
     *     (30 sessões) realizados em Natal passaram a ser contabilizados em Santa Cruz;
     *   - paciente #299 teve o convênio alterado de Unimed para Humana em 26/03/2026, o que
     *     reatribuiu todo o histórico dela — inclusive para efeito da regra de duração de
     *     sessão, que é diferente entre os dois convênios.
     *
     * É o mesmo princípio de uma nota fiscal guardar o preço praticado em vez de consultar
     * a tabela de preços atual: são fatos do momento da transação.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('agreement_id')->nullable()->after('service_type_id')
                  ->constrained('agreements')->nullOnDelete();

            $table->foreignId('unit_id')->nullable()->after('agreement_id')
                  ->constrained('units')->nullOnDelete();
        });

        // Backfill a partir do paciente. É seguro e não perde informação: hoje o valor
        // efetivo de todo atendimento JÁ É o do cadastro do paciente — é exatamente esse
        // o problema que estamos corrigindo. O backfill apenas materializa o que os
        // relatórios já assumem, para que daqui em diante deixe de mudar sozinho.
        //
        // Os registros anteriores às duas trocas acima ficarão com o valor atual do
        // paciente (não há como reconstruir o histórico retroativamente); a correção
        // desses dois casos precisa ser feita manualmente, se desejado.
        DB::statement('
            UPDATE appointments a
            JOIN patients p ON p.id = a.patient_id
            SET a.agreement_id = p.agreement_id,
                a.unit_id      = p.unit_id
        ');

        Schema::table('appointments', function (Blueprint $table) {
            // Os relatórios passam a filtrar por estas colunas; sem índice seria varredura
            // completa nas ~33 mil linhas, como já acontecia antes dos índices de apuração.
            $table->index('agreement_id', 'appointments_agreement_id_index');
            $table->index(['unit_id', 'appointment_date'], 'appointments_unit_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_agreement_id_index');
            $table->dropIndex('appointments_unit_date_index');

            $table->dropConstrainedForeignId('agreement_id');
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};
