<?php

declare(strict_types=1);

namespace Core;

/**
 * Classe Migrator - Gerenciador de migrations
 * 
 * Executa, reverte e gerencia migrations
 */
class Migrator
{
    private Database $db;
    private string $migrationsPath;
    private string $migrationsTable;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->migrationsPath = base_path('database/migrations');
        $this->migrationsTable = config('database.migrations', 'migrations');
        
        $this->createMigrationsTable();
    }

    /**
     * Cria a tabela de migrations se não existir
     */
    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
    }

    /**
     * Executa todas as migrations pendentes
     */
    public function apply(): void
    {
        $migrations = $this->getPendingMigrations();

        if (empty($migrations)) {
            echo "Nenhuma migration pendente.\n";
            return;
        }

        $batch = $this->getNextBatchNumber();

        foreach ($migrations as $migration) {
            echo "Executando migration: {$migration}\n";
            
            try {
                $this->runMigration($migration, 'up');
                $this->logMigration($migration, $batch);
                
                echo "✓ {$migration} executada com sucesso.\n";
            } catch (\Throwable $e) {
                echo "✗ Erro ao executar {$migration}: {$e->getMessage()}\n";
                throw $e;
            }
        }

        echo "\nTotal de migrations executadas: " . count($migrations) . "\n";
    }

    /**
     * Reverte a última batch de migrations
     */
    public function rollback(bool $all = false): void
    {
        if ($all) {
            $migrations = $this->getAllExecutedMigrations();
        } else {
            $migrations = $this->getLastBatchMigrations();
        }

        if (empty($migrations)) {
            echo "Nenhuma migration para reverter.\n";
            return;
        }

        // Reverte em ordem inversa
        $migrations = array_reverse($migrations);

        foreach ($migrations as $migration) {
            echo "Revertendo migration: {$migration['migration']}\n";
            
            try {
                $this->runMigration($migration['migration'], 'down');
                $this->removeMigration($migration['migration']);
                
                echo "✓ {$migration['migration']} revertida com sucesso.\n";
            } catch (\Throwable $e) {
                echo "✗ Erro ao reverter {$migration['migration']}: {$e->getMessage()}\n";
                throw $e;
            }
        }

        echo "\nTotal de migrations revertidas: " . count($migrations) . "\n";
    }

    /**
     * Cria uma nova migration
     */
    public function create(string $name): void
    {
        $timestamp = date('Y_m_d_His');
        $className = $this->getClassName($name);
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $this->migrationsPath . '/' . $filename;

        $template = $this->getMigrationTemplate($className);

        file_put_contents($filepath, $template);

        echo "Migration criada: {$filename}\n";
    }

    /**
     * Executa uma migration
     */
    private function runMigration(string $name, string $method): void
    {
        $filepath = $this->migrationsPath . '/' . $name . '.php';

        if (!file_exists($filepath)) {
            throw new \RuntimeException("Migration não encontrada: {$name}");
        }

        // As migrations retornam classes anônimas
        $migration = require $filepath;

        if (!is_object($migration)) {
            throw new \RuntimeException("Migration {$name} deve retornar uma instância da classe");
        }

        if (!method_exists($migration, $method)) {
            throw new \RuntimeException("Método {$method} não existe na migration {$name}");
        }

        $migration->$method();
    }

    /**
     * Obtém migrations pendentes
     */
    private function getPendingMigrations(): array
    {
        $all = $this->getAllMigrationFiles();
        $executed = $this->getExecutedMigrations();

        return array_diff($all, $executed);
    }

    /**
     * Obtém todos os arquivos de migration
     */
    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.*\.php$/', $file)) {
                $migrations[] = basename($file, '.php');
            }
        }

        sort($migrations);

        return $migrations;
    }

    /**
     * Obtém migrations já executadas
     */
    private function getExecutedMigrations(): array
    {
        $sql = "SELECT migration FROM {$this->migrationsTable} ORDER BY id";
        $results = $this->db->query($sql);

        return array_column($results, 'migration');
    }

    /**
     * Obtém todas as migrations executadas
     */
    private function getAllExecutedMigrations(): array
    {
        $sql = "SELECT * FROM {$this->migrationsTable} ORDER BY id DESC";
        return $this->db->query($sql);
    }

    /**
     * Obtém migrations da última batch
     */
    private function getLastBatchMigrations(): array
    {
        $batch = $this->getLastBatchNumber();

        if ($batch === 0) {
            return [];
        }

        $sql = "SELECT * FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id DESC";
        return $this->db->query($sql, [$batch]);
    }

    /**
     * Obtém o próximo número de batch
     */
    private function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Obtém o último número de batch
     */
    private function getLastBatchNumber(): int
    {
        $sql = "SELECT MAX(batch) as batch FROM {$this->migrationsTable}";
        $result = $this->db->queryOne($sql);

        return (int)($result['batch'] ?? 0);
    }

    /**
     * Registra migration executada
     */
    private function logMigration(string $migration, int $batch): void
    {
        $sql = "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)";
        $this->db->execute($sql, [$migration, $batch]);
    }

    /**
     * Remove registro de migration
     */
    private function removeMigration(string $migration): void
    {
        $sql = "DELETE FROM {$this->migrationsTable} WHERE migration = ?";
        $this->db->execute($sql, [$migration]);
    }

    /**
     * Obtém nome da classe a partir do arquivo (não usado mais - mantido para compatibilidade)
     */
    private function getClassNameFromFile(string $filename): string
    {
        // Migrations agora usam classes anônimas
        // Este método é mantido apenas para o template generator
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
        return $this->getClassName($name);
    }

    /**
     * Converte nome em classe (usado apenas no template generator)
     */
    private function getClassName(string $name): string
    {
        return str_replace('_', '', ucwords($name, '_'));
    }

    /**
     * Template de migration
     */
    private function getMigrationTemplate(string $className): string
    {
        return <<<PHP
<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    /**
     * Executa a migration
     */
    public function up(): void
    {
        \$this->createTable('table_name', function (Schema \$table) {
            \$table->id();
            \$table->string('name');
            \$table->timestamps();
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        \$this->dropTable('table_name');
    }
};

PHP;
    }
}

