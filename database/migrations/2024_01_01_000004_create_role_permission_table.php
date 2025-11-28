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
        $this->createTable('role_permission', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('role_id', 'id', 'roles', 'CASCADE');
            $table->foreign('permission_id', 'id', 'permissions', 'CASCADE');
            
            // Índices
            $table->unique(['role_id', 'permission_id']);
            $table->index('role_id');
            $table->index('permission_id');
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropTable('role_permission');
    }
};

