<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('bank_accounts', function (Schema $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['conta_corrente', 'conta_poupanca', 'conta_investimento', 'carteira_digital', 'outros'])->default('conta_corrente');
            $table->string('bank_name');
            $table->string('account_number')->nullable();
            $table->string('agency')->nullable();
            $table->string('digit')->nullable();
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('hide_balance')->default(false);
            $table->string('alert_email')->nullable();
            $table->boolean('alert_when_zero')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        $this->dropTable('bank_accounts');
    }
};

