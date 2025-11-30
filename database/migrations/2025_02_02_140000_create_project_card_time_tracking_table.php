<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('project_card_time_tracking', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('user_id');
            $table->datetime('inicio');
            $table->datetime('fim')->nullable();
            $table->integer('tempo_segundos')->default(0); // Tempo em segundos
            $table->text('observacoes')->nullable();
            $table->timestamps();
            
            $table->index('card_id');
            $table->index('user_id');
            $table->index('inicio');
            
            $table->foreign('card_id', 'id', 'project_cards', 'CASCADE');
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('project_card_time_tracking');
    }
};

