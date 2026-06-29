<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes[] = ['method' => 'GET', 'path' => $path, 'handler' => $handler];
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes[] = ['method' => 'POST', 'path' => $path, 'handler' => $handler];
    }

    // Encontra a rota que casa com o método + caminho e a executa. Se nenhuma casar, responde 404.
    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH), '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['path'], $path);
            if ($params !== null) {
                $this->call($route['handler'], $params);
                return;
            }
        }

        Response::error('Rota não encontrada.', 404);
    }

    // Transforma /users/{id} em regex e extrai os parâmetros nomeados da URL.
    private function match(string $routePath, string $path): ?array
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?<$1>[^/]+)', $routePath);

        if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
            return null;
        }

        // Mantém apenas as chaves nomeadas (ex: 'id'), descartando os índices numéricos.
        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    // Aceita um handler como closure ou como [Controller::class, 'metodo'].
    private function call(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $handler = [new $class(), $method];
        }

        call_user_func_array($handler, array_values($params));
    }
}
