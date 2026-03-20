<?php

namespace Brd\Boards;

class Manager
{
    public static array $boards = [];
    public static array $board_descriptions = [];

    public static function loadBoards(): void
    {
        if (self::$boards) {
            return;
        }

        $trimmed_boards = [];
        $descriptions = [];

        foreach (explode(",", trim($_ENV["BOARDS"] ?? "brd,etc")) as $board) {
            $trimmed = mb_strtolower(trim($board));
            if (!$trimmed) {
                trigger_error("passed an empty board name", E_USER_WARNING);
                continue;
            }
            $key = strtoupper($trimmed) . "_DESC";
            $trimmed_boards[] = $trimmed;
            $descriptions[$trimmed] = $_ENV[$key] ?? "Board";
        }

        self::$boards = $trimmed_boards;
        self::$board_descriptions = $descriptions;
    }

    public static function boardExists(string $board): bool
    {
        return in_array(strtolower(trim($board)), self::$boards);
    }

    public static function getBoardList(): array
    {
        return self::$board_descriptions;
    }
}
