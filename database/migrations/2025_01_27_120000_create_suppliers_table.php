<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('suppliers', function (Schema $table) {
            $table->id();
            $table->string('name');
            $table->string('fantasy_name')->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->text('additional_info')->nullable();
            $table->boolean('is_client')->default(false);
            $table->boolean('receives_invoice')->default(false);
            $table->boolean('issues_invoice')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->index('name');
            $table->index('cnpj');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        $this->dropTable('suppliers');
    }
};

