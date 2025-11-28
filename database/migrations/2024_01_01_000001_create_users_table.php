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
        $this->createTable('users', function (Schema $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('email');
            $table->index('status');
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropTable('users');
    }
};

