<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\User;
use DateTime;

class AppointmentController
{
    // Núcleo do módulo: dado atendente + data, devolve a grade de 1h com os ocupados marcados.
    public function availableSlots(string $attendantId): void
    {
        Auth::requireLogin();
        $attendantId = (int) $attendantId;

        $date = $_GET['date'] ?? '';
        if (!$this->isValidDate($date)) {
            Response::error('Data inválida.', 400);
        }

        $dayOfWeek = (int) date('w', strtotime($date)); // 0=domingo ... 6=sabado
        $windows = Availability::activeForDay($attendantId, $dayOfWeek);

        $booked = array_map(
            fn(array $row): string => substr($row['start_time'], 0, 5),
            Appointment::scheduledOn($attendantId, $date)
        );

        // Fatia cada janela em blocos de 1h e marca os ocupados. A chave evita slots duplicados.
        $slots = [];
        foreach ($windows as $window) {
            $startHour = (int) substr($window['start_time'], 0, 2);
            $endHour = (int) substr($window['end_time'], 0, 2);

            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $start = sprintf('%02d:00', $hour);
                $slots[$start] = [
                    'start'     => $start,
                    'end'       => sprintf('%02d:00', $hour + 1),
                    'available' => !in_array($start, $booked, true),
                ];
            }
        }

        ksort($slots);
        Response::json(['date' => $date, 'slots' => array_values($slots)]);
    }

    public function index(string $attendantId): void
    {
        Auth::requireLogin();
        Response::json(Appointment::forAttendant((int) $attendantId));
    }

    public function store(): void
    {
        Auth::requireLogin();
        $data = Request::body();

        // Atendente só agenda na própria agenda; admin agenda para qualquer atendente.
        $attendantId = Auth::isAdmin() ? (int) ($data['attendant_id'] ?? 0) : Auth::id();

        $date = $data['date'] ?? '';
        $start = substr($data['start_time'] ?? '', 0, 5);
        $clientName = trim($data['client_name'] ?? '');
        $clientEmail = trim($data['client_email'] ?? '');

        if ($clientName === '') {
            Response::error('O nome do cliente é obrigatório.', 400);
        }
        if (!$this->isValidDate($date)) {
            Response::error('Data inválida.', 400);
        }
        if (!$this->isWholeHour($start)) {
            Response::error('Horário inválido.', 400);
        }

        $attendant = User::find($attendantId);
        if ($attendant === null || $attendant['role'] !== 'attendant') {
            Response::error('Atendente inválido.', 400);
        }

        $end = sprintf('%02d:00', ((int) substr($start, 0, 2)) + 1);
        $dayOfWeek = (int) date('w', strtotime($date));

        if (!$this->withinAvailability($attendantId, $dayOfWeek, $start, $end)) {
            Response::error('Horário fora da disponibilidade do atendente.', 400);
        }
        if (Appointment::existsAt($attendantId, $date, $start)) {
            Response::error('Este horário já está ocupado.', 400);
        }

        $id = Appointment::create([
            'attendant_id' => $attendantId,
            'date'         => $date,
            'start_time'   => $start,
            'end_time'     => $end,
            'client_name'  => $clientName,
            'client_email' => $clientEmail !== '' ? $clientEmail : null,
        ]);

        Response::json(Appointment::find($id), 201);
    }

    public function cancel(string $id): void
    {
        Auth::requireLogin();
        $id = (int) $id;

        $appointment = Appointment::find($id);
        if ($appointment === null) {
            Response::error('Agendamento não encontrado.', 404);
        }

        if (!Auth::isAdmin() && Auth::id() !== (int) $appointment['attendant_id']) {
            Response::error('Acesso negado.', 403);
        }

        Appointment::cancel($id);
        Response::json(['message' => 'Agendamento cancelado.']);
    }

    // O horário precisa caber inteiro dentro de alguma janela ativa do atendente.
    private function withinAvailability(int $attendantId, int $dayOfWeek, string $start, string $end): bool
    {
        foreach (Availability::activeForDay($attendantId, $dayOfWeek) as $window) {
            $windowStart = substr($window['start_time'], 0, 5);
            $windowEnd = substr($window['end_time'], 0, 5);

            if ($start >= $windowStart && $end <= $windowEnd) {
                return true;
            }
        }

        return false;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function isWholeHour(string $time): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):00$/', $time);
    }
}
