<?php

use Core\Migration;
use Core\Database;

return new class extends Migration
{
    public function up(): void
    {
        $db = Database::getInstance();
        
        $tables = $db->query("SHOW TABLES LIKE 'users'");
        if (empty($tables)) {
            return;
        }
        
        $columns = $db->query("SHOW COLUMNS FROM users");
        $columnNames = array_column($columns, 'Field');
        
        if (in_array('avatar', $columnNames)) {
            try {
                // Altera para VARCHAR(255) se não for já
                $columnInfo = $db->queryOne(
                    "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'users' 
                     AND COLUMN_NAME = 'avatar'"
                );
                
                if ($columnInfo && strpos(strtolower($columnInfo['COLUMN_TYPE']), 'varchar') === false) {
                    $db->execute("ALTER TABLE users MODIFY COLUMN avatar VARCHAR(255) NULL");
                }
            } catch (\Exception $e) {
                // Ignora se não conseguir alterar
            }
        } else {
            // Adiciona coluna se não existir
            $this->addColumn('users', 'avatar', 'VARCHAR(255)', ['nullable' => true]);
        }
    }

    public function down(): void
    {
        // Não reverte, mantém a coluna
    }
};


