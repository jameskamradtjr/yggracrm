<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('automation_delays', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('automation_id');
            $table->unsignedBigInteger('execution_id');
            $table->string('node_id')->comment('ID do nó de delay no workflow');
            $table->json('trigger_data')->nullable()->comment('Dados do trigger para continuar o workflow');
            $table->timestamp('execute_at')->comment('Data/hora em que o delay deve ser processado');
            $table->string('status')->default('pending')->comment('pending, processed, cancelled');
            $table->timestamps();
            
            $table->index('automation_id');
            $table->index('execution_id');
            $table->index('status');
            $table->index('execute_at');
            $table->foreign('automation_id', 'id', 'automations', 'CASCADE');
            $table->foreign('execution_id', 'id', 'automation_executions', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('automation_delays');
    }
};

