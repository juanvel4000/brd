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

        if (mb_strlen($content) >= 255) {
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
            return self::find($pdo->lastInsertId());
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

    public static function find(int $id): ?Post
    {
        $pdo = Connector::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$id]);

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
        return self::find($this->reply_to);
    }
}
