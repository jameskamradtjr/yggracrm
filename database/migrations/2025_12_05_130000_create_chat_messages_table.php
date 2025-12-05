<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('chat_messages', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('chat_room_id');
            $table->unsignedBigInteger('user_id');
            $table->text('message');
            $table->string('attachment_url', 500)->nullable();
            $table->timestamps();
            
            $table->foreign('chat_room_id', 'id', 'chat_rooms', 'CASCADE');
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
            $table->index('chat_room_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        $this->dropTable('chat_messages');
    }
};

