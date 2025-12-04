<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('lead_quiz_responses', function (Schema $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned();
            $table->bigInteger('quiz_id')->unsigned();
            $table->bigInteger('quiz_step_id')->unsigned()->nullable();
            $table->string('field_name')->nullable(); // Nome do campo (ex: 'ramo', 'objetivo')
            $table->text('response'); // Resposta do usuário
            $table->integer('points')->default(0); // Pontos obtidos nesta resposta
            $table->timestamps();
            
            $table->index('lead_id');
            $table->index('quiz_id');
            $table->index('quiz_step_id');
            $table->index('field_name');
        });
    }

    public function down(): void
    {
        $this->dropTable('lead_quiz_responses');
    }
};

