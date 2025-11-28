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
        $this->createTable('user_role', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
            $table->foreign('role_id', 'id', 'roles', 'CASCADE');
            
            // Índices
            $table->unique(['user_id', 'role_id']);
            $table->index('user_id');
            $table->index('role_id');
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropTable('user_role');
    }
};

