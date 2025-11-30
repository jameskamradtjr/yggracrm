<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('proposal_conditions', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_id');
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->integer('ordem')->default(0);
            $table->timestamps();
            
            $table->index('proposal_id');
            $table->index('ordem');
            
            $table->foreign('proposal_id', 'id', 'proposals', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('proposal_conditions');
    }
};

