<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('automations', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('workflow_data')->nullable(); // Dados do workflow (nodes, connections)
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('is_active');
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('automations');
    }
};

