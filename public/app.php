<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Dotenv\Dotenv;
use Bramus\Router\Router;

use Brd\Database\Connector;
use Brd\Boards\Manager;
use Brd\Models\Post;
$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();
$dotenv->required(["DB_ADAPTER", "DB_NAME"]);
