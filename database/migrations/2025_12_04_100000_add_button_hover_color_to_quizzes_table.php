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
        $db = \Core\Database::getInstance();
        
        // Verifica se a tabela quizzes existe
        $tableExists = false;
        try {
            $result = $db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'quizzes'");
            $tableExists = !empty($result) && $result[0]['count'] > 0;
        } catch (\Exception $e) {
            // Tenta método alternativo
            try {
                $db->query("SELECT 1 FROM `quizzes` LIMIT 1");
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
            $columns = $db->query("SHOW COLUMNS FROM `quizzes` LIKE 'button_hover_color'");
            $columnExists = !empty($columns);
        } catch (\Exception $e) {
            // Se não conseguir verificar, assume que não existe
        }
        
        // Adiciona coluna se não existir
        if (!$columnExists) {
            try {
                $db->execute("ALTER TABLE `quizzes` ADD COLUMN `button_hover_color` VARCHAR(7) NULL AFTER `button_text_color`");
            } catch (\Exception $e) {
                // Ignora se já existe ou se houver outro erro
                error_log("Erro ao adicionar coluna button_hover_color: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $db = \Core\Database::getInstance();
        
        try {
            // Verifica se a coluna existe antes de remover
            $columns = $db->query("SHOW COLUMNS FROM `quizzes` LIKE 'button_hover_color'");
            if (!empty($columns)) {
                $db->execute("ALTER TABLE `quizzes` DROP COLUMN `button_hover_color`");
            }
        } catch (\Exception $e) {
            // Ignora erros ao reverter
            error_log("Erro ao remover coluna button_hover_color: " . $e->getMessage());
        }
    }
};

