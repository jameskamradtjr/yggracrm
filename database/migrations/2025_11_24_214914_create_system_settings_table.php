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
        $this->createTable('system_settings', function (Schema $table) {
            $table->id();
            $table->string('key')->unique()->comment('Chave da configuração');
            $table->text('value')->nullable()->comment('Valor da configuração');
            $table->string('type')->default('text')->comment('Tipo: text, image, json');
            $table->string('group')->default('general')->comment('Grupo: layout, email, etc');
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('key');
            $table->index('group');
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropTable('system_settings');
    }
};
