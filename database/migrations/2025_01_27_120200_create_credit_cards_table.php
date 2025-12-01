<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verifica se a tabela já existe de forma mais robusta
        $tableExists = false;
        try {
            $result = $this->db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'credit_cards'");
            $tableExists = !empty($result) && $result[0]['count'] > 0;
        } catch (\Exception $e) {
            // Tenta método alternativo
            try {
                $this->db->query("SELECT 1 FROM `credit_cards` LIMIT 1");
                $tableExists = true;
            } catch (\Exception $e2) {
                $tableExists = false;
            }
        }
        
        if (!$tableExists) {
            // Cria a tabela se não existir
            try {
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
                return; // Sucesso, sai da função
            } catch (\Exception $e) {
                // Se der erro ao criar (pode ser que a tabela exista parcialmente), tenta adicionar colunas
                if (strpos($e->getMessage(), 'already exists') !== false || 
                    strpos($e->getMessage(), 'Duplicate column') !== false ||
                    strpos($e->getMessage(), 'Table') !== false) {
                    $tableExists = true; // Força para o bloco de atualização
                } else {
                    throw $e;
                }
            }
        }
        
        // Tabela já existe ou foi criada parcialmente, verifica e corrige estrutura
        try {
            $columns = $this->db->query("SHOW COLUMNS FROM `credit_cards`");
            $existingColumns = array_column($columns, 'Field');
            
            // Conta quantas vezes bank_account_id aparece (pode estar duplicada)
            $bankAccountIdCount = 0;
            foreach ($columns as $col) {
                if ($col['Field'] === 'bank_account_id') {
                    $bankAccountIdCount++;
                }
            }
            
            // Se bank_account_id aparece mais de uma vez, precisa corrigir
            // Mas não podemos remover diretamente sem saber qual manter
            // Vamos verificar se precisa adicionar colunas que faltam
            
            // Lista de colunas que devem existir (sem duplicatas)
            $requiredColumns = [
                'name' => "VARCHAR(255) NOT NULL",
                'brand' => "ENUM('visa','mastercard','elo','amex','hipercard','outros') NOT NULL DEFAULT 'outros'",
                'closing_day' => "INT NOT NULL DEFAULT 1",
                'due_day' => "INT NOT NULL DEFAULT 10",
                'limit' => "DECIMAL(15,2) NOT NULL DEFAULT 0",
                'alert_limit' => "TINYINT(1) NOT NULL DEFAULT 0",
                'alert_percentage' => "INT NOT NULL DEFAULT 90",
                'user_id' => "BIGINT UNSIGNED NULL",
                'created_at' => "TIMESTAMP NULL",
                'updated_at' => "TIMESTAMP NULL"
            ];
            
            // Adiciona colunas que faltam (exceto bank_account_id que já existe)
            foreach ($requiredColumns as $column => $definition) {
                if (!in_array($column, $existingColumns)) {
                    try {
                        $this->db->execute("ALTER TABLE `credit_cards` ADD COLUMN `{$column}` {$definition}");
                    } catch (\Exception $e) {
                        // Ignora erro se coluna já existe
                        if (strpos($e->getMessage(), 'Duplicate column') === false && 
                            strpos($e->getMessage(), 'already exists') === false) {
                            error_log("Erro ao adicionar coluna {$column}: " . $e->getMessage());
                        }
                    }
                }
            }
            
            // Garante que bank_account_id existe e está correta (BIGINT UNSIGNED)
            if (!in_array('bank_account_id', $existingColumns)) {
                try {
                    $this->db->execute("ALTER TABLE `credit_cards` ADD COLUMN `bank_account_id` BIGINT UNSIGNED NULL");
                } catch (\Exception $e) {
                    // Ignora se já existe
                }
            }
            
            // Verifica e cria índices se não existirem
            try {
                $indexes = $this->db->query("SHOW INDEXES FROM `credit_cards`");
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
            } catch (\Exception $e) {
                // Ignora erro ao verificar índices
            }
        } catch (\Exception $e) {
            // Se não conseguir verificar/corrigir colunas, loga mas não falha
            error_log("Aviso ao verificar colunas de credit_cards: " . $e->getMessage());
            // Não lança exceção para não bloquear outras migrations
        }
    }

    public function down(): void
    {
        $this->dropTable('credit_cards');
    }
};

