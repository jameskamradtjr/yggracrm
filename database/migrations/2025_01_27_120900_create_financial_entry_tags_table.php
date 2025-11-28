<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('financial_entry_tags', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('financial_entry_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();
            
            $table->index('financial_entry_id');
            $table->index('tag_id');
            $table->foreign('financial_entry_id', 'id', 'financial_entries', 'CASCADE');
            $table->foreign('tag_id', 'id', 'tags', 'CASCADE');
            
            // Evita duplicatas
            $table->unique(['financial_entry_id', 'tag_id'], 'entry_tag_unique');
        });
    }

    public function down(): void
    {
        $this->dropTable('financial_entry_tags');
    }
};

