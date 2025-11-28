<?php

/**
 * Rotas API da Aplicação
 * 
 * @var \Core\Router $router
 */

// O router é injetado automaticamente pela Application

// Grupo de rotas API
$router->group(['prefix' => '/api'], function($router) {
    
    // Health Check
    $router->get('/health', function() {
        json_response([
            'status' => 'ok',
            'timestamp' => time(),
            'version' => '1.0.0'
        ]);
    });
    
    // TODO: Adicionar rotas API aqui
    // Exemplo:
    // $router->get('/users', [Api\UserController::class, 'index']);
});

