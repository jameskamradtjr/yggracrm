<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    /**
     * Executa a migration
     */
    public function up(): void
    {
        $this->createTable('password_resets', function (Schema $table) {
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            
            // Índices
            $table->index('email');
            $table->index('token');
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropTable('password_resets');
    }
};

