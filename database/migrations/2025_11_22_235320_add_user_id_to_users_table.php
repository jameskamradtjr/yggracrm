<?php

use Core\Migration;

return new class extends Migration
{
    /**
     * Executa a migration
     */
    public function up(): void
    {
        // Adiciona coluna user_id para multi-tenancy (owner da conta)
        // NULL = usuário principal (owner), preenchido = sub-usuário
        $this->addColumn('users', 'user_id', 'BIGINT UNSIGNED', [
            'nullable' => true
        ]);
        
        // Adiciona índice
        $this->addIndex('users', 'user_id', 'users_user_id_index');
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropIndex('users', 'users_user_id_index');
        $this->dropColumn('users', 'user_id');
    }
};
