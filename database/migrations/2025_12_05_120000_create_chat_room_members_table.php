<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('chat_room_members', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('chat_room_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            
            $table->foreign('chat_room_id', 'id', 'chat_rooms', 'CASCADE');
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
            $table->unique(['chat_room_id', 'user_id']);
            $table->index('chat_room_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        $this->dropTable('chat_room_members');
    }
};

