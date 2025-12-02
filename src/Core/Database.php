<?php

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Classe Database - Gerenciamento de conexão e queries
 * 
 * Implementa o padrão Singleton para garantir uma única conexão
 * Suporta Prepared Statements para prevenir SQL Injection
 */
class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private array $config;

    /**
     * Construtor privado (Singleton)
     */
    private function __construct()
    {
        $this->config = require base_path('config/database.php');
        $this->connect();
    }

    /**
     * Obtém instância única da classe (Singleton)
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Conecta ao banco de dados
     */
    private function connect(): void
    {
        $config = $this->config['connections'][$this->config['default']];

        $dsn = sprintf(
            "%s:host=%s;port=%s;dbname=%s;charset=%s",
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );

            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new \RuntimeException(
                "Não foi possível conectar ao banco de dados: " . $e->getMessage()
            );
        }
    }

    /**
     * Retorna a conexão PDO
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * Executa uma query SELECT
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Garante que retorna um array
        return is_array($results) ? $results : [];
    }

    /**
     * Executa uma query e retorna o primeiro resultado
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        
        return $result !== false ? $result : null;
    }

    /**
     * Executa uma query INSERT/UPDATE/DELETE
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            throw new \RuntimeException("Erro ao executar query: " . $e->getMessage());
        }
    }

    /**
     * Retorna o último ID inserido
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Reverte uma transação
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Verifica se está em uma transação
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    /**
     * Previne clonagem (Singleton)
     */
    private function __clone() {}

    /**
     * Previne unserialize (Singleton)
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

