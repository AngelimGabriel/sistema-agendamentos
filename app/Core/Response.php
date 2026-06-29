<?php

namespace App\Core;

class Response
{
    // Envia o corpo em JSON com o status HTTP correto e encerra a requisição.
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Atalho para erros: padroniza o corpo como { "error": "..." }.
    public static function error(string $message, int $status): void
    {
        self::json(['error' => $message], $status);
    }
}
