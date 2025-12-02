<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        
        // Verifica se a tabela leads existe
        $tableExists = false;
        try {
            $result = $db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'leads'");
            $tableExists = !empty($result) && $result[0]['count'] > 0;
        } catch (\Exception $e) {
            // Tenta método alternativo
            try {
                $db->query("SELECT 1 FROM `leads` LIMIT 1");
                $tableExists = true;
            } catch (\Exception $e2) {
                $tableExists = false;
            }
        }
        
        if (!$tableExists) {
            // Tabela não existe, pula esta migration
            return;
        }
        
        // Verifica se a coluna client_id já existe
        $columnExists = false;
        try {
            $columns = $db->query("SHOW COLUMNS FROM `leads` LIKE 'client_id'");
            $columnExists = !empty($columns);
        } catch (\Exception $e) {
            // Se não conseguir verificar, assume que não existe
        }
        
        if (!$columnExists) {
            try {
                // Tenta adicionar após uma coluna conhecida
                $db->execute("ALTER TABLE `leads` ADD COLUMN `client_id` BIGINT UNSIGNED NULL AFTER `user_id`");
            } catch (\Exception $e) {
                // Se não conseguir adicionar após user_id, tenta sem AFTER
                try {
                    $db->execute("ALTER TABLE `leads` ADD COLUMN `client_id` BIGINT UNSIGNED NULL");
                } catch (\Exception $e2) {
                    // Se ainda falhar, tenta adicionar no final
                    try {
                        $db->execute("ALTER TABLE `leads` ADD COLUMN `client_id` BIGINT UNSIGNED NULL");
                    } catch (\Exception $e3) {
                        error_log("Erro ao adicionar coluna client_id na tabela leads: " . $e3->getMessage());
                    }
                }
            }
        }
        
        // Verifica e adiciona índice se não existir
        try {
            $indexes = $db->query("SHOW INDEXES FROM `leads`");
            $existingIndexes = array_column($indexes, 'Key_name');
            
            if (!in_array('leads_client_id_index', $existingIndexes)) {
                try {
                    $this->addIndex('leads', 'client_id', 'leads_client_id_index');
                } catch (\Exception $e) {
                    // Ignora se já existe ou se houver erro
                    error_log("Erro ao adicionar índice leads_client_id_index: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            // Ignora erro ao verificar índices
            error_log("Erro ao verificar índices da tabela leads: " . $e->getMessage());
        }
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        
        try {
            $this->dropIndex('leads', 'leads_client_id_index');
        } catch (\Exception $e) {
            // Ignora se não existir
        }
        
        try {
            $db->execute("ALTER TABLE `leads` DROP COLUMN `client_id`");
        } catch (\Exception $e) {
            // Ignora se não existir
        }
    }
};

