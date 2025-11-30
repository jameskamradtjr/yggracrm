<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('contract_templates', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Multi-tenancy
            $table->string('nome'); // Nome do template
            $table->text('conteudo'); // Conteúdo do contrato com variáveis (ex: {{nome_cliente}})
            $table->text('variaveis_disponiveis')->nullable(); // JSON com lista de variáveis disponíveis
            $table->boolean('ativo')->default(true);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('ativo');
            
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('contract_templates');
    }
};

