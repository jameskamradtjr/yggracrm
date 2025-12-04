<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('newsletter_subscribers', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_site_id');
            $table->string('email');
            $table->string('name')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            
            $table->index('user_site_id');
            $table->index('email');
            $table->unique(['user_site_id', 'email']); // Um email por site
        });
    }

    public function down(): void
    {
        $this->dropTable('newsletter_subscribers');
    }
};

