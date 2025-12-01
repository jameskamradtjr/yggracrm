<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verifica se a tabela já existe
        $tableExists = $this->db->query("SHOW TABLES LIKE 'credit_cards'");
        
        if (empty($tableExists)) {
            // Cria a tabela se não existir
            $this->createTable('credit_cards', function (Schema $table) {
                $table->id();
                $table->string('name');
                $table->enum('brand', ['visa', 'mastercard', 'elo', 'amex', 'hipercard', 'outros'])->default('outros');
                $table->integer('closing_day')->default(1); // Dia de fechamento (1-31)
                $table->integer('due_day')->default(10); // Dia de vencimento (1-31)
                $table->decimal('limit', 15, 2)->default(0);
                $table->boolean('alert_limit')->default(false);
                $table->integer('alert_percentage')->default(90); // Alerta quando atingir X% do limite
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('bank_account_id');
            });
        } else {
            // Tabela já existe, verifica e adiciona colunas que faltam
            $columns = $this->db->query("SHOW COLUMNS FROM credit_cards");
            $existingColumns = array_column($columns, 'Field');
            
            // Lista de colunas que devem existir
            $requiredColumns = [
                'id' => "BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
                'name' => "VARCHAR(255) NOT NULL",
                'brand' => "ENUM('visa','mastercard','elo','amex','hipercard','outros') NOT NULL DEFAULT 'outros'",
                'closing_day' => "INT NOT NULL DEFAULT 1",
                'due_day' => "INT NOT NULL DEFAULT 10",
                'limit' => "DECIMAL(15,2) NOT NULL DEFAULT 0",
                'alert_limit' => "TINYINT(1) NOT NULL DEFAULT 0",
                'alert_percentage' => "INT NOT NULL DEFAULT 90",
                'user_id' => "BIGINT UNSIGNED NULL",
                'bank_account_id' => "BIGINT UNSIGNED NULL",
                'created_at' => "TIMESTAMP NULL",
                'updated_at' => "TIMESTAMP NULL"
            ];
            
            // Adiciona colunas que faltam
            foreach ($requiredColumns as $column => $definition) {
                if (!in_array($column, $existingColumns)) {
                    try {
                        if ($column === 'id') {
                            // ID já deve existir, pula
                            continue;
                        }
                        $this->db->execute("ALTER TABLE `credit_cards` ADD COLUMN `{$column}` {$definition}");
                    } catch (\Exception $e) {
                        // Ignora erro se coluna já existe (pode ter sido criada manualmente)
                        if (strpos($e->getMessage(), 'Duplicate column') === false) {
                            throw $e;
                        }
                    }
                }
            }
            
            // Verifica e cria índices se não existirem
            $indexes = $this->db->query("SHOW INDEXES FROM credit_cards");
            $existingIndexes = array_column($indexes, 'Key_name');
            
            if (!in_array('idx_user_id', $existingIndexes)) {
                try {
                    $this->db->execute("CREATE INDEX `idx_user_id` ON `credit_cards` (`user_id`)");
                } catch (\Exception $e) {
                    // Ignora se já existe
                }
            }
            
            if (!in_array('idx_bank_account_id', $existingIndexes)) {
                try {
                    $this->db->execute("CREATE INDEX `idx_bank_account_id` ON `credit_cards` (`bank_account_id`)");
                } catch (\Exception $e) {
                    // Ignora se já existe
                }
            }
        }
    }

    public function down(): void
    {
        $this->dropTable('credit_cards');
    }
};

