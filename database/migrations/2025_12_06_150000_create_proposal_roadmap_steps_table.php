<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('proposal_roadmap_steps', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_id');
            $table->string('title', 255); // Ex: Reunião Inicial, Design, Programação
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // Ordem de exibição
            $table->date('estimated_date')->nullable(); // Data estimada
            $table->timestamps();
            
            $table->foreign('proposal_id', 'id', 'proposals', 'CASCADE');
            $table->index('proposal_id');
            $table->index('order');
        });
    }

    public function down(): void
    {
        $this->dropTable('proposal_roadmap_steps');
    }
};

