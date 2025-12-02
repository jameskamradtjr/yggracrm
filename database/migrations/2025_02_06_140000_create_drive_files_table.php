<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('drive_files', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Dono do arquivo
            $table->unsignedBigInteger('folder_id')->nullable(); // Pasta (opcional)
            $table->string('name'); // Nome original do arquivo
            $table->string('s3_key', 500); // Caminho no S3
            $table->string('mime_type', 100); // Tipo MIME
            $table->bigInteger('size'); // Tamanho em bytes
            $table->string('extension', 20)->nullable(); // Extensão do arquivo
            
            // Relacionamentos opcionais
            $table->unsignedBigInteger('client_id')->nullable(); // Cliente relacionado
            $table->unsignedBigInteger('lead_id')->nullable(); // Lead relacionado
            $table->unsignedBigInteger('project_id')->nullable(); // Projeto relacionado
            $table->unsignedBigInteger('responsible_user_id')->nullable(); // Responsável
            
            // Metadados
            $table->text('description')->nullable(); // Descrição
            $table->date('expiration_date')->nullable(); // Data de vencimento
            $table->boolean('is_favorite')->default(false); // Favorito
            $table->boolean('is_shared')->default(false); // Compartilhado
            
            // Controle de versão
            $table->integer('version')->default(1); // Versão do arquivo
            $table->unsignedBigInteger('previous_version_id')->nullable(); // Versão anterior
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete para lixeira
            
            $table->index('user_id');
            $table->index('folder_id');
            $table->index('client_id');
            $table->index('lead_id');
            $table->index('project_id');
            $table->index('responsible_user_id');
            $table->index('expiration_date');
            $table->index('is_favorite');
            $table->index(['user_id', 'folder_id']);
        });
    }

    public function down(): void
    {
        $this->dropTable('drive_files');
    }
};

