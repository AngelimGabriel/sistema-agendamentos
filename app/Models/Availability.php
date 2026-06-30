<?php

namespace App\Models;

use App\Core\Database;

class Availability
{
    // Todas as janelas de um atendente (para a tela de gestão do admin).
    public static function forUser(int $userId): array
    {
        $sql = 'SELECT id, user_id, day_of_week, start_time, end_time, active
                FROM availability
                WHERE user_id = :uid AND deleted_at IS NULL
                ORDER BY day_of_week, start_time';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':uid' => $userId]);

        return $stmt->fetchAll();
    }

    // Janelas ativas de um atendente num dia da semana — base do cálculo de horários livres.
    public static function activeForDay(int $userId, int $dayOfWeek): array
    {
        $sql = 'SELECT start_time, end_time
                FROM availability
                WHERE user_id = :uid AND day_of_week = :dow
                  AND active = TRUE AND deleted_at IS NULL';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':uid' => $userId, ':dow' => $dayOfWeek]);

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $sql = 'SELECT id, user_id, day_of_week, start_time, end_time, active
                FROM availability
                WHERE id = :id AND deleted_at IS NULL';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $sql = 'INSERT INTO availability (user_id, day_of_week, start_time, end_time, active)
                VALUES (:uid, :dow, :start, :end, :active)
                RETURNING id';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':uid'    => $data['user_id'],
            ':dow'    => $data['day_of_week'],
            ':start'  => $data['start_time'],
            ':end'    => $data['end_time'],
            ':active' => $data['active'] ? 'true' : 'false',
        ]);

        return (int) $stmt->fetchColumn();
    }

    public static function update(int $id, array $data): void
    {
        $sql = 'UPDATE availability
                SET day_of_week = :dow, start_time = :start, end_time = :end,
                    active = :active, updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':dow'    => $data['day_of_week'],
            ':start'  => $data['start_time'],
            ':end'    => $data['end_time'],
            ':active' => $data['active'] ? 'true' : 'false',
            ':id'     => $id,
        ]);
    }

    public static function softDelete(int $id): void
    {
        $sql = 'UPDATE availability SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
}
