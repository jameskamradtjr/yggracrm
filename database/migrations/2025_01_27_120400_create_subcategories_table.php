<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('subcategories', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->index('category_id');
            $table->index('user_id');
            $table->foreign('category_id', 'id', 'categories', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('subcategories');
    }
};

