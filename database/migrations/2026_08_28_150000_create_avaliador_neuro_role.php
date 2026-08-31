<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Papel aditivo: um coordenador (ou qualquer outro papel) pode acumular
     * 'avaliador_neuro' para ganhar acesso a Avaliações Neuro sem depender de ser
     * admin/manager/administrative — caso de quem realiza avaliação na prática, não
     * só coordena. Atribuído pela tela de Usuários (checkbox), como qualquer outro
     * papel do Spatie; nada aqui atribui a role a ninguém automaticamente.
     */
    public function up(): void
    {
        Role::firstOrCreate(['name' => 'avaliador_neuro', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Role::where('name', 'avaliador_neuro')->where('guard_name', 'web')->delete();
    }
};
