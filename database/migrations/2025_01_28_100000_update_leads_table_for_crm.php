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
            // Tabela não existe, pula esta migration (será criada por outra migration)
            return;
        }
        
        // Verifica quais colunas já existem
        $existingColumns = [];
        try {
            $columns = $db->query("SHOW COLUMNS FROM `leads`");
            $existingColumns = array_column($columns, 'Field');
        } catch (\Exception $e) {
            // Se não conseguir verificar, assume que nenhuma coluna existe
        }
        
        // Adiciona novos campos à tabela leads (apenas se não existirem)
        if (!in_array('etapa_funil', $existingColumns)) {
            try {
                $db->execute("ALTER TABLE `leads` ADD COLUMN `etapa_funil` ENUM('interessados', 'negociacao_proposta', 'fechamento') DEFAULT 'interessados' AFTER `status_kanban`");
            } catch (\Exception $e) {
                // Se não conseguir adicionar após status_kanban, tenta sem AFTER
                try {
                    $db->execute("ALTER TABLE `leads` ADD COLUMN `etapa_funil` ENUM('interessados', 'negociacao_proposta', 'fechamento') DEFAULT 'interessados'");
                } catch (\Exception $e2) {
                    // Ignora se já existe
                }
            }
        }
        
        if (!in_array('origem', $existingColumns)) {
            try {
                $db->execute("ALTER TABLE `leads` ADD COLUMN `origem` VARCHAR(255) NULL");
            } catch (\Exception $e) {
                // Ignora se já existe
            }
        }
        
        if (!in_array('responsible_user_id', $existingColumns)) {
            try {
                $db->execute("ALTER TABLE `leads` ADD COLUMN `responsible_user_id` BIGINT UNSIGNED NULL");
            } catch (\Exception $e) {
                // Ignora se já existe
            }
        }
        
        if (!in_array('client_id', $existingColumns)) {
            try {
                $db->execute("ALTER TABLE `leads` ADD COLUMN `client_id` BIGINT UNSIGNED NULL");
            } catch (\Exception $e) {
                // Ignora se já existe
            }
        }
        
        // Índices (apenas se não existirem)
        try {
            $indexes = $db->query("SHOW INDEXES FROM `leads`");
            $existingIndexes = array_column($indexes, 'Key_name');
            
            if (!in_array('leads_etapa_funil_index', $existingIndexes)) {
                try {
                    $this->addIndex('leads', 'etapa_funil', 'leads_etapa_funil_index');
                } catch (\Exception $e) {
                    // Ignora se já existe
                }
            }
            
            if (!in_array('leads_responsible_user_id_index', $existingIndexes)) {
                try {
                    $this->addIndex('leads', 'responsible_user_id', 'leads_responsible_user_id_index');
                } catch (\Exception $e) {
                    // Ignora se já existe
                }
            }
            
            if (!in_array('leads_client_id_index', $existingIndexes)) {
                try {
                    $this->addIndex('leads', 'client_id', 'leads_client_id_index');
                } catch (\Exception $e) {
                    // Ignora se já existe
                }
            }
        } catch (\Exception $e) {
            // Ignora erro ao verificar índices
        }
    }

    public function down(): void
    {
        $this->dropIndex('leads', 'leads_client_id_index');
        $this->dropIndex('leads', 'leads_responsible_user_id_index');
        $this->dropIndex('leads', 'leads_etapa_funil_index');
        
        $this->dropColumn('leads', 'client_id');
        $this->dropColumn('leads', 'responsible_user_id');
        $this->dropColumn('leads', 'origem');
        $this->dropColumn('leads', 'etapa_funil');
    }
};

