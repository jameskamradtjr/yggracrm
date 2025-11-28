<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('user_permissions', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // ID do usuário que tem a permissão
            $table->string('module', 100); // Ex: 'gerenciamento', 'dashboard', 'sistema'
            $table->string('resource', 100); // Ex: 'usuarios', 'roles', 'logs'
            $table->string('action', 50); // Ex: 'view', 'create', 'edit', 'delete', 'all'
            $table->boolean('granted')->default(true);
            $table->unsignedBigInteger('owner_user_id'); // ID do owner (master admin)
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('owner_user_id');
            $table->index(['module', 'resource']);
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('user_permissions');
    }
};


