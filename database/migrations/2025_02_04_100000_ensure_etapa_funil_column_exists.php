<?php

use Core\Migration;
use Core\Database;

return new class extends Migration
{
    public function up(): void
    {
        $db = Database::getInstance();
        
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
        
        // Verifica se a coluna etapa_funil já existe
        $columnExists = false;
        try {
            $columns = $db->query("SHOW COLUMNS FROM `leads` LIKE 'etapa_funil'");
            $columnExists = !empty($columns);
        } catch (\Exception $e) {
            $columnExists = false;
        }
        
        // Se a coluna não existe, adiciona
        if (!$columnExists) {
            try {
                // Tenta adicionar após status_kanban se existir
                $hasStatusKanban = false;
                try {
                    $statusColumns = $db->query("SHOW COLUMNS FROM `leads` LIKE 'status_kanban'");
                    $hasStatusKanban = !empty($statusColumns);
                } catch (\Exception $e) {
                    $hasStatusKanban = false;
                }
                
                if ($hasStatusKanban) {
                    $db->execute("ALTER TABLE `leads` ADD COLUMN `etapa_funil` ENUM('interessados', 'negociacao_proposta', 'fechamento') DEFAULT 'interessados' AFTER `status_kanban`");
                } else {
                    $db->execute("ALTER TABLE `leads` ADD COLUMN `etapa_funil` ENUM('interessados', 'negociacao_proposta', 'fechamento') DEFAULT 'interessados'");
                }
                
                // Atualiza registros existentes que não têm etapa_funil definida
                $db->execute("UPDATE `leads` SET `etapa_funil` = 'interessados' WHERE `etapa_funil` IS NULL");
                
            } catch (\Exception $e) {
                // Se falhar, tenta sem AFTER
                try {
                    $db->execute("ALTER TABLE `leads` ADD COLUMN `etapa_funil` ENUM('interessados', 'negociacao_proposta', 'fechamento') DEFAULT 'interessados'");
                    $db->execute("UPDATE `leads` SET `etapa_funil` = 'interessados' WHERE `etapa_funil` IS NULL");
                } catch (\Exception $e2) {
                    // Se ainda falhar, pode ser que a coluna já existe mas com outro tipo
                    // Tenta alterar o tipo se necessário
                    try {
                        $db->execute("ALTER TABLE `leads` MODIFY COLUMN `etapa_funil` ENUM('interessados', 'negociacao_proposta', 'fechamento') DEFAULT 'interessados'");
                    } catch (\Exception $e3) {
                        // Ignora erro - coluna pode já existir com tipo diferente
                        error_log("Erro ao adicionar/modificar coluna etapa_funil: " . $e3->getMessage());
                    }
                }
            }
        } else {
            // Coluna existe, mas verifica se precisa atualizar valores NULL
            try {
                $db->execute("UPDATE `leads` SET `etapa_funil` = 'interessados' WHERE `etapa_funil` IS NULL");
            } catch (\Exception $e) {
                // Ignora erro
            }
        }
        
        // Adiciona índice se não existir
        try {
            $indexes = $db->query("SHOW INDEXES FROM `leads` WHERE Key_name = 'leads_etapa_funil_index'");
            if (empty($indexes)) {
                $db->execute("CREATE INDEX `leads_etapa_funil_index` ON `leads` (`etapa_funil`)");
            }
        } catch (\Exception $e) {
            // Ignora erro - índice pode já existir
        }
    }

    public function down(): void
    {
        // Não remove a coluna no down para evitar perda de dados
        // Se necessário reverter, faça manualmente
    }
};

