<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('project_cards', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id'); // Multi-tenancy
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->enum('coluna', ['backlog', 'a_fazer', 'fazendo', 'testes', 'publicado'])->default('backlog');
            $table->enum('prioridade', ['baixa', 'media', 'alta', 'urgente'])->default('media');
            $table->unsignedBigInteger('responsible_user_id')->nullable(); // Responsável
            $table->date('data_prazo')->nullable(); // Prazo do card
            $table->integer('ordem')->default(0); // Ordem dentro da coluna
            $table->timestamps();
            
            $table->index('project_id');
            $table->index('user_id');
            $table->index('coluna');
            $table->index('responsible_user_id');
            $table->foreign('project_id', 'id', 'projects', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('project_cards');
    }
};

