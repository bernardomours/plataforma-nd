<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Acompanhamento do recurso de glosa: quando a clínica contesta a glosa junto ao
     * convênio, parte do valor pode ser "recursada" e, dela, parte "acatada" (devolvida).
     *
     * Um recurso por lote de glosa (unique em glosa_batch_id) — não há histórico de
     * reenvios aqui, só o estado atual do recurso daquele lote. Preenchido manualmente
     * pela coordenação; nada disso vem de importação.
     *
     * Os percentuais de conversão (recursado/glosa, acatado/recursado) não são gravados:
     * são derivados na tela a partir de glosa_batches.vl_glosa e dos valores aqui.
     */
    public function up(): void
    {
        Schema::create('glosa_recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('glosa_batch_id')->unique()->constrained('glosa_batches')->cascadeOnDelete();

            // Número do lote do recurso junto ao convênio — não é o mesmo "lote" de
            // glosa_items.lote (esse é o lote de faturamento original do item).
            $table->string('lote', 30)->nullable();

            $table->decimal('valor_recursado', 12, 2)->nullable();
            $table->decimal('valor_acatado', 12, 2)->nullable();

            // Lista fechada por enquanto: em_analise, pagamento_efetuado. Guardado como
            // slug estável, não o rótulo — a tela traduz para exibição.
            $table->string('status', 30)->nullable();

            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glosa_recursos');
    }
};
