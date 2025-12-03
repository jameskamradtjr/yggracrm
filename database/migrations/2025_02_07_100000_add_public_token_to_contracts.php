<?php

use Core\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        
        // Adiciona token_publico se não existir
        try {
            $db->execute("ALTER TABLE `contracts` ADD COLUMN `token_publico` VARCHAR(100) NULL AFTER `updated_at`");
        } catch (\Exception $e) {
            // Coluna já existe
        }
        
        // Adiciona data_visualizacao_cliente se não existir
        try {
            $db->execute("ALTER TABLE `contracts` ADD COLUMN `data_visualizacao_cliente` DATETIME NULL AFTER `token_publico`");
        } catch (\Exception $e) {
            // Coluna já existe
        }
        
        // Adiciona índice único para token_publico se não existir
        try {
            $db->execute("ALTER TABLE `contracts` ADD UNIQUE INDEX `contracts_token_publico_unique` (`token_publico`)");
        } catch (\Exception $e) {
            // Índice já existe
        }
        
        // Gera tokens para contratos existentes que não têm
        $contracts = $db->query("SELECT id FROM contracts WHERE token_publico IS NULL OR token_publico = ''");
        foreach ($contracts as $contract) {
            $token = bin2hex(random_bytes(32));
            $db->execute("UPDATE contracts SET token_publico = ? WHERE id = ?", [$token, $contract['id']]);
        }
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        
        // Remove índice
        try {
            $db->execute("ALTER TABLE `contracts` DROP INDEX `contracts_token_publico_unique`");
        } catch (\Exception $e) {
            // Índice não existe
        }
        
        // Remove colunas
        try {
            $db->execute("ALTER TABLE `contracts` DROP COLUMN `data_visualizacao_cliente`");
        } catch (\Exception $e) {
            // Coluna não existe
        }
        
        try {
            $db->execute("ALTER TABLE `contracts` DROP COLUMN `token_publico`");
        } catch (\Exception $e) {
            // Coluna não existe
        }
    }
};

