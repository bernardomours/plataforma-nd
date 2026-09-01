<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Mesmo padrão de 'avaliador_neuro': papel aditivo, atribuído pela tela de Usuários
     * (checkbox), sem tocar em ninguém automaticamente. Governa quem vê a Agenda Diária
     * na página inicial (ver "Agenda Diária da Recepção" no CLAUDE.md) — recepção não
     * deixa de ser 'administrative', só ganha esse papel a mais.
     */
    public function up(): void
    {
        Role::firstOrCreate(['name' => 'recepcao', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Role::where('name', 'recepcao')->where('guard_name', 'web')->delete();
    }
};
