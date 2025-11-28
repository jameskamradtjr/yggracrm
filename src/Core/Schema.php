<?php

declare(strict_types=1);

namespace Core;

/**
 * Classe Schema - Construtor de schemas de tabelas
 * 
 * Fornece uma API fluente para definir estrutura de tabelas
 */
class Schema
{
    private string $table;
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];
    private ?string $primaryKey = null;
    private string $engine = 'InnoDB';
    private string $charset = 'utf8mb4';
    private string $collation = 'utf8mb4_unicode_ci';

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Define coluna ID auto-increment
     */
    public function id(string $name = 'id'): self
    {
        $this->columns[] = "`{$name}` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
        $this->primaryKey = $name;
        return $this;
    }

    /**
     * Define coluna UUID como primary key
     */
    public function uuid(string $name = 'id'): self
    {
        $this->columns[] = "`{$name}` CHAR(36) PRIMARY KEY";
        $this->primaryKey = $name;
        return $this;
    }

    /**
     * Coluna string/varchar
     */
    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, "VARCHAR({$length})");
    }

    /**
     * Coluna text
     */
    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "TEXT");
    }

    /**
     * Coluna longText
     */
    public function longText(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "LONGTEXT");
    }

    /**
     * Coluna integer
     */
    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "INT");
    }

    /**
     * Coluna bigInteger
     */
    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "BIGINT");
    }

    /**
     * Coluna unsignedBigInteger (para foreign keys)
     */
    public function unsignedBigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "BIGINT UNSIGNED");
    }

    /**
     * Coluna decimal
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, "DECIMAL({$precision},{$scale})");
    }

    /**
     * Coluna float
     */
    public function float(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "FLOAT");
    }

    /**
     * Coluna double
     */
    public function double(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "DOUBLE");
    }

    /**
     * Coluna boolean
     */
    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "TINYINT(1)");
    }

    /**
     * Coluna date
     */
    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "DATE");
    }

    /**
     * Coluna datetime
     */
    public function datetime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "DATETIME");
    }

    /**
     * Coluna timestamp
     */
    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "TIMESTAMP");
    }

    /**
     * Coluna enum
     */
    public function enum(string $name, array $values): ColumnDefinition
    {
        $valuesList = "'" . implode("','", $values) . "'";
        return $this->addColumn($name, "ENUM({$valuesList})");
    }

    /**
     * Coluna json
     */
    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn($name, "JSON");
    }

    /**
     * Timestamps automáticos (created_at, updated_at)
     */
    public function timestamps(): self
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
        return $this;
    }

    /**
     * Soft deletes (deleted_at)
     */
    public function softDeletes(): self
    {
        $this->timestamp('deleted_at')->nullable();
        return $this;
    }

    /**
     * Coluna user_id para multi-tenancy
     */
    public function userIdColumn(): self
    {
        $this->unsignedBigInteger('user_id');
        $this->indexes[] = "INDEX `idx_user_id` (`user_id`)";
        return $this;
    }

    /**
     * Adiciona uma coluna
     */
    private function addColumn(string $name, string $type): ColumnDefinition
    {
        $column = new ColumnDefinition($name, $type, $this);
        return $column;
    }

    /**
     * Adiciona definição de coluna ao array
     */
    public function pushColumn(string $definition): void
    {
        $this->columns[] = $definition;
    }

    /**
     * Adiciona índice único
     */
    public function unique(string|array $columns, string $name = null): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?? 'unique_' . implode('_', $columns);
        
        $this->indexes[] = "UNIQUE KEY `{$name}` (`" . implode('`, `', $columns) . "`)";
        return $this;
    }

    /**
     * Adiciona índice
     */
    public function index(string|array $columns, string $name = null): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?? 'idx_' . implode('_', $columns);
        
        $this->indexes[] = "INDEX `{$name}` (`" . implode('`, `', $columns) . "`)";
        return $this;
    }

    /**
     * Adiciona foreign key
     */
    public function foreign(string $column, string $references, string $on, string $onDelete = 'CASCADE'): self
    {
        $name = "fk_{$this->table}_{$column}";
        $this->foreignKeys[] = "CONSTRAINT `{$name}` FOREIGN KEY (`{$column}`) REFERENCES `{$on}`(`{$references}`) ON DELETE {$onDelete}";
        return $this;
    }

    /**
     * Converte schema para SQL
     */
    public function toSql(): string
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (\n";
        
        // Adiciona colunas
        $sql .= "  " . implode(",\n  ", $this->columns);
        
        // Adiciona índices
        if (!empty($this->indexes)) {
            $sql .= ",\n  " . implode(",\n  ", $this->indexes);
        }
        
        // Adiciona foreign keys
        if (!empty($this->foreignKeys)) {
            $sql .= ",\n  " . implode(",\n  ", $this->foreignKeys);
        }
        
        $sql .= "\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation};";
        
        return $sql;
    }
}

/**
 * Classe ColumnDefinition - Definição fluente de colunas
 */
class ColumnDefinition
{
    private string $name;
    private string $type;
    private Schema $schema;
    private array $modifiers = [];

    public function __construct(string $name, string $type, Schema $schema)
    {
        $this->name = $name;
        $this->type = $type;
        $this->schema = $schema;
    }

    /**
     * Permite NULL
     */
    public function nullable(): self
    {
        $this->modifiers['nullable'] = true;
        return $this;
    }

    /**
     * Valor padrão
     */
    public function default(mixed $value): self
    {
        $this->modifiers['default'] = $value;
        return $this;
    }

    /**
     * Unsigned (para números)
     */
    public function unsigned(): self
    {
        $this->type .= ' UNSIGNED';
        return $this;
    }

    /**
     * Auto increment
     */
    public function autoIncrement(): self
    {
        $this->modifiers['autoIncrement'] = true;
        return $this;
    }

    /**
     * Unique
     */
    public function unique(): self
    {
        $this->modifiers['unique'] = true;
        return $this;
    }

    /**
     * Comment
     */
    public function comment(string $comment): self
    {
        $this->modifiers['comment'] = $comment;
        return $this;
    }

    /**
     * After (posição da coluna)
     */
    public function after(string $column): self
    {
        $this->modifiers['after'] = $column;
        return $this;
    }

    /**
     * Finaliza a definição da coluna
     */
    public function __destruct()
    {
        $definition = "`{$this->name}` {$this->type}";

        // Adiciona NOT NULL ou NULL
        if (!isset($this->modifiers['nullable']) || !$this->modifiers['nullable']) {
            $definition .= " NOT NULL";
        } else {
            $definition .= " NULL";
        }

        // Adiciona DEFAULT
        if (isset($this->modifiers['default'])) {
            $default = $this->modifiers['default'];
            if (is_string($default)) {
                $definition .= " DEFAULT '{$default}'";
            } elseif (is_bool($default)) {
                $definition .= " DEFAULT " . ($default ? '1' : '0');
            } elseif (is_null($default)) {
                $definition .= " DEFAULT NULL";
            } else {
                $definition .= " DEFAULT {$default}";
            }
        }

        // Adiciona AUTO_INCREMENT
        if (isset($this->modifiers['autoIncrement']) && $this->modifiers['autoIncrement']) {
            $definition .= " AUTO_INCREMENT";
        }

        // Adiciona UNIQUE
        if (isset($this->modifiers['unique']) && $this->modifiers['unique']) {
            $definition .= " UNIQUE";
        }

        // Adiciona COMMENT
        if (isset($this->modifiers['comment'])) {
            $definition .= " COMMENT '{$this->modifiers['comment']}'";
        }

        $this->schema->pushColumn($definition);
    }
}

