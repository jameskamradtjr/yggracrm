<?php

declare(strict_types=1);

namespace Core;

use PDO;

/**
 * Classe Model Base - Active Record Pattern
 * 
 * Implementa o padrão Active Record para facilitar operações no banco de dados
 * Suporta multi-tenancy automático via user_id
 */
abstract class Model
{
    protected Database $db;
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected bool $timestamps = true;
    protected bool $softDeletes = false;
    
    // Multi-tenancy
    protected bool $multiTenant = true;
    protected string $tenantColumn = 'user_id';
    
    // Dados do model
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance();
        $this->fill($attributes);
    }

    /**
     * Preenche o model com dados
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Busca todos os registros
     */
    public static function all(): array
    {
        $instance = new static();
        
        $table = $instance->getTable();
        $escapedTable = strpos($table, '`') !== false ? $table : "`{$table}`";
        
        $sql = "SELECT * FROM {$escapedTable}";
        
        if ($instance->multiTenant && $instance->shouldFilterByTenant()) {
            $escapedTenantColumn = $instance->escapeColumn($instance->tenantColumn);
            $sql .= " WHERE {$escapedTenantColumn} = ?";
            $results = $instance->db->query($sql, [auth()->getDataUserId()]);
        } else {
            $results = $instance->db->query($sql);
        }

        return array_map(fn($row) => static::newInstance($row, true), $results);
    }

    /**
     * Busca um registro por ID
     */
    public static function find(int|string $id): ?static
    {
        $instance = new static();
        
        $table = $instance->getTable();
        $escapedTable = strpos($table, '`') !== false ? $table : "`{$table}`";
        $escapedPrimaryKey = $instance->escapeColumn($instance->primaryKey);
        
        $sql = "SELECT * FROM {$escapedTable} WHERE {$escapedPrimaryKey} = ?";
        
        if ($instance->multiTenant && $instance->shouldFilterByTenant()) {
            $escapedTenantColumn = $instance->escapeColumn($instance->tenantColumn);
            $sql .= " AND {$escapedTenantColumn} = ?";
            $result = $instance->db->queryOne($sql, [$id, auth()->getDataUserId()]);
        } else {
            $result = $instance->db->queryOne($sql, [$id]);
        }

        return $result ? static::newInstance($result, true) : null;
    }

    /**
     * Busca registros com condições
     */
    public static function where(string $column, mixed $value, string $operator = '='): QueryBuilder
    {
        $instance = new static();
        $builder = new QueryBuilder($instance);
        
        return $builder->where($column, $value, $operator);
    }

    /**
     * Cria um novo registro
     */
    public static function create(array $attributes): static
    {
        $instance = new static($attributes);
        $instance->save();
        
        return $instance;
    }

    /**
     * Salva o model no banco de dados
     */
    public function save(): bool
    {
        // Adiciona tenant_id se necessário
        if ($this->multiTenant && $this->shouldFilterByTenant() && !isset($this->attributes[$this->tenantColumn])) {
            $this->attributes[$this->tenantColumn] = auth()->getDataUserId();
        }

        // Adiciona timestamps
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            
            if (!$this->exists) {
                $this->attributes['created_at'] = $now;
            }
            
            $this->attributes['updated_at'] = $now;
        }

        if ($this->exists) {
            return $this->performUpdate();
        }

        return $this->performInsert();
    }

    /**
     * Escapa nome de coluna com backticks
     */
    private function escapeColumn(string $column): string
    {
        // Se já tem backticks, retorna como está
        if (strpos($column, '`') !== false) {
            return $column;
        }
        
        // Escapa com backticks
        return "`{$column}`";
    }

    /**
     * Realiza INSERT no banco
     */
    private function performInsert(): bool
    {
        $columns = array_keys($this->attributes);
        $escapedColumns = array_map([$this, 'escapeColumn'], $columns);
        $placeholders = array_fill(0, count($columns), '?');
        
        $table = $this->getTable();
        $escapedTable = strpos($table, '`') !== false ? $table : "`{$table}`";

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $escapedTable,
            implode(', ', $escapedColumns),
            implode(', ', $placeholders)
        );

        $this->db->execute($sql, array_values($this->attributes));
        
        $this->attributes[$this->primaryKey] = (int) $this->db->lastInsertId();
        $this->exists = true;
        $this->original = $this->attributes;

        return true;
    }

    /**
     * Realiza UPDATE no banco
     */
    private function performUpdate(): bool
    {
        $columns = array_keys($this->attributes);
        $escapedColumns = array_map([$this, 'escapeColumn'], $columns);
        $setClause = implode(' = ?, ', $escapedColumns) . ' = ?';
        
        $table = $this->getTable();
        $escapedTable = strpos($table, '`') !== false ? $table : "`{$table}`";
        $escapedPrimaryKey = $this->escapeColumn($this->primaryKey);

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            $escapedTable,
            $setClause,
            $escapedPrimaryKey
        );

        $values = array_values($this->attributes);
        $values[] = $this->attributes[$this->primaryKey];

        $this->db->execute($sql, $values);
        $this->original = $this->attributes;

        return true;
    }

    /**
     * Deleta o registro
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        if ($this->softDeletes) {
            $this->attributes['deleted_at'] = date('Y-m-d H:i:s');
            return $this->save();
        }

        $table = $this->getTable();
        $escapedTable = strpos($table, '`') !== false ? $table : "`{$table}`";
        $escapedPrimaryKey = $this->escapeColumn($this->primaryKey);
        
        $sql = "DELETE FROM {$escapedTable} WHERE {$escapedPrimaryKey} = ?";
        $this->db->execute($sql, [$this->attributes[$this->primaryKey]]);

        return true;
    }

    /**
     * Atualiza o registro
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    /**
     * Verifica se deve filtrar por tenant
     */
    private function shouldFilterByTenant(): bool
    {
        return config('app.multi_tenant_enabled', true) && auth()->check();
    }

    /**
     * Cria nova instância do model
     */
    public static function newInstance(array $attributes, bool $exists = false): static
    {
        $instance = new static();
        $instance->attributes = $attributes;
        $instance->original = $attributes;
        $instance->exists = $exists;

        return $instance;
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        $array = $this->attributes;

        // Remove campos hidden
        foreach ($this->hidden as $field) {
            unset($array[$field]);
        }

        return $array;
    }

    /**
     * Converte para JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Retorna o nome da tabela
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Magic getter
     */
    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Magic setter
     */
    public function __set(string $name, mixed $value): void
    {
        if (in_array($name, $this->fillable)) {
            $this->attributes[$name] = $value;
        }
    }

    /**
     * Magic isset
     */
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }
}

