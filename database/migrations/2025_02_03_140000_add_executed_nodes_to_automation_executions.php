<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->db->execute("ALTER TABLE automation_executions ADD COLUMN executed_nodes TEXT NULL COMMENT 'JSON array de IDs de nós já executados' AFTER execution_log");
    }

    public function down(): void
    {
        $this->db->execute("ALTER TABLE automation_executions DROP COLUMN executed_nodes");
    }
};

