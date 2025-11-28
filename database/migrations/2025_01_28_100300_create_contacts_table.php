<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('contacts', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->enum('tipo', ['telefone', 'email', 'whatsapp', 'presencial', 'outro'])->default('telefone');
            $table->string('assunto')->nullable();
            $table->text('descricao');
            $table->date('data_contato');
            $table->string('hora_contato', 8)->nullable();
            $table->enum('resultado', ['sem_resposta', 'agendado', 'interessado', 'nao_interessado', 'retornar'])->default('sem_resposta');
            $table->text('observacoes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->index('lead_id');
            $table->index('client_id');
            $table->index('user_id');
            $table->index('data_contato');
        });
    }

    public function down(): void
    {
        $this->dropTable('contacts');
    }
};

