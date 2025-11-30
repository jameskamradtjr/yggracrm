<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('whatsapp_templates', function (Schema $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('message'); // Mensagem do WhatsApp
            $table->text('variables')->nullable(); // Variáveis disponíveis (JSON)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        $this->dropTable('whatsapp_templates');
    }
};

