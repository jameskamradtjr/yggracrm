<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('quiz_options', function (Schema $table) {
            $table->id();
            $table->bigInteger('quiz_step_id')->unsigned();
            $table->string('label'); // Texto da opção
            $table->string('value')->nullable(); // Valor da opção
            $table->integer('points')->default(0); // Pontos desta opção
            $table->integer('order')->default(0); // Ordem da opção
            $table->timestamps();
            
            $table->index('quiz_step_id');
            $table->index('order');
        });
    }

    public function down(): void
    {
        $this->dropTable('quiz_options');
    }
};

