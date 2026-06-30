<?php

namespace App\Models;

use App\Core\Database;

class Appointment
{
    // Agenda do atendente (inclui cancelados, para o front mostrar o histórico).
    public static function forAttendant(int $attendantId): array
    {
        $sql = 'SELECT id, attendant_id, date, start_time, end_time, client_name, client_email, status
                FROM appointments
                WHERE attendant_id = :aid AND deleted_at IS NULL
                ORDER BY date, start_time';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':aid' => $attendantId]);

        return $stmt->fetchAll();
    }

    // Horários agendados (não cancelados) de um atendente numa data — usado para marcar ocupados.
    public static function scheduledOn(int $attendantId, string $date): array
    {
        $sql = "SELECT start_time
                FROM appointments
                WHERE attendant_id = :aid AND date = :date
                  AND status = 'scheduled' AND deleted_at IS NULL";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':aid' => $attendantId, ':date' => $date]);

        return $stmt->fetchAll();
    }

    public static function existsAt(int $attendantId, string $date, string $startTime): bool
    {
        $sql = "SELECT 1 FROM appointments
                WHERE attendant_id = :aid AND date = :date AND start_time = :start
                  AND status = 'scheduled' AND deleted_at IS NULL";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':aid' => $attendantId, ':date' => $date, ':start' => $startTime]);

        return (bool) $stmt->fetch();
    }

    public static function find(int $id): ?array
    {
        $sql = 'SELECT id, attendant_id, date, start_time, end_time, client_name, client_email, status
                FROM appointments
                WHERE id = :id AND deleted_at IS NULL';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $sql = 'INSERT INTO appointments (attendant_id, date, start_time, end_time, client_name, client_email)
                VALUES (:aid, :date, :start, :end, :name, :email)
                RETURNING id';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':aid'   => $data['attendant_id'],
            ':date'  => $data['date'],
            ':start' => $data['start_time'],
            ':end'   => $data['end_time'],
            ':name'  => $data['client_name'],
            ':email' => $data['client_email'],
        ]);

        return (int) $stmt->fetchColumn();
    }

    public static function cancel(int $id): void
    {
        $sql = "UPDATE appointments SET status = 'cancelled', updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
}
