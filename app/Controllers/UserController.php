<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

class UserController
{
    // Retorna a lista de usuários cadastrados. O PDF diz que ela é a mesma para todos
    // os tipos (RQF1.1), então basta estar autenticado — não precisa ser admin.
    public function index(): void
    {
        Auth::requireLogin();
        Response::json(User::all());
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $user = User::find((int) $id);

        if ($user === null) {
            Response::error('Usuário não encontrado.', 404);
        }

        Response::json($user);
    }

    // Inserção: apenas admin (requisito funcional RQF1.2).
    public function store(): void
    {
        Auth::requireAdmin();
        $data = Request::body();

        if (($error = $this->validate($data, isUpdate: false)) !== null) {
            Response::error($error, 400);
        }

        $id = User::create([
            'name'     => trim($data['name']),
            'email'    => trim($data['email']),
            'password' => $data['password'],
            'role'     => $data['role'],
        ]);

        Response::json(User::find($id), 201);
    }

    // Edição: admin edita qualquer um, atendente edita só o próprio (requisito funcional RQF1.3).
    public function update(string $id): void
    {
        Auth::requireLogin();
        $id = (int) $id;

        if (!Auth::isAdmin() && Auth::id() !== $id) {
            Response::error('Acesso negado.', 403);
        }

        $user = User::find($id);
        if ($user === null) {
            Response::error('Usuário não encontrado.', 404);
        }

        $data = Request::body();
        if (($error = $this->validate($data, isUpdate: true)) !== null) {
            Response::error($error, 400);
        }

        // Ninguém altera o próprio tipo de usuário. Só o admin altera, e apenas o de outro usuário.
        $role = $user['role'];
        if (Auth::isAdmin() && Auth::id() !== $id) {
            if (!$this->isValidRole($data['role'] ?? '')) {
                Response::error('Tipo de usuário inválido.', 400);
            }
            $role = $data['role'];
        }

        // Impede deixar o sistema sem nenhum administrador (case exige ao menos um).
        if ($user['role'] === 'admin' && $role !== 'admin' && User::adminCount() <= 1) {
            Response::error('O sistema deve ter ao menos um administrador.', 400);
        }

        User::update($id, ['name' => trim($data['name']), 'role' => $role]);
        Response::json(User::find($id));
    }

    // Exclusão: apenas admin, com soft delete (requisito funcional RQF1.1).
    public function destroy(string $id): void
    {
        Auth::requireAdmin();
        $id = (int) $id;

        $user = User::find($id);
        if ($user === null) {
            Response::error('Usuário não encontrado.', 404);
        }

        // Impede deixar o sistema sem nenhum administrador (case exige ao menos um).
        if ($user['role'] === 'admin' && User::adminCount() <= 1) {
            Response::error('O sistema deve ter ao menos um administrador.', 400);
        }

        User::softDelete($id);
        Response::json(['message' => 'Usuário excluído.']);
    }

    // Retorna a primeira mensagem de erro de validação, ou null se estiver tudo certo.
    private function validate(array $data, bool $isUpdate): ?string
    {
        if (trim($data['name'] ?? '') === '') {
            return 'O nome é obrigatório.';
        }

        if (!$isUpdate) {
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $confirmation = $data['password_confirmation'] ?? '';

            if (!$this->isValidRole($data['role'] ?? '')) {
                return 'Tipo de usuário inválido.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return 'E-mail inválido.';
            }
            if (User::emailExists($email)) {
                return 'Este e-mail já está cadastrado.';
            }
            if (strlen($password) < 8) {
                return 'A senha deve ter no mínimo 8 caracteres.';
            }
            if ($password !== $confirmation) {
                return 'A confirmação de senha não confere.';
            }
        }

        return null;
    }

    private function isValidRole(string $role): bool
    {
        return in_array($role, ['admin', 'attendant'], true);
    }
}
