<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        
        // Adiciona novos campos à tabela leads
        $db->execute("ALTER TABLE `leads` ADD COLUMN `etapa_funil` ENUM('interessados', 'negociacao_proposta', 'fechamento') DEFAULT 'interessados' AFTER `status_kanban`");
        $db->execute("ALTER TABLE `leads` ADD COLUMN `origem` VARCHAR(255) NULL AFTER `etapa_funil`");
        $db->execute("ALTER TABLE `leads` ADD COLUMN `responsible_user_id` BIGINT UNSIGNED NULL AFTER `origem`");
        $db->execute("ALTER TABLE `leads` ADD COLUMN `client_id` BIGINT UNSIGNED NULL AFTER `responsible_user_id`");
        
        // Índices
        $this->addIndex('leads', 'etapa_funil', 'leads_etapa_funil_index');
        $this->addIndex('leads', 'responsible_user_id', 'leads_responsible_user_id_index');
        $this->addIndex('leads', 'client_id', 'leads_client_id_index');
    }

    public function down(): void
    {
        $this->dropIndex('leads', 'leads_client_id_index');
        $this->dropIndex('leads', 'leads_responsible_user_id_index');
        $this->dropIndex('leads', 'leads_etapa_funil_index');
        
        $this->dropColumn('leads', 'client_id');
        $this->dropColumn('leads', 'responsible_user_id');
        $this->dropColumn('leads', 'origem');
        $this->dropColumn('leads', 'etapa_funil');
    }
};

