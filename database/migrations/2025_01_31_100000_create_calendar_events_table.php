<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verifica quais tabelas existem antes de criar foreign keys
        $tablesToCheck = ['users', 'clients', 'leads', 'projects'];
        $existingTables = [];
        
        foreach ($tablesToCheck as $tableName) {
            try {
                $result = $this->db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$tableName}'");
                if (!empty($result) && $result[0]['count'] > 0) {
                    $existingTables[] = $tableName;
                }
            } catch (\Exception $e) {
                // Tenta método alternativo
                try {
                    $this->db->query("SELECT 1 FROM `{$tableName}` LIMIT 1");
                    $existingTables[] = $tableName;
                } catch (\Exception $e2) {
                    // Tabela não existe
                }
            }
        }
        
        $this->createTable('calendar_events', function (Schema $table) use ($existingTables) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Multi-tenancy
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->dateTime('data_inicio');
            $table->dateTime('data_fim')->nullable();
            $table->enum('cor', ['danger', 'success', 'primary', 'warning'])->default('primary');
            $table->boolean('dia_inteiro')->default(false);
            $table->string('localizacao')->nullable();
            $table->text('observacoes')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('data_inicio');
            $table->index('data_fim');
            $table->index('client_id');
            $table->index('lead_id');
            $table->index('project_id');
            
            // Só cria foreign keys se as tabelas existirem
            if (in_array('users', $existingTables)) {
                try {
                    $table->foreign('user_id', 'id', 'users', 'CASCADE');
                } catch (\Exception $e) {
                    // Ignora erro ao criar foreign key
                }
            }
            
            if (in_array('clients', $existingTables)) {
                try {
                    $table->foreign('client_id', 'id', 'clients', 'SET NULL');
                } catch (\Exception $e) {
                    // Ignora erro ao criar foreign key
                }
            }
            
            if (in_array('leads', $existingTables)) {
                try {
                    $table->foreign('lead_id', 'id', 'leads', 'SET NULL');
                } catch (\Exception $e) {
                    // Ignora erro ao criar foreign key
                }
            }
            
            if (in_array('projects', $existingTables)) {
                try {
                    $table->foreign('project_id', 'id', 'projects', 'SET NULL');
                } catch (\Exception $e) {
                    // Ignora erro ao criar foreign key
                }
            }
        });
        
        // Tenta criar foreign keys depois se as tabelas existirem mas não foram criadas durante o createTable
        foreach ($existingTables as $refTable) {
            try {
                $indexes = $this->db->query("SHOW INDEXES FROM `calendar_events`");
                $existingIndexes = array_column($indexes, 'Key_name');
                
                $fkName = "fk_calendar_events_{$refTable}_id";
                $columnName = $refTable === 'users' ? 'user_id' : ($refTable . '_id');
                $onDelete = $refTable === 'users' ? 'CASCADE' : 'SET NULL';
                
                if (!in_array($fkName, $existingIndexes)) {
                    try {
                        $this->db->execute("ALTER TABLE `calendar_events` ADD CONSTRAINT `{$fkName}` FOREIGN KEY (`{$columnName}`) REFERENCES `{$refTable}`(`id`) ON DELETE {$onDelete}");
                    } catch (\Exception $e) {
                        // Ignora se já existe ou se houver erro
                    }
                }
            } catch (\Exception $e) {
                // Ignora erro ao verificar/criar foreign keys
            }
        }
    }

    public function down(): void
    {
        $this->dropTable('calendar_events');
    }
};

