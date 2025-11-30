<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('contracts', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Multi-tenancy
            $table->unsignedBigInteger('template_id')->nullable(); // Template usado
            $table->unsignedBigInteger('client_id')->nullable(); // Cliente (contratante)
            $table->string('numero_contrato')->unique(); // Ex: C2024-04-4
            $table->string('titulo');
            $table->text('conteudo_gerado'); // Conteúdo após substituição de variáveis
            $table->enum('status', ['rascunho', 'enviado', 'aguardando_assinaturas', 'assinado', 'cancelado', 'vencido'])->default('rascunho');
            $table->date('data_inicio')->nullable();
            $table->date('data_termino')->nullable();
            $table->decimal('valor_total', 15, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->string('link_assinatura')->nullable(); // Link único para assinatura
            $table->string('token_assinatura')->unique()->nullable(); // Token único para assinatura
            $table->dateTime('data_envio')->nullable();
            $table->dateTime('data_assinatura_completa')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('client_id');
            $table->index('template_id');
            $table->index('status');
            $table->index('token_assinatura');
            
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
            $table->foreign('client_id', 'id', 'clients', 'SET NULL');
            $table->foreign('template_id', 'id', 'contract_templates', 'SET NULL');
        });
    }

    public function down(): void
    {
        $this->dropTable('contracts');
    }
};

