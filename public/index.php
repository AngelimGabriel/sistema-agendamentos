<?php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;
use App\Core\Response;

$router = new Router();

// Rota de teste para validar o roteador. Sai quando as rotas reais entrarem.
$router->get('/health', function (): void {
    Response::json(['status' => 'ok']);
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
