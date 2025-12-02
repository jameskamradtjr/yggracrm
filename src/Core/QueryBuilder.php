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
     * Retorna o array de wheres de forma segura
     */
    public function getWheres(): array
    {
        return is_array($this->wheres) ? $this->wheres : [];
    }
    
    /**
     * Retorna o array de bindings de forma segura
     */
    public function getBindings(): array
    {
        return is_array($this->bindings) ? $this->bindings : [];
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
     * Suporta: 
     *   - where('column', 'value') - usa '=' como operador padrão
     *   - where('column', 'operator', 'value') - quando o segundo parâmetro é um operador válido
     *   - where(function($q) { ... }) - para grupos de condições
     */
    public function where(string|callable $column, mixed $value = null, mixed $operator = null): self
    {
        // Se for uma closure, trata como grupo de condições
        if (is_callable($column)) {
            $groupBuilder = new self($this->model);
            // Suprime warning de depreciação key() que pode ocorrer durante execução da closure
            @$column($groupBuilder);
            
            // Usa métodos getter para evitar problemas com key()
            $groupWheres = $groupBuilder->getWheres();
            $groupBindings = $groupBuilder->getBindings();
            
            if (!empty($groupWheres)) {
                $this->wheres[] = "(" . implode(' AND ', $groupWheres) . ")";
                $this->bindings = array_merge($this->bindings, $groupBindings);
            }
            
            return $this;
        }
        
        // Lista de operadores válidos
        $validOperators = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];
        
        // Detecta se o segundo parâmetro é um operador válido
        // Se for, trata como: where('column', 'operator', 'value')
        // Caso contrário, trata como: where('column', 'value') ou where('column', 'value', 'operator')
        if ($value !== null && is_string($value) && in_array($value, $validOperators)) {
            // Formato: where('column', 'operator', 'value')
            $actualOperator = $value;
            $actualValue = $operator;
            $escapedColumn = $this->escapeColumn($column);
            $this->wheres[] = "{$escapedColumn} {$actualOperator} ?";
            $this->bindings[] = $actualValue;
        } else {
            // Formato: where('column', 'value') ou where('column', 'value', 'operator')
            $actualOperator = ($operator !== null && is_string($operator) && in_array($operator, $validOperators)) ? $operator : '=';
            $actualValue = $value;
            
            $escapedColumn = $this->escapeColumn($column);
            $this->wheres[] = "{$escapedColumn} {$actualOperator} ?";
            if ($actualValue !== null) {
                $this->bindings[] = $actualValue;
            }
        }

        return $this;
    }

    /**
     * Adiciona condição WHERE com OR
     * Suporta: orWhere('column', 'value') ou orWhere(function($q) { ... })
     */
    public function orWhere(string|callable $column, mixed $value = null, string $operator = '='): self
    {
        // Se for uma closure, trata como grupo de condições
        if (is_callable($column)) {
            $groupBuilder = new self($this->model);
            // Suprime warning de depreciação key() que pode ocorrer durante execução da closure
            @$column($groupBuilder);
            
            // Usa métodos getter para evitar problemas com key()
            $groupWheres = $groupBuilder->getWheres();
            $groupBindings = $groupBuilder->getBindings();
            
            if (!empty($groupWheres)) {
                $conjunction = empty($this->wheres) ? '' : ' OR ';
                $this->wheres[] = $conjunction . "(" . implode(' AND ', $groupWheres) . ")";
                $this->bindings = array_merge($this->bindings, $groupBindings);
            }
            
            return $this;
        }
        
        // Caso contrário, trata como condição normal
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
     * Adiciona condição WHERE NOT EQUAL
     */
    public function whereNot(string $column, mixed $value): self
    {
        $escapedColumn = $this->escapeColumn($column);
        $this->wheres[] = "{$escapedColumn} != ?";
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Adiciona condição WHERE BETWEEN
     */
    public function whereBetween(string $column, array $values): self
    {
        $escapedColumn = $this->escapeColumn($column);
        $this->wheres[] = "{$escapedColumn} BETWEEN ? AND ?";
        $this->bindings[] = $values[0];
        $this->bindings[] = $values[1];

        return $this;
    }

    /**
     * Adiciona condição WHERE com SQL bruto
     */
    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = $sql;
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    /**
     * Adiciona condição WHERE DATE (filtra apenas pela data, ignorando hora)
     */
    public function whereDate(string $column, string $date): self
    {
        $escapedColumn = $this->escapeColumn($column);
        $this->wheres[] = "DATE({$escapedColumn}) = ?";
        $this->bindings[] = $date;

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
     * Adiciona ORDER BY com SQL bruto
     */
    public function orderByRaw(string $sql): self
    {
        $this->orderBy = $sql;
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

        // Garante que $results é um array antes de usar array_map
        if (!is_array($results)) {
            return [];
        }

        $items = [];
        foreach ($results as $row) {
            $items[] = ($this->model::class)::newInstance($row, true);
        }
        
        return $items;
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

