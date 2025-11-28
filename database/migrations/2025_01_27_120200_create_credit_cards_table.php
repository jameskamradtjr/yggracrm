<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('credit_cards', function (Schema $table) {
            $table->id();
            $table->string('name');
            $table->enum('brand', ['visa', 'mastercard', 'elo', 'amex', 'hipercard', 'outros'])->default('outros');
            $table->bigInteger('bank_account_id')->nullable();
            $table->integer('closing_day')->default(1); // Dia de fechamento (1-31)
            $table->integer('due_day')->default(10); // Dia de vencimento (1-31)
            $table->decimal('limit', 15, 2)->default(0);
            $table->boolean('alert_limit')->default(false);
            $table->integer('alert_percentage')->default(90); // Alerta quando atingir X% do limite
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('bank_account_id');
        });
    }

    public function down(): void
    {
        $this->dropTable('credit_cards');
    }
};

