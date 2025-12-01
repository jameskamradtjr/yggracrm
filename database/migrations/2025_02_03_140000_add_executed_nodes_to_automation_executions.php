<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verifica se a tabela existe
        $tableExists = false;
        try {
            $result = $this->db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'automation_executions'");
            $tableExists = !empty($result) && $result[0]['count'] > 0;
        } catch (\Exception $e) {
            // Tenta método alternativo
            try {
                $this->db->query("SELECT 1 FROM `automation_executions` LIMIT 1");
                $tableExists = true;
            } catch (\Exception $e2) {
                $tableExists = false;
            }
        }
        
        if (!$tableExists) {
            // Tabela não existe, pula esta migration
            return;
        }
        
        // Verifica se a coluna já existe
        $columnExists = false;
        try {
            $columns = $this->db->query("SHOW COLUMNS FROM `automation_executions`");
            $existingColumns = array_column($columns, 'Field');
            $columnExists = in_array('executed_nodes', $existingColumns);
        } catch (\Exception $e) {
            // Se não conseguir verificar, assume que não existe
        }
        
        if (!$columnExists) {
            try {
                $this->db->execute("ALTER TABLE automation_executions ADD COLUMN executed_nodes TEXT NULL COMMENT 'JSON array de IDs de nós já executados' AFTER execution_log");
            } catch (\Exception $e) {
                // Se não conseguir adicionar após execution_log, tenta sem AFTER
                try {
                    $this->db->execute("ALTER TABLE automation_executions ADD COLUMN executed_nodes TEXT NULL COMMENT 'JSON array de IDs de nós já executados'");
                } catch (\Exception $e2) {
                    // Ignora se já existe ou se houver outro erro
                }
            }
        }
    }

    public function down(): void
    {
        $this->db->execute("ALTER TABLE automation_executions DROP COLUMN executed_nodes");
    }
};

