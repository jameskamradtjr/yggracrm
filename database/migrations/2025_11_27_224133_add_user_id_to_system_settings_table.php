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
        // Remove unique do campo key (agora pode ter mesma key para diferentes users)
        $this->execute("ALTER TABLE `system_settings` DROP INDEX `key`");
        
        // Adiciona coluna user_id para multi-tenancy
        $this->addColumn('system_settings', 'user_id', 'BIGINT UNSIGNED', [
            'nullable' => true
        ]);
        
        // Adiciona índice composto (key + user_id) para garantir unicidade por usuário
        $this->execute("CREATE UNIQUE INDEX `system_settings_key_user_id_unique` ON `system_settings` (`key`, `user_id`)");
        
        // Adiciona índice para user_id
        $this->addIndex('system_settings', 'user_id', 'system_settings_user_id_index');
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropIndex('system_settings', 'system_settings_key_user_id_unique');
        $this->dropIndex('system_settings', 'system_settings_user_id_index');
        $this->dropColumn('system_settings', 'user_id');
        
        // Restaura unique no campo key
        $this->execute("ALTER TABLE `system_settings` ADD UNIQUE INDEX `key` (`key`)");
    }
};
