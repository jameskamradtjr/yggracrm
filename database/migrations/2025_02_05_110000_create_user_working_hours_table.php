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
            $result = $db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'user_working_hours'");
            $tableExists = !empty($result) && $result[0]['count'] > 0;
        } catch (\Exception $e) {
            $tableExists = false;
        }
        
        if (!$tableExists) {
            $this->createTable('user_working_hours', function (Schema $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
                $table->time('start_time_morning')->nullable(); // Ex: 08:00
                $table->time('end_time_morning')->nullable(); // Ex: 12:00
                $table->time('start_time_afternoon')->nullable(); // Ex: 13:00
                $table->time('end_time_afternoon')->nullable(); // Ex: 18:00
                $table->boolean('is_available')->default(true);
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('day_of_week');
                $table->unique(['user_id', 'day_of_week']);
            });
            
            // Adiciona foreign key se a tabela users existir
            try {
                $db->execute("ALTER TABLE user_working_hours ADD CONSTRAINT fk_user_working_hours_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
            } catch (\Exception $e) {
                // Ignora se já existir ou se houver erro
            }
        }
    }

    public function down(): void
    {
        $this->dropTable('user_working_hours');
    }
};

