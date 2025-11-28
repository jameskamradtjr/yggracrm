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
        $this->createTable('email_templates', function (Schema $table) {
            $table->id();
            $table->string('name')->comment('Nome do template');
            $table->string('slug')->unique()->comment('Identificador único');
            $table->string('subject')->comment('Assunto do email');
            $table->text('body')->comment('Corpo do email (HTML)');
            $table->text('variables')->nullable()->comment('Variáveis disponíveis (JSON)');
            $table->boolean('is_active')->default(1);
            $table->timestamps();
            
            // Índices
            $table->index('slug');
            $table->index('is_active');
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropTable('email_templates');
    }
};
