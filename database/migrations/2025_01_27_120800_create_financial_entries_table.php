<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('financial_entries', function (Schema $table) {
            $table->id();
            $table->enum('type', ['entrada', 'saida', 'transferencia'])->default('saida');
            $table->string('description');
            $table->date('competence_date'); // Data de competência
            $table->date('due_date')->nullable(); // Data de vencimento
            $table->date('payment_date')->nullable(); // Data de pagamento/recebimento
            $table->date('release_date')->nullable(); // Data de liberação (para recebimentos via cartão)
            $table->decimal('value', 15, 2);
            $table->decimal('paid_value', 15, 2)->nullable(); // Valor pago (pode ser parcial)
            $table->decimal('fees', 15, 2)->default(0); // Multa
            $table->decimal('interest', 15, 2)->default(0); // Juros
            
            // Relacionamentos
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('credit_card_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('sub_cost_center_id')->nullable();
            
            // Status e flags
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_received')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_installment')->default(false);
            $table->integer('installment_number')->nullable(); // Número da parcela (ex: 1 de 10)
            $table->integer('total_installments')->nullable(); // Total de parcelas
            $table->unsignedBigInteger('parent_entry_id')->nullable(); // ID do lançamento pai (para parcelas)
            $table->enum('recurrence_type', ['mensal', 'semanal', 'diario', 'anual'])->nullable();
            $table->date('recurrence_end_date')->nullable();
            
            // Observações e anexos
            $table->text('observations')->nullable();
            $table->json('attachments')->nullable(); // Array de caminhos de arquivos
            
            // Responsável
            $table->unsignedBigInteger('responsible_user_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('type');
            $table->index('competence_date');
            $table->index('due_date');
            $table->index('is_paid');
            $table->index('is_received');
            $table->index('bank_account_id');
            $table->index('credit_card_id');
            $table->index('category_id');
            $table->index('cost_center_id');
            $table->index('user_id');
            $table->index('parent_entry_id');
            
            // Foreign keys
            $table->foreign('bank_account_id', 'id', 'bank_accounts', 'SET NULL');
            $table->foreign('credit_card_id', 'id', 'credit_cards', 'SET NULL');
            $table->foreign('supplier_id', 'id', 'suppliers', 'SET NULL');
            $table->foreign('category_id', 'id', 'categories', 'SET NULL');
            $table->foreign('subcategory_id', 'id', 'subcategories', 'SET NULL');
            $table->foreign('cost_center_id', 'id', 'cost_centers', 'SET NULL');
            $table->foreign('sub_cost_center_id', 'id', 'sub_cost_centers', 'SET NULL');
            $table->foreign('parent_entry_id', 'id', 'financial_entries', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('financial_entries');
    }
};

