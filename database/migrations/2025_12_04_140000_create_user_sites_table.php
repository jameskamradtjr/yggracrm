<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('user_sites', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('slug')->unique(); // URL única do site (ex: gregisenberg)
            $table->string('logo_url')->nullable(); // URL da logo
            $table->string('photo_url')->nullable(); // Foto do perfil
            $table->text('bio')->nullable(); // Texto sobre o usuário
            $table->string('twitter_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('newsletter_title')->default('Newsletter'); // Título da newsletter
            $table->text('newsletter_description')->nullable(); // Descrição da newsletter
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        $this->dropTable('user_sites');
    }
};

