<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('chat_rooms', function (Schema $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('type', ['public', 'private'])->default('public');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->foreign('created_by', 'id', 'users', 'CASCADE');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        $this->dropTable('chat_rooms');
    }
};

