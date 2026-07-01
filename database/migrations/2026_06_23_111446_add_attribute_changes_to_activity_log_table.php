<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // Adiciona a coluna attribute_changes se não existir
            if (!Schema::hasColumn('activity_log', 'attribute_changes')) {
                $table->json('attribute_changes')->nullable()->after('properties');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            if (Schema::hasColumn('activity_log', 'attribute_changes')) {
                $table->dropColumn('attribute_changes');
            }
        });
    }
};