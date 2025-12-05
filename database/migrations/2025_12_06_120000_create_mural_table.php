<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('mural', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('titulo', 255);
            $table->text('descricao')->nullable();
            $table->string('imagem_url', 500)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->string('link_texto', 100)->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->boolean('is_ativo')->default(true);
            $table->integer('ordem')->default(0);
            $table->timestamps();
            
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
            $table->index('user_id');
            $table->index('is_ativo');
            $table->index('data_inicio');
            $table->index('data_fim');
        });
    }

    public function down(): void
    {
        $this->dropTable('mural');
    }
};

