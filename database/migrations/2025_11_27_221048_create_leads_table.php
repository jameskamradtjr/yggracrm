<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    /**
     * Executa a migration
     */
    public function up(): void
    {
        $this->createTable('leads', function (Schema $table) {
            $table->id();
            $table->string('nome');
            $table->string('email');
            $table->string('telefone', 20);
            $table->string('instagram')->nullable();
            $table->string('ramo')->nullable();
            $table->string('faturamento_raw')->nullable();
            $table->string('faturamento_categoria')->nullable();
            $table->string('invest_raw')->nullable();
            $table->string('invest_categoria')->nullable();
            $table->text('objetivo')->nullable();
            $table->boolean('faz_trafego')->default(false);
            $table->json('tags_ai')->nullable();
            $table->integer('score_potencial')->default(0);
            $table->enum('urgencia', ['baixa', 'media', 'alta'])->default('baixa');
            $table->text('resumo')->nullable();
            $table->enum('status_kanban', ['cold', 'morno', 'quente', 'ultra_quente'])->default('cold');
            $table->timestamps();
            
            // Índices
            $table->index('email');
            $table->index('status_kanban');
            $table->index('score_potencial');
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropTable('leads');
    }
};
