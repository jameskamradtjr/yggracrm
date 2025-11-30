<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('contract_signatures', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->enum('tipo_assinante', ['contratante', 'contratado']); // Quem está assinando
            $table->string('nome_assinante');
            $table->string('cpf_cnpj')->nullable();
            $table->string('email');
            $table->string('telefone')->nullable();
            $table->string('codigo_verificacao', 6)->nullable(); // Código enviado por email
            $table->dateTime('codigo_enviado_em')->nullable();
            $table->dateTime('codigo_validado_em')->nullable();
            $table->boolean('assinado')->default(false);
            $table->dateTime('assinado_em')->nullable();
            $table->string('ip_assinatura')->nullable();
            $table->text('geolocalizacao')->nullable(); // JSON com dados de geolocalização
            $table->text('dispositivo')->nullable(); // Informações do dispositivo
            $table->text('hash_assinatura')->nullable(); // Hash para validação jurídica
            $table->text('certificado_digital')->nullable(); // Certificado digital (se aplicável)
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('contract_id');
            $table->index('tipo_assinante');
            $table->index('codigo_verificacao');
            $table->index('email');
            $table->index('assinado');
            
            $table->foreign('contract_id', 'id', 'contracts', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('contract_signatures');
    }
};

