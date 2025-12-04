<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('site_posts', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_site_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable(); // Resumo do post
            $table->text('content'); // Conteúdo completo do post
            $table->enum('type', ['text', 'youtube', 'twitter'])->default('text');
            $table->string('external_url')->nullable(); // URL do YouTube ou Twitter
            $table->string('featured_image')->nullable(); // Imagem destacada
            $table->integer('likes_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->boolean('published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->index('user_site_id');
            $table->index('slug');
            $table->index('published');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        $this->dropTable('site_posts');
    }
};

