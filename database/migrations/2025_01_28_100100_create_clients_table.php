<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('clients', function (Schema $table) {
            $table->id();
            $table->enum('tipo', ['fisica', 'juridica'])->default('fisica');
            $table->string('nome_razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('cpf_cnpj', 18)->nullable();
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('instagram')->nullable();
            $table->text('endereco')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();
            $table->string('cep', 10)->nullable();
            $table->text('observacoes')->nullable();
            $table->integer('score')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->index('email');
            $table->index('cpf_cnpj');
            $table->index('user_id');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        $this->dropTable('clients');
    }
};

