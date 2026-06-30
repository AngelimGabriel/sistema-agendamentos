<?php

namespace App\Core;

use PDO;
use RuntimeException;

class Database
{
    private static ?PDO $connection = null;

    // Reaproveita a mesma conexão durante a requisição, em vez de reconectar a cada query.
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                self::env('DB_HOST'),
                self::env('DB_PORT'),
                self::env('DB_NAME')
            );

            self::$connection = new PDO(
                $dsn,
                self::env('DB_USER'),
                self::env('DB_PASSWORD'),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        }

        return self::$connection;
    }

    // Lê uma variável de ambiente obrigatória. Falha explicitamente se não existir, em vez de
    // cair em um valor padrão as credenciais vivem só no ambiente (compose/.env), nunca no código.
    private static function env(string $key): string
    {
        $value = getenv($key);

        if ($value === false) {
            throw new RuntimeException("Variável de ambiente obrigatória não definida: {$key}");
        }

        return $value;
    }
}
