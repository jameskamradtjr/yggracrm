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
        $this->createTable('permissions', function (Schema $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('resource')->comment('Ex: users, posts, orders');
            $table->string('action')->comment('Ex: create, read, update, delete');
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('slug');
            $table->index(['resource', 'action']);
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropTable('permissions');
    }
};

