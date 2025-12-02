<?php

use Core\Migration;
use Core\Database;

return new class extends Migration
{
    public function up(): void
    {
        $db = Database::getInstance();
        
        // Verifica se a tabela users existe
        $tables = $db->query("SHOW TABLES LIKE 'users'");
        if (empty($tables)) {
            return;
        }
        
        // Verifica se a coluna avatar existe
        $columns = $db->query("SHOW COLUMNS FROM users LIKE 'avatar'");
        if (empty($columns)) {
            // Adiciona coluna se não existir
            $db->execute("ALTER TABLE users ADD COLUMN avatar VARCHAR(500) NULL");
        } else {
            // Atualiza para VARCHAR(500) para suportar URLs longas do S3
            try {
                $db->execute("ALTER TABLE users MODIFY COLUMN avatar VARCHAR(500) NULL");
            } catch (\Exception $e) {
                // Ignora se já for VARCHAR(500) ou maior
                error_log("Aviso ao atualizar coluna avatar: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        $db = Database::getInstance();
        
        try {
            $db->execute("ALTER TABLE users MODIFY COLUMN avatar VARCHAR(255) NULL");
        } catch (\Exception $e) {
            // Ignora erros
        }
    }
};

