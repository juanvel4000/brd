<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();
$dotenv->required(["DB_ADAPTER", "DB_NAME"]);
