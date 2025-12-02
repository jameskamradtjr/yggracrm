<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('drive_file_shares', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('file_id'); // Arquivo compartilhado
            $table->unsignedBigInteger('shared_with_user_id'); // Usuário com quem foi compartilhado
            $table->enum('permission', ['view', 'download', 'edit'])->default('view'); // Permissão
            $table->timestamp('expires_at')->nullable(); // Data de expiração do compartilhamento
            $table->timestamps();
            
            $table->index('file_id');
            $table->index('shared_with_user_id');
            $table->index(['file_id', 'shared_with_user_id']);
        });
    }

    public function down(): void
    {
        $this->dropTable('drive_file_shares');
    }
};

