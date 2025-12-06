<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('proposal_testimonials', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_id');
            $table->string('client_name', 255);
            $table->string('company', 255)->nullable();
            $table->text('testimonial'); // Depoimento
            $table->string('photo_url', 500)->nullable(); // URL da foto do cliente
            $table->integer('order')->default(0); // Ordem de exibição
            $table->timestamps();
            
            $table->foreign('proposal_id', 'id', 'proposals', 'CASCADE');
            $table->index('proposal_id');
            $table->index('order');
        });
    }

    public function down(): void
    {
        $this->dropTable('proposal_testimonials');
    }
};

