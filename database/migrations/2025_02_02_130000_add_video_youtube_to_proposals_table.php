<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        
        // Adiciona campo video_youtube se não existir
        $columns = $db->query("SHOW COLUMNS FROM `proposals` LIKE 'video_youtube'");
        if (empty($columns)) {
            $db->execute("ALTER TABLE `proposals` ADD COLUMN `video_youtube` VARCHAR(500) NULL AFTER `imagem_capa`");
        }
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `video_youtube`");
    }
};

