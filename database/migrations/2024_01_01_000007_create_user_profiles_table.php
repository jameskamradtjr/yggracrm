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
        $this->createTable('user_profiles', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('company_name')->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('cpf', 14)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zipcode', 10)->nullable();
            $table->string('country', 50)->default('Brasil');
            $table->text('bio')->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamps();
            
            // Foreign key
            $table->foreign('user_id', 'id', 'users', 'CASCADE');
            
            // Índices
            $table->index('user_id');
            $table->index('cnpj');
            $table->index('cpf');
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        $this->dropTable('user_profiles');
    }
};

