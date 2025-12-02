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
            $result = $db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'public_appointments'");
            $tableExists = !empty($result) && $result[0]['count'] > 0;
        } catch (\Exception $e) {
            $tableExists = false;
        }
        
        if (!$tableExists) {
            $this->createTable('public_appointments', function (Schema $table) {
                $table->id();
                $table->unsignedBigInteger('user_id'); // Responsável/agendado
                $table->string('name'); // Nome do cliente que agendou
                $table->string('email');
                $table->string('phone')->nullable();
                $table->text('notes')->nullable(); // Observações do cliente
                $table->dateTime('appointment_date');
                $table->integer('duration')->default(30); // minutos
                $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
                $table->string('confirmation_token')->nullable()->unique();
                $table->dateTime('confirmed_at')->nullable();
                $table->dateTime('cancelled_at')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->unsignedBigInteger('client_id')->nullable(); // Se já for cliente
                $table->unsignedBigInteger('lead_id')->nullable(); // Se criar lead
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('appointment_date');
                $table->index('status');
                $table->index('confirmation_token');
                $table->index('client_id');
                $table->index('lead_id');
            });
            
            // Adiciona foreign keys se as tabelas existirem
            try {
                $db->execute("ALTER TABLE public_appointments ADD CONSTRAINT fk_public_appointments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
            } catch (\Exception $e) {
                // Ignora se já existir ou se houver erro
            }
            
            try {
                $db->execute("ALTER TABLE public_appointments ADD CONSTRAINT fk_public_appointments_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL");
            } catch (\Exception $e) {
                // Ignora se já existir ou se houver erro
            }
            
            try {
                $db->execute("ALTER TABLE public_appointments ADD CONSTRAINT fk_public_appointments_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL");
            } catch (\Exception $e) {
                // Ignora se já existir ou se houver erro
            }
        }
    }

    public function down(): void
    {
        $this->dropTable('public_appointments');
    }
};

