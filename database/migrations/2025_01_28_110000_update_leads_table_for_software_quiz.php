<?php

return new class extends \Core\Migration {
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        
        // Adiciona novos campos para quiz de software
        $db->execute("ALTER TABLE `leads` ADD COLUMN `tem_software` TINYINT(1) DEFAULT 0 AFTER `faz_trafego`");
        $db->execute("ALTER TABLE `leads` ADD COLUMN `investimento_software` VARCHAR(50) NULL AFTER `tem_software`");
        $db->execute("ALTER TABLE `leads` ADD COLUMN `tipo_sistema` ENUM('interno', 'cliente', 'saas') NULL AFTER `investimento_software`");
        $db->execute("ALTER TABLE `leads` ADD COLUMN `plataforma_app` ENUM('ios_android', 'ios', 'android', 'nenhum') NULL AFTER `tipo_sistema`");
        $db->execute("ALTER TABLE `leads` ADD COLUMN `origem_conheceu` VARCHAR(255) NULL AFTER `origem`");
        
        // Índice para origem_conheceu para relatórios
        $this->addIndex('leads', 'origem_conheceu', 'leads_origem_conheceu_index');
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        
        $this->dropIndex('leads', 'leads_origem_conheceu_index');
        $db->execute("ALTER TABLE `leads` DROP COLUMN `origem_conheceu`");
        $db->execute("ALTER TABLE `leads` DROP COLUMN `plataforma_app`");
        $db->execute("ALTER TABLE `leads` DROP COLUMN `tipo_sistema`");
        $db->execute("ALTER TABLE `leads` DROP COLUMN `investimento_software`");
        $db->execute("ALTER TABLE `leads` DROP COLUMN `tem_software`");
    }
};

