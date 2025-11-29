<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('projects', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Multi-tenancy
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->enum('status', ['planejamento', 'em_andamento', 'pausado', 'concluido', 'cancelado'])->default('planejamento');
            $table->enum('prioridade', ['baixa', 'media', 'alta', 'urgente'])->default('media');
            $table->date('data_inicio')->nullable();
            $table->date('data_termino_prevista')->nullable();
            $table->date('data_termino_real')->nullable();
            $table->unsignedBigInteger('client_id')->nullable(); // Cliente associado
            $table->unsignedBigInteger('lead_id')->nullable(); // Lead associado (opcional)
            $table->unsignedBigInteger('responsible_user_id')->nullable(); // Responsável pelo projeto
            $table->decimal('orcamento', 15, 2)->nullable();
            $table->decimal('custo_real', 15, 2)->nullable();
            $table->integer('progresso')->default(0); // 0-100%
            $table->text('observacoes')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('client_id');
            $table->index('lead_id');
            $table->index('responsible_user_id');
            $table->index('status');
            $table->index('prioridade');
        });
    }

    public function down(): void
    {
        $this->dropTable('projects');
    }
};

