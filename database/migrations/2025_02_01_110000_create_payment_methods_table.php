<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('payment_methods', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Multi-tenancy
            $table->string('nome'); // Ex: "PIX", "Boleto", "Cartão de Crédito", "Stripe"
            $table->enum('tipo', ['pix', 'boleto', 'credito', 'debito', 'transferencia', 'dinheiro', 'outro'])->default('outro');
            $table->decimal('taxa', 5, 2)->default(0.00); // Taxa em percentual (ex: 2.99 para 2.99%)
            $table->unsignedBigInteger('conta_id')->nullable(); // Conta onde o dinheiro será recebido
            $table->integer('dias_recebimento')->default(0); // Quantos dias demora para receber (0 = à vista)
            $table->boolean('adicionar_taxa_como_despesa')->default(false); // Se a taxa deve ser adicionada como despesa
            $table->boolean('ativo')->default(true);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('tipo');
            $table->index('conta_id');
            $table->index('ativo');
            
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
            $table->foreign('conta_id', 'id', 'bank_accounts', 'SET NULL');
        });
    }

    public function down(): void
    {
        $this->dropTable('payment_methods');
    }
};

