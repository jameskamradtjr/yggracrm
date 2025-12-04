<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('post_likes', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('site_post_id');
            $table->string('ip_address'); // IP do usuário que curtiu
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index('site_post_id');
            $table->index('ip_address');
            $table->unique(['site_post_id', 'ip_address']); // Um like por IP por post
        });
    }

    public function down(): void
    {
        $this->dropTable('post_likes');
    }
};

