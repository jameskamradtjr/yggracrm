<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('drive_folders', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Dono da pasta
            $table->unsignedBigInteger('parent_id')->nullable(); // Pasta pai (para subpastas)
            $table->string('name'); // Nome da pasta
            $table->string('color', 20)->nullable(); // Cor da pasta (opcional)
            $table->text('description')->nullable(); // Descrição
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('parent_id');
            $table->index(['user_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        $this->dropTable('drive_folders');
    }
};

