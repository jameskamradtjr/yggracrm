<?php

use Core\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Altera featured_image de VARCHAR para TEXT para suportar URLs longas do S3
        $this->execute("ALTER TABLE `site_posts` MODIFY COLUMN `featured_image` TEXT NULL");
    }

    public function down(): void
    {
        // Reverte para VARCHAR(255)
        $this->execute("ALTER TABLE `site_posts` MODIFY COLUMN `featured_image` VARCHAR(255) NULL");
    }
};

