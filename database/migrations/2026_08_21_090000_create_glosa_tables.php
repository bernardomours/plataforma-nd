<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro das glosas do convênio, importado do relatório NAT_RELATORIO_CPLS.
     *
     * Por que tabela própria e não colunas em `appointments`:
     *
     *   - o relatório é documento do convênio, com vida própria. Ele chega meses depois do
     *     atendimento (a competência 06/2026 foi emitida em 10/07/2026 e cobre serviços de
     *     abril e maio) e pode ser reemitido. Gravar direto no atendimento reescreveria
     *     histórico já fechado — o mesmo problema que convênio e unidade tiveram;
     *   - 21% das linhas não encontram atendimento na plataforma. Elas precisam existir
     *     mesmo assim, senão o total importado nunca fecha com o RESUMO do arquivo;
     *   - o valor apresentado/liberado é do convênio, não tem relação com o repasse ao
     *     profissional, que sai de `professional_payment_rules`.
     *
     * A glosa é INFORMATIVA: não marca `appointments.is_glosado` nem altera repasse.
     */
    public function up(): void
    {
        // Uma remessa = um arquivo = um prestador em uma competência.
        Schema::create('glosa_batches', function (Blueprint $table) {
            $table->id();

            // Dia 1 do mês. Guardado como date para filtrar por intervalo e usar índice —
            // YEAR()/MONTH() na coluna anulariam o índice.
            $table->date('competencia');

            $table->string('prestador_codigo', 20);
            $table->string('prestador_nome');

            // Resolvida por units.unimed_code, que já guarda o código do prestador.
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->string('arquivo_nome');

            // Impede importar o mesmo arquivo duas vezes e inflar os totais.
            $table->char('arquivo_hash', 64)->unique();

            // Totais declarados no RESUMO do rodapé do relatório. Servem de conferência:
            // o que foi gravado tem que bater com o que o convênio diz ter enviado.
            $table->unsignedInteger('total_itens')->default(0);
            $table->decimal('vl_apresentado', 12, 2)->default(0);
            $table->decimal('vl_liberado', 12, 2)->default(0);
            $table->decimal('vl_glosa', 12, 2)->default(0);

            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['competencia', 'unit_id']);
            $table->unique(['competencia', 'prestador_codigo'], 'glosa_batches_competencia_prestador_unique');
        });

        // Uma linha do relatório.
        Schema::create('glosa_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('glosa_batch_id')->constrained('glosa_batches')->cascadeOnDelete();

            // Desnormalizadas da remessa: quase toda consulta da tela filtra por elas.
            $table->date('competencia');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->date('dt_item');
            $table->string('item_codigo', 30)->nullable();
            $table->string('item_descricao')->nullable();

            $table->string('conta', 30)->nullable();
            $table->string('guia', 30)->nullable();
            $table->string('carteira', 30)->nullable();
            $table->string('lote', 30)->nullable();

            $table->decimal('qt_item', 10, 4)->default(0);
            $table->decimal('taxa', 8, 2)->nullable();
            $table->decimal('vl_apresentado', 12, 2)->default(0);
            $table->decimal('vl_liberado', 12, 2)->default(0);
            $table->decimal('vl_glosa', 12, 2)->default(0);
            $table->decimal('prorata', 12, 2)->default(0);

            // Como vieram no arquivo. Guardados mesmo quando o vínculo é resolvido, porque
            // são a prova do que o convênio afirmou — e porque o nome do "Medico" diverge
            // do cadastro por acento e ordem, então não serve para identificar ninguém.
            $table->string('beneficiario_nome')->nullable();
            $table->string('medico_nome')->nullable();

            // Resolvidos pela guia, que casa 1:1 com appointments.guide. Nulos quando o
            // atendimento não existe na plataforma.
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();

            // glosado = vl_glosa cobre tudo; parcial = glosou parte; liberado = sem glosa.
            $table->string('status', 12)->default('liberado');

            $table->timestamps();

            $table->unique(['glosa_batch_id', 'guia', 'item_codigo'], 'glosa_items_batch_guia_item_unique');
            $table->index(['competencia', 'status']);
            $table->index(['professional_id', 'competencia']);
            $table->index(['patient_id', 'competencia']);
            $table->index('guia');
        });

        // Motivos e pareceres. Tabela filha porque o ranking por motivo é um GROUP BY —
        // em JSON viraria processamento em PHP. O relatório aceita vários por item
        // (o painel antigo mostrava até 3 ocorrências e 2 pareceres).
        Schema::create('glosa_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('glosa_item_id')->constrained('glosa_items')->cascadeOnDelete();

            $table->string('tipo', 12)->default('ocorrencia');
            $table->string('codigo', 20)->nullable();
            $table->text('descricao')->nullable();

            $table->timestamps();

            $table->index(['codigo', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glosa_reasons');
        Schema::dropIfExists('glosa_items');
        Schema::dropIfExists('glosa_batches');
    }
};
