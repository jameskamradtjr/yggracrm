<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('sistema_logs', function (Schema $table) {
            $table->id();
            $table->string('tabela', 100); // Nome da tabela afetada
            $table->unsignedBigInteger('registro_id')->nullable(); // ID do registro afetado
            $table->string('acao', 50); // CREATE, UPDATE, DELETE, VIEW, etc
            $table->text('descricao')->nullable();
            $table->text('dados_anteriores')->nullable(); // JSON
            $table->text('dados_novos')->nullable(); // JSON
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            
            $table->index('tabela');
            $table->index('acao');
            $table->index('usuario_id');
            $table->index('created_at');
            $table->foreign('usuario_id', 'id', 'users', 'SET NULL');
        });
        
        // Adiciona DEFAULT CURRENT_TIMESTAMP após criar a tabela
        $this->db->execute("ALTER TABLE `sistema_logs` MODIFY COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->dropTable('sistema_logs');
    }
};

