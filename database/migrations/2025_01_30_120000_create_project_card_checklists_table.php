<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('project_card_checklists', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->string('item');
            $table->boolean('concluido')->default(false);
            $table->integer('ordem')->default(0);
            $table->timestamps();
            
            $table->index('card_id');
            $table->foreign('card_id', 'id', 'project_cards', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('project_card_checklists');
    }
};

