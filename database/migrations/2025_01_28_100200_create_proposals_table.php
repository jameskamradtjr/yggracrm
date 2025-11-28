<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('proposals', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->decimal('valor', 15, 2)->default(0);
            $table->enum('status', ['rascunho', 'enviada', 'aprovada', 'rejeitada', 'cancelada'])->default('rascunho');
            $table->date('data_envio')->nullable();
            $table->date('data_validade')->nullable();
            $table->text('observacoes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->index('lead_id');
            $table->index('client_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        $this->dropTable('proposals');
    }
};

