<?php

declare(strict_types=1);

namespace Core;

/**
 * Classe QueryBuilder - Construtor de queries SQL
 * 
 * Implementa um query builder fluente similar ao Eloquent
 */
class QueryBuilder
{
    private object $model;
    private Database $db;
    private array $wheres = [];
    private array $bindings = [];
    private ?string $orderBy = null;
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(object $model)
    {
        $this->model = $model;
        $this->db = Database::getInstance();
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
     * Adiciona condição WHERE
     */
    public function where(string $column, mixed $value, string $operator = '='): self
    {
        $escapedColumn = $this->escapeColumn($column);
        $this->wheres[] = "{$escapedColumn} {$operator} ?";
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Adiciona condição WHERE com OR
     */
    public function orWhere(string $column, mixed $value, string $operator = '='): self
    {
        $escapedColumn = $this->escapeColumn($column);
        $conjunction = empty($this->wheres) ? '' : ' OR ';
        $this->wheres[] = $conjunction . "{$escapedColumn} {$operator} ?";
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Adiciona condição WHERE IN
     */
    public function whereIn(string $column, array $values): self
    {
        $escapedColumn = $this->escapeColumn($column);
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = "{$escapedColumn} IN ({$placeholders})";
        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Adiciona condição WHERE NULL
     */
    public function whereNull(string $column): self
    {
        $escapedColumn = $this->escapeColumn($column);
        $this->wheres[] = "{$escapedColumn} IS NULL";

        return $this;
    }

    /**
     * Adiciona condição WHERE NOT NULL
     */
    public function whereNotNull(string $column): self
    {
        $escapedColumn = $this->escapeColumn($column);
        $this->wheres[] = "{$escapedColumn} IS NOT NULL";

        return $this;
    }

    /**
     * Adiciona ORDER BY
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $escapedColumn = $this->escapeColumn($column);
        $this->orderBy = "{$escapedColumn} {$direction}";

        return $this;
    }

    /**
     * Adiciona LIMIT
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Adiciona OFFSET
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Paginação
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;
        $this->limit($perPage)->offset($offset);

        $items = $this->get();
        $total = $this->count();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * Conta registros
     */
    public function count(): int
    {
        $sql = $this->buildCountQuery();
        $result = $this->db->queryOne($sql, $this->bindings);

        return (int)$result['count'];
    }

    /**
     * Retorna o primeiro resultado
     */
    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();

        return $results[0] ?? null;
    }

    /**
     * Executa a query e retorna resultados
     */
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $results = $this->db->query($sql, $this->bindings);

        return array_map(function($row) {
            return ($this->model::class)::newInstance($row, true);
        }, $results);
    }

    /**
     * Deleta registros
     */
    public function delete(): int
    {
        $sql = $this->buildDeleteQuery();
        $stmt = $this->db->execute($sql, $this->bindings);

        return $stmt->rowCount();
    }

    /**
     * Atualiza registros
     */
    public function update(array $data): int
    {
        $sql = $this->buildUpdateQuery($data);
        $bindings = array_merge(array_values($data), $this->bindings);
        $stmt = $this->db->execute($sql, $bindings);

        return $stmt->rowCount();
    }

    /**
     * Constrói query SELECT
     */
    private function buildSelectQuery(): string
    {
        $table = $this->model->getTable();
        // Escapa tabela apenas se necessário (não tem backticks)
        $escapedTable = strpos($table, '`') !== false ? $table : "`{$table}`";
        $sql = "SELECT * FROM {$escapedTable}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        if ($this->orderBy !== null) {
            $sql .= " ORDER BY {$this->orderBy}";
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Constrói query COUNT
     */
    private function buildCountQuery(): string
    {
        $table = $this->model->getTable();
        $escapedTable = strpos($table, '`') !== false ? $table : "`{$table}`";
        $sql = "SELECT COUNT(*) as count FROM {$escapedTable}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        return $sql;
    }

    /**
     * Constrói query DELETE
     */
    private function buildDeleteQuery(): string
    {
        $table = $this->model->getTable();
        $escapedTable = strpos($table, '`') !== false ? $table : "`{$table}`";
        $sql = "DELETE FROM {$escapedTable}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        return $sql;
    }

    /**
     * Constrói query UPDATE
     */
    private function buildUpdateQuery(array $data): string
    {
        $table = $this->model->getTable();
        $escapedTable = strpos($table, '`') !== false ? $table : "`{$table}`";
        $sets = [];

        foreach (array_keys($data) as $column) {
            $escapedColumn = $this->escapeColumn($column);
            $sets[] = "{$escapedColumn} = ?";
        }

        $sql = "UPDATE {$escapedTable} SET " . implode(', ', $sets);

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        return $sql;
    }
}

