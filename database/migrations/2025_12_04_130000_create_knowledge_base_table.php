<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('knowledge_base', function (Schema $table) {
            $table->id();
            $table->string('titulo');
            $table->text('conteudo'); // Conteúdo completo do artigo
            $table->text('resumo')->nullable(); // Resumo/descrição curta
            $table->unsignedBigInteger('client_id')->nullable(); // Cliente relacionado (opcional)
            $table->string('categoria')->nullable(); // Categoria do conhecimento
            $table->enum('status', ['rascunho', 'publicado', 'arquivado'])->default('rascunho');
            $table->integer('visualizacoes')->default(0); // Contador de visualizações
            $table->unsignedBigInteger('user_id'); // Criador do conhecimento
            $table->timestamps();
            
            $table->index('client_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('categoria');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        $this->dropTable('knowledge_base');
    }
};

