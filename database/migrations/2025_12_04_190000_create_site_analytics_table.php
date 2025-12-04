<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('site_analytics', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_site_id');
            $table->unsignedBigInteger('site_post_id')->nullable(); // NULL = visualização do site, não de post específico
            $table->enum('event_type', ['pageview', 'click', 'impression']); // Tipo de evento
            $table->string('page_path')->nullable(); // Caminho da página (/site/slug ou /site/slug/post/slug)
            $table->string('referrer')->nullable(); // Origem do tráfego (referrer)
            $table->string('utm_source')->nullable(); // UTM source
            $table->string('utm_medium')->nullable(); // UTM medium
            $table->string('utm_campaign')->nullable(); // UTM campaign
            $table->string('utm_term')->nullable(); // UTM term
            $table->string('utm_content')->nullable(); // UTM content
            $table->string('ip_address')->nullable(); // IP do visitante
            $table->string('user_agent')->nullable(); // User agent
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('browser')->nullable(); // Nome do navegador
            $table->string('os')->nullable(); // Sistema operacional
            $table->string('country')->nullable(); // País (se disponível)
            $table->string('city')->nullable(); // Cidade (se disponível)
            $table->timestamps();
            
            $table->index('user_site_id');
            $table->index('site_post_id');
            $table->index('event_type');
            $table->index('created_at');
            $table->index(['user_site_id', 'event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        $this->dropTable('site_analytics');
    }
};

