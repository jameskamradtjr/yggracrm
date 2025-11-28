<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('notificacoes', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id'); // Usuário que recebe a notificação
            $table->string('tipo', 50); // 'info', 'success', 'warning', 'error'
            $table->string('titulo', 200);
            $table->text('mensagem');
            $table->string('link')->nullable(); // Link para ação
            $table->boolean('lida')->default(false);
            $table->timestamp('created_at')->nullable();
            
            $table->index('usuario_id');
            $table->index('lida');
            $table->index('created_at');
            $table->foreign('usuario_id', 'id', 'users', 'CASCADE');
        });
        
        // Adiciona DEFAULT CURRENT_TIMESTAMP após criar a tabela
        $this->db->execute("ALTER TABLE `notificacoes` MODIFY COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->dropTable('notificacoes');
    }
};

