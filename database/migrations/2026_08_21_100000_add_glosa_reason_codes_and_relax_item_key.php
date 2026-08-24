<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajustes descobertos ao importar o CSV consolidado (11 competências, 2 prestadores).
     *
     * 1. O par (guia, item) SE REPETE dentro da mesma remessa — 335 casos em 73 mil linhas.
     *    São cobranças legítimas do mesmo procedimento na mesma guia. O índice único barrava
     *    a importação. A idempotência continua garantida no nível da remessa, pelo hash e
     *    pela dupla competência+prestador; reimportar troca a remessa inteira.
     *
     *    ORDEM IMPORTA: a foreign key de glosa_batch_id se apoia nesse índice único, por ser
     *    a coluna mais à esquerda. Dropar antes de ter substituto dá "needed in a foreign key
     *    constraint" (erro 1553). Cria o novo, depois remove o antigo.
     *
     * 2. O mesmo código de motivo chega com duas grafias, porque a conversão para CSV perdeu
     *    os acentos em parte dos meses: "Cobrança de item..." e "Cobran?a de item...". Sem
     *    catálogo o ranking parte o motivo em duas linhas — e a grafia corrompida costuma ser
     *    a mais frequente, então não dá para desempatar por contagem. O código (CM100, CM89,
     *    3145) é ASCII e nunca corrompe: ele é a chave.
     *
     * Idempotente: ALTER TABLE no MySQL commita na hora e não volta atrás, então cada passo
     * confere se já foi feito.
     */
    public function up(): void
    {
        if (! $this->temIndice('glosa_items', 'glosa_items_batch_guia_index')) {
            Schema::table('glosa_items', function (Blueprint $table) {
                $table->index(['glosa_batch_id', 'guia'], 'glosa_items_batch_guia_index');
            });
        }

        if ($this->temIndice('glosa_items', 'glosa_items_batch_guia_item_unique')) {
            Schema::table('glosa_items', function (Blueprint $table) {
                $table->dropUnique('glosa_items_batch_guia_item_unique');
            });
        }

        if (! Schema::hasTable('glosa_reason_codes')) {
            Schema::create('glosa_reason_codes', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 20)->unique();

                // Melhor grafia vista até agora: a que tem menos caractere de substituição.
                $table->text('descricao')->nullable();

                // Espaço para a clínica anotar o que fazer para não repetir a glosa.
                $table->text('orientacao')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('glosa_reason_codes');

        if (! $this->temIndice('glosa_items', 'glosa_items_batch_guia_item_unique')) {
            Schema::table('glosa_items', function (Blueprint $table) {
                $table->unique(['glosa_batch_id', 'guia', 'item_codigo'], 'glosa_items_batch_guia_item_unique');
            });
        }

        if ($this->temIndice('glosa_items', 'glosa_items_batch_guia_index')) {
            Schema::table('glosa_items', function (Blueprint $table) {
                $table->dropIndex('glosa_items_batch_guia_index');
            });
        }
    }

    private function temIndice(string $tabela, string $indice): bool
    {
        return ! empty(DB::select(
            'SHOW INDEX FROM ' . $tabela . ' WHERE Key_name = ?',
            [$indice]
        ));
    }
};
