<?php

use Core\Migration;
use Core\Database;

return new class extends Migration
{
    public function up(): void
    {
        $db = Database::getInstance();
        
        // Verifica se a tabela clients existe
        $tables = $db->query("SHOW TABLES LIKE 'clients'");
        if (empty($tables)) {
            return;
        }
        
        // Verifica se a coluna foto já existe
        $columns = $db->query("SHOW COLUMNS FROM clients LIKE 'foto'");
        if (empty($columns)) {
            // Adiciona coluna foto (VARCHAR 500 para suportar URLs do S3)
            $db->execute("ALTER TABLE clients ADD COLUMN foto VARCHAR(500) NULL AFTER instagram");
        } else {
            // Atualiza para VARCHAR(500) se já existir
            try {
                $db->execute("ALTER TABLE clients MODIFY COLUMN foto VARCHAR(500) NULL");
            } catch (\Exception $e) {
                // Ignora se já for VARCHAR(500) ou maior
                error_log("Aviso ao atualizar coluna foto: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        $db = Database::getInstance();
        
        try {
            $db->execute("ALTER TABLE clients DROP COLUMN foto");
        } catch (\Exception $e) {
            // Ignora erros
        }
    }
};

