<?php

use Core\Migration;
use Core\Database;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = Database::getInstance();
        
        // Cria tabela de tags de leads se não existir
        $tableExists = false;
        try {
            $result = $db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'lead_tags'");
            $tableExists = !empty($result) && $result[0]['count'] > 0;
        } catch (\Exception $e) {
            $tableExists = false;
        }
        
        if (!$tableExists) {
            $this->createTable('lead_tags', function (Schema $table) {
                $table->id();
                $table->bigInteger('lead_id')->unsigned();
                $table->bigInteger('tag_id')->unsigned();
                $table->timestamps();
                
                $table->index('lead_id');
                $table->index('tag_id');
            });
        }
        
        // Cria tabela de tags de clientes se não existir
        $tableExists = false;
        try {
            $result = $db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'client_tags'");
            $tableExists = !empty($result) && $result[0]['count'] > 0;
        } catch (\Exception $e) {
            $tableExists = false;
        }
        
        if (!$tableExists) {
            $this->createTable('client_tags', function (Schema $table) {
                $table->id();
                $table->bigInteger('client_id')->unsigned();
                $table->bigInteger('tag_id')->unsigned();
                $table->timestamps();
                
                $table->index('client_id');
                $table->index('tag_id');
            });
        }
    }

    public function down(): void
    {
        $this->dropTable('client_tags');
        $this->dropTable('lead_tags');
    }
};

