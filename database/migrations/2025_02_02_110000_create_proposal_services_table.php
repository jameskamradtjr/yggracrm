<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('proposal_services', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_id');
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->integer('quantidade')->default(1);
            $table->decimal('valor_unitario', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->integer('ordem')->default(0);
            $table->timestamps();
            
            $table->index('proposal_id');
            $table->index('ordem');
            
            $table->foreign('proposal_id', 'id', 'proposals', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('proposal_services');
    }
};

