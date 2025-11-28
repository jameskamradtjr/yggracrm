<?php

use Core\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        
        // Adiciona campo valor_oportunidade na tabela leads
        $db->execute("ALTER TABLE `leads` ADD COLUMN `valor_oportunidade` DECIMAL(15, 2) NULL DEFAULT NULL AFTER `score_potencial`");
        
        // Índice para facilitar consultas
        $this->addIndex('leads', 'valor_oportunidade', 'leads_valor_oportunidade_index');
    }

    public function down(): void
    {
        $this->dropIndex('leads', 'leads_valor_oportunidade_index');
        $this->dropColumn('leads', 'valor_oportunidade');
    }
};

