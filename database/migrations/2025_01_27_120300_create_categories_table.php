<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('categories', function (Schema $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['entrada', 'saida', 'outros'])->default('saida');
            $table->string('color', 7)->nullable(); // Cor em hex (#RRGGBB)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('type');
            $table->index('name');
        });
    }

    public function down(): void
    {
        $this->dropTable('categories');
    }
};

