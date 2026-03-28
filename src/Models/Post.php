<?php

namespace Brd\Models;

use Brd\Database\Connector;
use Brd\Boards\Manager;
use PDO;

class Post
{
    public int $id;
    public ?int $reply_to;

    public string $content;
    protected string $delete_key;
    public string $board;

    public string $created_at;
    public string $updated_at;

    public function __construct($keys)
    {
        foreach ($keys as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }
    public static function create(
        string $content,
        string $delete_key,
        string $board,
        ?int $reply_to = null,
    ): ?Post {
        $board = mb_strtolower(trim($board));
        if (!Manager::boardExists($board)) {
            throw new \Exception("board does not exist");
        }

        $content = trim(htmlspecialchars($content, encoding: "UTF-8"));

        if (mb_strlen($content) > 255) {
            throw new \Exception("post too large (255 charlimit exceeded)");
        }

        $delete_key = self::createDeleteKey($delete_key);

        if ($reply_to && !self::exists($reply_to)) {
            throw new \Exception(
                "tried to reply to a post that does not exist",
            );
        }

        $pdo = Connector::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO posts (content, delete_key, board, reply_to) VALUES (?, ?, ?, ?)",
        );
        if ($stmt->execute([$content, $delete_key, $board, $reply_to])) {
            return self::where("id", $pdo->lastInsertId());
        }

        return null;
    }

    protected static function createDeleteKey(string $delete_key): string
    {
        return password_hash($delete_key, PASSWORD_BCRYPT);
    }

    protected function verifyDeleteKey(string $delete_key): bool
    {
        return password_verify($delete_key, $this->delete_key);
    }

    public function delete(string $delete_key): bool
    {
        if ($this->verifyDeleteKey($delete_key)) {
            $pdo = Connector::getConnection();
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            return $stmt->execute([$this->id]);
        }
        return false;
    }

    protected function forceDelete(): bool
    {
        $pdo = Connector::getConnection();
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    public static function where(
        string $column = "id",
        string|int $value,
    ): ?Post {
        $pdo = Connector::getConnection();

        $whitelist = ["id", "board", "reply_to"];
        $column = mb_strtolower(trim($column));
        if (!in_array($column, $whitelist)) {
            throw new \Exception("column '${$column}' is not allowed");
        }

        $stmt = $pdo->prepare("SELECT * FROM posts WHERE $column = ?");
        $stmt->execute([$value]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            return new Post($data);
        }
    }

    public static function exists(int $id): bool
    {
        $pdo = Connector::getConnection();

        $stmt = $pdo->prepare(
            "SELECT EXISTS(SELECT 1 FROM posts WHERE id = ?)",
        );
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    public function parent(): ?Post
    {
        return self::where("id", $this->reply_to);
    }

    public static function getBoard(
        string $board,
        int $limit = 50,
        int $offset = 0,
    ): array {
        $board = trim(mb_strtolower($board));
        $pdo = Connector::getConnection();

        $stmt = $pdo->prepare(
            "SELECT * FROM posts WHERE board = ? AND reply_to IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ? ",
        );

        $stmt->execute([$board, $limit, $offset]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $posts = [];
        foreach ($data as $post) {
            $posts[] = new Post($post);
        }
        return $posts;
    }

    public function getReplies(int $limit = 50, int $offset = 0): array
    {
        $pdo = Connector::getConnection();

        $stmt = $pdo->prepare(
            "SELECT * FROM posts WHERE reply_to = ? ORDER BY created_at ASC LIMIT ? OFFSET ?",
        );

        $stmt->execute([$this->id, $limit, $offset]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $posts = [];
        foreach ($data as $post) {
            $posts[] = new Post($post);
        }
        return $posts;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "reply_to" => $this->reply_to,
            "board" => $this->board,
            "content" => $this->content,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
