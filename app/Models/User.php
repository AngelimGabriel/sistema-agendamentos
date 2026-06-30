<?php

namespace App\Models;

use App\Core\Database;

class User
{
    // Lista os usuarios ativos. Nunca seleciona a coluna password.
    public static function all(): array
    {
        $sql = 'SELECT id, name, email, role, created_at, updated_at
                FROM users
                WHERE deleted_at IS NULL
                ORDER BY id';

        return Database::connection()->query($sql)->fetchAll();
    }

    // Busca um usuario ativo pelo id. Retorna null se não existir.
    public static function find(int $id): ?array
    {
        $sql = 'SELECT id, name, email, role, created_at, updated_at
                FROM users
                WHERE id = :id AND deleted_at IS NULL';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    // Inclui o password porque é usado na verificação de login.
    public static function findByEmail(string $email): ?array
    {
        $sql = 'SELECT id, name, email, password, role
                FROM users
                WHERE email = :email AND deleted_at IS NULL';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch() ?: null;
    }

    // Verifica se o email já existe entre os ativos. ignoreId pula um id, útil na edição.
    public static function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email AND deleted_at IS NULL';
        $params = [':email' => $email];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch();
    }

    // Cria um novo usuário e retorna o id gerado. Senha é armazenada como hash bcrypt.
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO users (name, email, password, role)
                VALUES (:name, :email, :password, :role)
                RETURNING id';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':name'     => $data['name'],
            ':email'    => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':role'     => $data['role'],
        ]);

        return (int) $stmt->fetchColumn();
    }

    // Edição não altera email nem senha. Atualiza nome, role e updated_at.
    public static function update(int $id, array $data): void
    {
        $sql = 'UPDATE users
                SET name = :name, role = :role, updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':role' => $data['role'],
            ':id'   => $id,
        ]);
    }

    // Conta os admins ativos. Usado para garantir que sempre exista ao menos um administrador.
    public static function adminCount(): int
    {
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'admin' AND deleted_at IS NULL";

        return (int) Database::connection()->query($sql)->fetchColumn();
    }

    public static function softDelete(int $id): void
    {
        $sql = 'UPDATE users SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
}
