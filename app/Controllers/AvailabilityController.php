<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Availability;
use App\Models\User;

class AvailabilityController
{
    public function index(string $userId): void
    {
        Auth::requireLogin();
        Response::json(Availability::forUser((int) $userId));
    }

    // Cadastro de disponibilidade: apenas admin (requisitos funcionais RQF2.2).
    public function store(): void
    {
        Auth::requireAdmin();
        $data = Request::body();

        $user = User::find((int) ($data['user_id'] ?? 0));
        if ($user === null || $user['role'] !== 'attendant') {
            Response::error('Atendente inválido.', 400);
        }

        if (($error = $this->validateWindow($data)) !== null) {
            Response::error($error, 400);
        }

        $id = Availability::create([
            'user_id'     => (int) $data['user_id'],
            'day_of_week' => (int) $data['day_of_week'],
            'start_time'  => $data['start_time'],
            'end_time'    => $data['end_time'],
            'active'      => (bool) ($data['active'] ?? true),
        ]);

        Response::json(Availability::find($id), 201);
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        $id = (int) $id;

        if (Availability::find($id) === null) {
            Response::error('Disponibilidade não encontrada.', 404);
        }

        $data = Request::body();
        if (($error = $this->validateWindow($data)) !== null) {
            Response::error($error, 400);
        }

        Availability::update($id, [
            'day_of_week' => (int) $data['day_of_week'],
            'start_time'  => $data['start_time'],
            'end_time'    => $data['end_time'],
            'active'      => (bool) ($data['active'] ?? true),
        ]);

        Response::json(Availability::find($id));
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();
        $id = (int) $id;

        if (Availability::find($id) === null) {
            Response::error('Disponibilidade não encontrada.', 404);
        }

        Availability::softDelete($id);
        Response::json(['message' => 'Disponibilidade removida.']);
    }

    private function validateWindow(array $data): ?string
    {
        $dayOfWeek = $data['day_of_week'] ?? null;
        if (!is_numeric($dayOfWeek) || $dayOfWeek < 0 || $dayOfWeek > 6) {
            return 'Dia da semana inválido.';
        }

        $start = $data['start_time'] ?? '';
        $end = $data['end_time'] ?? '';

        if (!$this->isWholeHour($start) || !$this->isWholeHour($end)) {
            return 'Os horários devem ser em horas cheias (ex: 09:00).';
        }

        if ($end <= $start) {
            return 'A hora final deve ser maior que a hora inicial.';
        }

        return null;
    }

    private function isWholeHour(string $time): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):00$/', $time);
    }
}
