<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumn('proposals', 'is_archived', 'TINYINT(1)', ['nullable' => false, 'default' => 0]);
        $this->addIndex('proposals', 'is_archived');
    }

    public function down(): void
    {
        $this->dropIndex('proposals', 'is_archived');
        $this->dropColumn('proposals', 'is_archived');
    }
};

