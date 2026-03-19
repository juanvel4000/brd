<?php
require_once __DIR__ . "/vendor/autoload.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(["DB_ADAPTER", "DB_NAME"]);

$adapter = $_ENV["DB_ADAPTER"];

if ($adapter === "sqlite") {
    $db = [
        "adapter" => "sqlite",
        "name" => $_ENV["DB_NAME"],
    ];
} else {
    $dotenv->required(["DB_HOST", "DB_USER", "DB_PASS"]);
    $db = [
        "adapter" => $adapter,
        "host" => $_ENV["DB_HOST"],
        "name" => $_ENV["DB_NAME"],
        "user" => $_ENV["DB_USER"],
        "pass" => $_ENV["DB_PASS"],
        "port" => $_ENV["DB_PORT"] ?? ($adapter === "pgsql" ? "5432" : "3306"),
        "charset" => $_ENV["DB_CHARSET"] ?? "utf8",
    ];
}

return [
    "paths" => [
        "migrations" => "%%PHINX_CONFIG_DIR%%/db/migrations",
        "seeds" => "%%PHINX_CONFIG_DIR%%/db/seeds",
    ],
    "environments" => [
        "default_migration_table" => "phinxlog",
        "default_environment" => "production",
        "production" => $db,
    ],
    "version_order" => "creation",
];
