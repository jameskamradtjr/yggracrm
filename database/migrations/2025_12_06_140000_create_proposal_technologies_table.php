<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('proposal_technologies', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_id');
            $table->string('technology', 50); // php, mysql, figma, aws, python, n8n, hostinger, vps
            $table->timestamps();
            
            $table->foreign('proposal_id', 'id', 'proposals', 'CASCADE');
            $table->index('proposal_id');
            $table->index('technology');
            $table->unique(['proposal_id', 'technology']);
        });
    }

    public function down(): void
    {
        $this->dropTable('proposal_technologies');
    }
};

