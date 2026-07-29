<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quality_processes', function (Blueprint $table) {
            $table->id();
            $table->string('sector');
            $table->string('process_name'); 
            $table->string('procedure_code');
            $table->date('due_date')->nullable();
            
            $table->enum('status', [
                'pendente', 
                'em_andamento', 
                'concluido', 
                'atrasado'
            ])->default('pendente');
            
            $table->integer('progress')->default(0);
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_processes');
    }
};
