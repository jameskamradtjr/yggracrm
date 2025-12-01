<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('automation_executions', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('automation_id');
            $table->string('status', 50)->default('running'); // running, completed, failed
            $table->text('trigger_data')->nullable(); // Dados do trigger que iniciou a execução
            $table->text('execution_log')->nullable(); // Log de execução
            $table->text('executed_nodes')->nullable()->comment('JSON array de IDs de nós já executados');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('automation_id');
            $table->index('status');
            $table->index('started_at');
            $table->foreign('automation_id', 'id', 'automations', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('automation_executions');
    }
};

