<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    /**
     * Executa a migration
     */
    public function up(): void
    {
        // Adiciona coluna user_id para multi-tenancy
        // NULL = usuário principal (owner), preenchido = sub-usuário
        $this->addColumn('leads', 'user_id', 'BIGINT UNSIGNED', [
            'nullable' => true
        ]);
        
        // Adiciona índice
        $this->addIndex('leads', 'user_id', 'leads_user_id_index');
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropIndex('leads', 'leads_user_id_index');
        $this->dropColumn('leads', 'user_id');
    }
};
