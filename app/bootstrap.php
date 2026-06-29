<?php

// Autoloader: traduz um namespace (App\Core\Router) no caminho do arquivo (app/Core/Router.php),
// carregando a classe sob demanda. Evita um require manual para cada arquivo, sem depender do Composer.
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

session_start();
