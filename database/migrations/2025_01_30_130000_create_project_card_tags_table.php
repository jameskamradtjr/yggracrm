<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('project_card_tags', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->string('nome');
            $table->string('cor', 7)->default('#0dcaf0'); // Cor em hex
            $table->timestamps();
            
            $table->index('card_id');
            $table->foreign('card_id', 'id', 'project_cards', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('project_card_tags');
    }
};

