<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Availability;
use App\Models\User;

class AvailabilityController
{
    private const WEEKDAYS = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

    public function index(string $userId): void
    {
        Auth::requireLogin();
        Response::json(Availability::forUser((int) $userId));
    }

    // Cadastro de disponibilidade: apenas admin (requisito funcional RQF2.2).
    // Aceita vários dias de uma vez; se um único dia se sobrepuser, nada é salvo (tudo ou nada).
    public function store(): void
    {
        Auth::requireAdmin();
        $data = Request::body();

        $userId = (int) ($data['user_id'] ?? 0);
        $user = User::find($userId);
        if ($user === null || $user['role'] !== 'attendant') {
            Response::error('Atendente inválido.', 400);
        }

        $days = $data['days'] ?? [];
        if (!is_array($days) || count($days) === 0) {
            Response::error('Selecione ao menos um dia da semana.', 400);
        }

        $start = $data['start_time'] ?? '';
        $end = $data['end_time'] ?? '';
        if (($error = $this->validateTimes($start, $end)) !== null) {
            Response::error($error, 400);
        }

        // Valida e checa sobreposição em todos os dias antes de inserir qualquer um.
        foreach ($days as $day) {
            if (!is_numeric($day) || $day < 0 || $day > 6) {
                Response::error('Dia da semana inválido.', 400);
            }
            if (Availability::overlaps($userId, (int) $day, $start, $end)) {
                Response::error('Já existe disponibilidade sobreposta em ' . self::WEEKDAYS[(int) $day] . '. Nada foi salvo.', 400);
            }
        }

        $active = (bool) ($data['active'] ?? true);
        foreach ($days as $day) {
            Availability::create([
                'user_id'     => $userId,
                'day_of_week' => (int) $day,
                'start_time'  => $start,
                'end_time'    => $end,
                'active'      => $active,
            ]);
        }

        Response::json(['message' => 'Disponibilidade adicionada.'], 201);
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        $id = (int) $id;

        $existing = Availability::find($id);
        if ($existing === null) {
            Response::error('Disponibilidade não encontrada.', 404);
        }

        $data = Request::body();
        if (($error = $this->validateWindow($data)) !== null) {
            Response::error($error, 400);
        }

        if (Availability::overlaps((int) $existing['user_id'], (int) $data['day_of_week'], $data['start_time'], $data['end_time'], $id)) {
            Response::error('Já existe uma disponibilidade que se sobrepõe a esse horário neste dia.', 400);
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

        return $this->validateTimes($data['start_time'] ?? '', $data['end_time'] ?? '');
    }

    private function validateTimes(string $start, string $end): ?string
    {
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
