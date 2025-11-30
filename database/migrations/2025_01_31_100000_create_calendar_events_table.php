<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('calendar_events', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Multi-tenancy
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->dateTime('data_inicio');
            $table->dateTime('data_fim')->nullable();
            $table->enum('cor', ['danger', 'success', 'primary', 'warning'])->default('primary');
            $table->boolean('dia_inteiro')->default(false);
            $table->string('localizacao')->nullable();
            $table->text('observacoes')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('data_inicio');
            $table->index('data_fim');
            $table->index('client_id');
            $table->index('lead_id');
            $table->index('project_id');
            
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
            $table->foreign('client_id', 'id', 'clients', 'SET NULL');
            $table->foreign('lead_id', 'id', 'leads', 'SET NULL');
            $table->foreign('project_id', 'id', 'projects', 'SET NULL');
        });
    }

    public function down(): void
    {
        $this->dropTable('calendar_events');
    }
};

