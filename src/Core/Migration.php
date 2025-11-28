<?php

declare(strict_types=1);

namespace Core;

/**
 * Classe Migration Base
 * 
 * Todas as migrations devem estender esta classe
 * Implementa métodos up() e down() para aplicar e reverter mudanças
 */
abstract class Migration
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Aplica a migration
     */
    abstract public function up(): void;

    /**
     * Reverte a migration
     */
    abstract public function down(): void;

    /**
     * Cria uma tabela
     */
    protected function createTable(string $table, callable $callback): void
    {
        $schema = new Schema($table);
        $callback($schema);
        
        $sql = $schema->toSql();
        $this->db->execute($sql);
    }

    /**
     * Remove uma tabela
     */
    protected function dropTable(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`";
        $this->db->execute($sql);
    }

    /**
     * Adiciona uma coluna
     */
    protected function addColumn(string $table, string $column, string $type, array $options = []): void
    {
        $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$type}";
        
        if (isset($options['nullable']) && !$options['nullable']) {
            $sql .= " NOT NULL";
        }
        
        if (isset($options['default'])) {
            $sql .= " DEFAULT '{$options['default']}'";
        }
        
        $this->db->execute($sql);
    }

    /**
     * Remove uma coluna
     */
    protected function dropColumn(string $table, string $column): void
    {
        $sql = "ALTER TABLE `{$table}` DROP COLUMN `{$column}`";
        $this->db->execute($sql);
    }

    /**
     * Adiciona um índice
     */
    protected function addIndex(string $table, string|array $columns, string $name = null): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?? $table . '_' . implode('_', $columns) . '_index';
        
        $sql = "CREATE INDEX `{$name}` ON `{$table}` (`" . implode('`, `', $columns) . "`)";
        $this->db->execute($sql);
    }

    /**
     * Remove um índice
     */
    protected function dropIndex(string $table, string $name): void
    {
        $sql = "DROP INDEX `{$name}` ON `{$table}`";
        $this->db->execute($sql);
    }

    /**
     * Executa SQL raw
     */
    protected function execute(string $sql): void
    {
        $this->db->execute($sql);
    }
}

