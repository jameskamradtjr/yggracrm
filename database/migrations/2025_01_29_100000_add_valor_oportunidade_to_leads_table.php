<?php

use Core\Migration;

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
        
        // Adiciona campo valor_oportunidade na tabela leads (apenas se não existir)
        if (!in_array('valor_oportunidade', $existingColumns)) {
            try {
                $db->execute("ALTER TABLE `leads` ADD COLUMN `valor_oportunidade` DECIMAL(15, 2) NULL DEFAULT NULL AFTER `score_potencial`");
            } catch (\Exception $e) {
                // Se não conseguir adicionar após score_potencial, tenta sem AFTER
                try {
                    $db->execute("ALTER TABLE `leads` ADD COLUMN `valor_oportunidade` DECIMAL(15, 2) NULL DEFAULT NULL");
                } catch (\Exception $e2) {
                    // Ignora se já existe
                }
            }
        }
        
        // Índice para facilitar consultas (apenas se não existir)
        try {
            $indexes = $db->query("SHOW INDEXES FROM `leads`");
            $existingIndexes = array_column($indexes, 'Key_name');
            
            if (!in_array('leads_valor_oportunidade_index', $existingIndexes)) {
                try {
                    $this->addIndex('leads', 'valor_oportunidade', 'leads_valor_oportunidade_index');
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
        $this->dropIndex('leads', 'leads_valor_oportunidade_index');
        $this->dropColumn('leads', 'valor_oportunidade');
    }
};

