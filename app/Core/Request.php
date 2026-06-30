<?php

namespace App\Core;

class Request
{
    // le o corpo JSON da requisição e devolve como array associativo.
    public static function body(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }
}
