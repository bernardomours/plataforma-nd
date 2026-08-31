<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Regra revista: um lote de glosa pode, raramente, ter mais de um recurso (reenvio,
     * nova tentativa). Tira o unique de glosa_batch_id, mantendo um índice comum — a FK
     * se apoia no índice da coluna, então o substituto precisa existir antes de dropar
     * o antigo (senão o MySQL recusa com erro 1553).
     */
    public function up(): void
    {
        Schema::table('glosa_recursos', function (Blueprint $table) {
            $table->index('glosa_batch_id', 'glosa_recursos_glosa_batch_id_index');
        });

        Schema::table('glosa_recursos', function (Blueprint $table) {
            $table->dropUnique('glosa_recursos_glosa_batch_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('glosa_recursos', function (Blueprint $table) {
            $table->unique('glosa_batch_id', 'glosa_recursos_glosa_batch_id_unique');
        });

        Schema::table('glosa_recursos', function (Blueprint $table) {
            $table->dropIndex('glosa_recursos_glosa_batch_id_index');
        });
    }
};
