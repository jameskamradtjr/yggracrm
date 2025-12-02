<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('quizzes', function (Schema $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('primary_color', 7)->default('#007bff'); // Cor principal do formulário
            $table->string('secondary_color', 7)->default('#6c757d'); // Cor secundária
            $table->string('text_color', 7)->default('#212529'); // Cor do texto
            $table->string('background_color', 7)->default('#ffffff'); // Cor de fundo
            $table->string('button_color', 7)->default('#007bff'); // Cor do botão
            $table->string('button_text_color', 7)->default('#ffffff'); // Cor do texto do botão
            $table->string('logo_url')->nullable(); // URL do logo
            $table->text('welcome_message')->nullable(); // Mensagem de boas-vindas
            $table->text('completion_message')->nullable(); // Mensagem de conclusão
            $table->bigInteger('default_tag_id')->unsigned()->nullable(); // Tag padrão para leads deste quiz
            $table->boolean('active')->default(true);
            $table->bigInteger('user_id')->unsigned();
            $table->timestamps();
            
            $table->index('slug');
            $table->index('user_id');
            $table->index('active');
        });
    }

    public function down(): void
    {
        $this->dropTable('quizzes');
    }
};

