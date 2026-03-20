<?php

namespace Brd\Database;

use Dotenv\Dotenv;
use PDOException;
use PDO;

Dotenv::createImmutable(__DIR__ . "/../../");
Dotenv::load();
Dotenv::required(["DB_ADAPTER", "DB_NAME"]);

class Connector
{
    private static ?PDO $connection = null;

    public static function getConnection(): ?PDO
    {
        if (self::$connection === null) {
            self::$connection = self::connect();
        }
        return self::$connection;
    }

    public static function connect(): ?PDO
    {
        $adapter = $_ENV["DB_ADAPTER"];
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $dsn = match ($adapter) {
            "mysql",
            "mariadb"
                => "mysql:host={$_ENV["DB_HOST"]};dbname={$_ENV["DB_NAME"]};charset=" .
                ($_ENV["DB_CHARSET"] ?? "utf8mb4"),
            "pgsql"
                => "pgsql:host={$_ENV["DB_HOST"]};dbname={$_ENV["DB_NAME"]}",
            "sqlite" => "sqlite:{$_ENV["DB_NAME"]}",
            default => throw new \InvalidArgumentException(
                "unsupported adapter: $adapter",
            ),
        };

        return new PDO(
            $dsn,
            $_ENV["DB_USER"] ?? null,
            $_ENV["DB_PASS"] ?? null,
            $options,
        );
    }
}
