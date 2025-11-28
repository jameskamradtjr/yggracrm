<?php

return new class extends \Core\Migration {
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        
        // Adiciona campos de endereço que faltam
        $db->execute("ALTER TABLE `clients` ADD COLUMN `numero` VARCHAR(10) NULL AFTER `endereco`");
        $db->execute("ALTER TABLE `clients` ADD COLUMN `complemento` VARCHAR(255) NULL AFTER `numero`");
        $db->execute("ALTER TABLE `clients` ADD COLUMN `bairro` VARCHAR(255) NULL AFTER `complemento`");
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        $db->execute("ALTER TABLE `clients` DROP COLUMN `bairro`");
        $db->execute("ALTER TABLE `clients` DROP COLUMN `complemento`");
        $db->execute("ALTER TABLE `clients` DROP COLUMN `numero`");
    }
};

