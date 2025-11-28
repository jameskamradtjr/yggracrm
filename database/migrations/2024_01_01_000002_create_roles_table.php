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
        $this->createTable('roles', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Owner do role (multi-tenant)');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(0)->comment('Roles de sistema não podem ser deletadas');
            $table->timestamps();
            
            // Índices
            $table->index('user_id');
            $table->index('slug');
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropTable('roles');
    }
};

