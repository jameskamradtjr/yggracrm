<?php

return new class extends \Core\Migration {
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
        
        // Adiciona novos campos para quiz de software (apenas se não existirem)
        if (!in_array('tem_software', $existingColumns)) {
            try {
                $db->execute("ALTER TABLE `leads` ADD COLUMN `tem_software` TINYINT(1) DEFAULT 0 AFTER `faz_trafego`");
            } catch (\Exception $e) {
                // Se não conseguir adicionar após faz_trafego, tenta sem AFTER
                try {
                    $db->execute("ALTER TABLE `leads` ADD COLUMN `tem_software` TINYINT(1) DEFAULT 0");
                } catch (\Exception $e2) {
                    // Ignora se já existe
                }
            }
        }
        
        if (!in_array('investimento_software', $existingColumns)) {
            try {
                $db->execute("ALTER TABLE `leads` ADD COLUMN `investimento_software` VARCHAR(50) NULL");
            } catch (\Exception $e) {
                // Ignora se já existe
            }
        }
        
        if (!in_array('tipo_sistema', $existingColumns)) {
            try {
                $db->execute("ALTER TABLE `leads` ADD COLUMN `tipo_sistema` ENUM('interno', 'cliente', 'saas') NULL");
            } catch (\Exception $e) {
                // Ignora se já existe
            }
        }
        
        if (!in_array('plataforma_app', $existingColumns)) {
            try {
                $db->execute("ALTER TABLE `leads` ADD COLUMN `plataforma_app` ENUM('ios_android', 'ios', 'android', 'nenhum') NULL");
            } catch (\Exception $e) {
                // Ignora se já existe
            }
        }
        
        if (!in_array('origem_conheceu', $existingColumns)) {
            try {
                $db->execute("ALTER TABLE `leads` ADD COLUMN `origem_conheceu` VARCHAR(255) NULL");
            } catch (\Exception $e) {
                // Ignora se já existe
            }
        }
        
        // Índice para origem_conheceu para relatórios (apenas se não existir)
        try {
            $indexes = $db->query("SHOW INDEXES FROM `leads`");
            $existingIndexes = array_column($indexes, 'Key_name');
            
            if (!in_array('leads_origem_conheceu_index', $existingIndexes)) {
                try {
                    $this->addIndex('leads', 'origem_conheceu', 'leads_origem_conheceu_index');
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
        $db = \Core\Database::getInstance();
        
        $this->dropIndex('leads', 'leads_origem_conheceu_index');
        $db->execute("ALTER TABLE `leads` DROP COLUMN `origem_conheceu`");
        $db->execute("ALTER TABLE `leads` DROP COLUMN `plataforma_app`");
        $db->execute("ALTER TABLE `leads` DROP COLUMN `tipo_sistema`");
        $db->execute("ALTER TABLE `leads` DROP COLUMN `investimento_software`");
        $db->execute("ALTER TABLE `leads` DROP COLUMN `tem_software`");
    }
};

