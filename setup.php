#!/usr/bin/env php
<?php

/**
 * Script de Setup Automático
 * 
 * Configura o sistema automaticamente
 * 
 * Uso: php setup.php
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║                                                          ║\n";
echo "║          SistemaBase Framework - Setup v1.0              ║\n";
echo "║                                                          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";

// Verifica requisitos
echo "Verificando requisitos do sistema...\n";

// PHP Version
$phpVersion = phpversion();
if (version_compare($phpVersion, '8.1.0', '<')) {
    die("✗ PHP 8.1+ é necessário. Versão atual: {$phpVersion}\n");
}
echo "✓ PHP {$phpVersion}\n";

// Extensões necessárias
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl'];
foreach ($extensions as $ext) {
    if (!extension_loaded($ext)) {
        die("✗ Extensão PHP '{$ext}' não está instalada\n");
    }
    echo "✓ Extensão {$ext}\n";
}

// Verifica Composer
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("✗ Dependências não instaladas. Execute: composer install\n");
}
echo "✓ Composer dependencies\n";

echo "\n";

// Cria arquivo .env se não existir
if (!file_exists(__DIR__ . '/.env')) {
    echo "Criando arquivo .env...\n";
    if (file_exists(__DIR__ . '/env.example')) {
        copy(__DIR__ . '/env.example', __DIR__ . '/.env');
        echo "✓ Arquivo .env criado\n";
        echo "\n";
        echo "⚠ IMPORTANTE: Edite o arquivo .env e configure:\n";
        echo "  - DB_DATABASE\n";
        echo "  - DB_USERNAME\n";
        echo "  - DB_PASSWORD\n";
        echo "\n";
    } else {
        echo "✗ Arquivo env.example não encontrado\n";
    }
} else {
    echo "✓ Arquivo .env já existe\n";
}

// Verifica permissões
echo "\nVerificando permissões de pastas...\n";

$writableDirs = [
    'storage/logs',
    'storage/cache',
    'storage/sessions',
    'public/uploads'
];

foreach ($writableDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✓ Criado: {$dir}\n";
    }
    
    if (!is_writable($path)) {
        echo "⚠ {$dir} não tem permissão de escrita\n";
    } else {
        echo "✓ {$dir} - writable\n";
    }
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║              Próximos Passos                             ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "1. Configure o arquivo .env com suas credenciais\n";
echo "2. Crie o banco de dados: CREATE DATABASE sistemabase;\n";
echo "3. Execute as migrations: php migrate apply\n";
echo "4. (Opcional) Popule dados iniciais: php -f database/seeds/InitialDataSeeder.php\n";
echo "5. Acesse: http://localhost/sistemabase26\n";
echo "\n";
echo "Documentação:\n";
echo "- README.md - Visão geral do projeto\n";
echo "- INSTALL.md - Guia de instalação detalhado\n";
echo "- USAGE.md - Exemplos de uso\n";
echo "\n";
echo "✨ Setup concluído com sucesso!\n";
echo "\n";

