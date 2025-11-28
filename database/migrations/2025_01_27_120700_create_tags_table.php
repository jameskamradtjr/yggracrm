<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('tags', function (Schema $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('name');
        });
    }

    public function down(): void
    {
        $this->dropTable('tags');
    }
};

