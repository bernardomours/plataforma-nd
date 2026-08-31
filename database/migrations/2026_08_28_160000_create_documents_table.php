<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laudos e documentos (paciente) e, no futuro, documentos pessoais (profissional).
     * Polimórfica de propósito — mesma tabela para os dois, mesmo padrão de
     * MovementHistory nesse projeto — em vez de duas tabelas quase idênticas.
     *
     * Guarda só metadado aqui; o arquivo em si vive no disco 'documents'
     * (config/filesystems.php), local por padrão e trocável pra S3/R2 via .env
     * sem migração de schema.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->string('documentable_type');
            $table->unsignedBigInteger('documentable_id');

            // Qual disco guarda o arquivo — registrado por linha (não só lido da config
            // atual) porque, se o disco padrão mudar no futuro, os arquivos antigos
            // continuam no disco onde foram salvos; sem isso perderíamos a referência.
            $table->string('disk');
            $table->string('path');

            // Nome que o usuário enviou — nunca usado como nome de arquivo no disco
            // (o path é um UUID), só para exibir/baixar com o nome original.
            $table->string('nome_original');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamanho_bytes');

            $table->string('categoria', 30);

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documentable_type', 'documentable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
