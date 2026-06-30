<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

class AuthController
{
    public function login(): void
    {
        $data = Request::body();
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            Response::error('Informe email e senha.', 400);
        }

        $user = User::findByEmail($email);

        // Mesma mensagem para email inexistente ou senha errada: não revela qual dos dois falhou.
        if ($user === null || !password_verify($password, $user['password'])) {
            Response::error('Email ou senha inválidos.', 401);
        }

        Auth::login($user);
        Response::json(Auth::user());
    }

    public function logout(): void
    {
        Auth::logout();
        Response::json(['message' => 'Logout realizado.']);
    }

    // Usado pelo front ao carregar: diz se há sessão e qual o tipo de usuário.
    public function me(): void
    {
        Auth::requireLogin();
        Response::json(Auth::user());
    }
}
