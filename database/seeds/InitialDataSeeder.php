<?php

/**
 * Seeder de Dados Iniciais
 * 
 * Popula o banco com permissões e dados básicos
 * 
 * Uso: php -f database/seeds/InitialDataSeeder.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Core\Application;
use Core\Database;
use App\Models\Permission;

// Inicializa aplicação
$app = Application::getInstance(dirname(__DIR__, 2));
$db = Database::getInstance();

echo "Iniciando seed de dados iniciais...\n\n";

try {
    // Cria permissões básicas
    echo "Criando permissões...\n";
    
    $resources = [
        'users' => 'Usuários',
        'roles' => 'Roles',
        'permissions' => 'Permissões',
        'dashboard' => 'Dashboard',
        'reports' => 'Relatórios',
        'settings' => 'Configurações',
    ];

    foreach ($resources as $resource => $name) {
        echo "- Criando permissões para {$name}...\n";
        
        // Verifica se já existe
        $existing = $db->queryOne(
            "SELECT COUNT(*) as count FROM permissions WHERE resource = ?",
            [$resource]
        );

        if ($existing['count'] == 0) {
            Permission::createCrudPermissions($resource, $name);
        } else {
            echo "  Permissões para {$name} já existem, pulando...\n";
        }
    }

    echo "\n✓ Seed de dados concluído com sucesso!\n";
    echo "\nPermissões criadas:\n";
    
    $permissions = $db->query("SELECT * FROM permissions ORDER BY resource, action");
    foreach ($permissions as $perm) {
        echo "- {$perm['name']} ({$perm['slug']})\n";
    }

    echo "\n";
    echo "===========================================\n";
    echo "Sistema pronto para uso!\n";
    echo "===========================================\n";
    echo "\n";
    echo "Próximos passos:\n";
    echo "1. Acesse: http://localhost/sistemabase26\n";
    echo "2. Clique em 'Criar uma conta'\n";
    echo "3. Preencha seus dados\n";
    echo "4. Faça login e comece a usar!\n";
    echo "\n";

} catch (Exception $e) {
    echo "\n✗ Erro ao executar seed: {$e->getMessage()}\n";
    exit(1);
}

