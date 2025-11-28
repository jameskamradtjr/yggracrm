<?php

/**
 * SistemaBase Framework
 * 
 * Front Controller - Ponto de entrada da aplicação
 */

// Define o caminho base
define('BASE_PATH', dirname(__DIR__));

// Carrega o autoloader do Composer
require BASE_PATH . '/vendor/autoload.php';

use Core\Application;

// Cria e executa a aplicação
$app = Application::getInstance(BASE_PATH);
$app->run();

