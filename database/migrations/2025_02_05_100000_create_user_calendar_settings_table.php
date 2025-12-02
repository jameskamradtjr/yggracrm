<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        
        // Verifica se a tabela já existe
        $tableExists = false;
        try {
            $result = $db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'user_calendar_settings'");
            $tableExists = !empty($result) && $result[0]['count'] > 0;
        } catch (\Exception $e) {
            $tableExists = false;
        }
        
        if (!$tableExists) {
            $this->createTable('user_calendar_settings', function (Schema $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->boolean('public_calendar_enabled')->default(false);
                $table->string('calendar_slug')->nullable()->unique();
                $table->string('calendar_title')->nullable();
                $table->text('calendar_description')->nullable();
                $table->integer('appointment_duration')->default(30); // minutos
                $table->integer('buffer_time_before')->default(0); // minutos antes
                $table->integer('buffer_time_after')->default(0); // minutos depois
                $table->integer('advance_booking_days')->default(30); // dias de antecedência
                $table->integer('same_day_booking_hours')->default(2); // horas mínimas para agendamento no mesmo dia
                $table->json('timezone')->nullable();
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('calendar_slug');
            });
            
            // Adiciona foreign key se a tabela users existir
            try {
                $db->execute("ALTER TABLE user_calendar_settings ADD CONSTRAINT fk_user_calendar_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
            } catch (\Exception $e) {
                // Ignora se já existir ou se houver erro
            }
        }
    }

    public function down(): void
    {
        $this->dropTable('user_calendar_settings');
    }
};

