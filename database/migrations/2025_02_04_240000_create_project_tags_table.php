<?php

use Core\Migration;
use Core\Database;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = Database::getInstance();
        
        // Cria tabela de tags de projetos se não existir
        $tableExists = false;
        try {
            $result = $db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'project_tags'");
            $tableExists = !empty($result) && $result[0]['count'] > 0;
        } catch (\Exception $e) {
            $tableExists = false;
        }
        
        if (!$tableExists) {
            $this->createTable('project_tags', function (Schema $table) {
                $table->id();
                $table->bigInteger('project_id')->unsigned();
                $table->bigInteger('tag_id')->unsigned();
                $table->timestamps();
                
                $table->index('project_id');
                $table->index('tag_id');
            });
            
            // Adiciona foreign keys se as tabelas existirem
            try {
                $db->execute("ALTER TABLE project_tags ADD CONSTRAINT fk_project_tags_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE");
            } catch (\Exception $e) {
                // Ignora se já existir ou se a tabela não existir
            }
            
            try {
                $db->execute("ALTER TABLE project_tags ADD CONSTRAINT fk_project_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE");
            } catch (\Exception $e) {
                // Ignora se já existir ou se a tabela não existir
            }
        }
    }

    public function down(): void
    {
        $this->dropTable('project_tags');
    }
};

