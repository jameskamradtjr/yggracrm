<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verifica se a tabela já existe
        $db = \Core\Database::getInstance();
        $tables = $db->query("SHOW TABLES LIKE 'taggables'");
        
        if (!empty($tables)) {
            return; // Tabela já existe
        }
        
        $this->createTable('taggables', function (Schema $table) {
            $table->id();
            $table->string('taggable_type'); // Ex: 'DriveFile', 'Lead', 'Client'
            $table->unsignedBigInteger('taggable_id'); // ID do registro
            $table->unsignedBigInteger('tag_id'); // ID da tag
            $table->timestamps();
            
            $table->index(['taggable_type', 'taggable_id']);
            $table->index('tag_id');
            $table->index(['taggable_type', 'taggable_id', 'tag_id'], 'taggables_unique');
        });
    }

    public function down(): void
    {
        $this->dropTable('taggables');
    }
};

