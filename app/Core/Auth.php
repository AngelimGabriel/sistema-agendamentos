<?php

namespace App\Core;

class Auth
{
    // Guarda na sessão apenas o necessário.
    public static function login(array $user): void
    {
        session_regenerate_id(true); // evita fixação de sessão após o login
        $_SESSION['user'] = [
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return (($_SESSION['user']['role'] ?? null) === 'admin');
    }

    // Exige usuário logado e encerra com 401 caso contrário.
    public static function requireLogin(): void
    {
        if (!self::check()) {
            Response::error('Não autenticado.', 401);
        }
    }

    // Exige que o usuário seja admin e encerra com 403 caso contrário.
    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            Response::error('Acesso negado.', 403);
        }
    }
}
