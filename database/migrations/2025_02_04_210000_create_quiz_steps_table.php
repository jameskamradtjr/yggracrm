<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('quiz_steps', function (Schema $table) {
            $table->id();
            $table->bigInteger('quiz_id')->unsigned();
            $table->string('title'); // Título da etapa/pergunta
            $table->text('description')->nullable(); // Descrição/instruções
            $table->enum('type', ['text', 'textarea', 'select', 'radio', 'checkbox', 'email', 'phone', 'number'])->default('text');
            $table->boolean('required')->default(true);
            $table->integer('order')->default(0); // Ordem da etapa
            $table->integer('points')->default(0); // Pontos que esta etapa vale
            $table->string('field_name')->nullable(); // Nome do campo (para mapear para lead)
            $table->timestamps();
            
            $table->index('quiz_id');
            $table->index('order');
        });
    }

    public function down(): void
    {
        $this->dropTable('quiz_steps');
    }
};

