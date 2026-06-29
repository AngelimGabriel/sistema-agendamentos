<?php

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $connection = null;

    // Reaproveita a mesma conexão durante a requisição, em vez de reconectar a cada query.
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $host = getenv('DB_HOST') ?: 'db';
            $port = getenv('DB_PORT') ?: '5432';
            $name = getenv('DB_NAME') ?: 'agendamentos';
            $user = getenv('DB_USER') ?: 'app';
            $pass = getenv('DB_PASSWORD') ?: 'app';

            self::$connection = new PDO(
                "pgsql:host=$host;port=$port;dbname=$name",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        }

        return self::$connection;
    }
}
