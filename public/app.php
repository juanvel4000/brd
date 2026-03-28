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

Manager::loadBoards();
$router = new Router();

$router->before("POST|GET|DELETE", "./*", function () {
    header("Content-Type: application/json");
});
$router->get("/board/{board}", function ($board) {
    $limit = 50;
    $offset = 0;

    if (!empty($_GET["limit"])) {
        $limit = min(100, max(1, intval($_GET["limit"])));
    }

    if (!empty($_GET["offset"])) {
        $offset = max(0, intval($_GET["offset"]));
    }

    $board = trim(mb_strtolower($board));
    if (!Manager::boardExists($board)) {
        http_response_code(404);
        echo json_encode(["error" => "board doesn't exist"]);
        return;
    }

    echo json_encode([
        "description" => Manager::$board_descriptions[$board],
        "posts" => Post::getBoard($board, $limit, $offset),
    ]);
    return;
});

$router->post("/board/{board}", function ($board) {
    $content = $_POST["content"] ?? null;
    $delete_key = $_POST["delete_key"] ?? null;
    $board = trim(mb_strtolower($board));

    if (!$content || !$delete_key) {
        http_response_code(400);
        echo json_encode(["error" => "missing fields"]);
        return;
    }

    if (!Manager::boardExists($board)) {
        http_response_code(404);
        echo json_encode(["error" => "board doesn't exist"]);
        return;
    }

    try {
        $post = Post::create($content, $delete_key, $board, null);
        if ($post) {
            http_response_code(201);
            echo json_encode($post->toArray());
            return;
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(["error" => $e->getMessage()]);
        return;
    }

    http_response_code(400);
    echo json_encode(["error" => "unknown"]);
    return;
});

$router->post("/post/{reply_to}", function ($reply_to) {
    $content = $_POST["content"] ?? null;
    $delete_key = $_POST["delete_key"] ?? null;

    if (!$content || !$delete_key) {
        http_response_code(400);
        echo json_encode(["error" => "missing fields"]);
        return;
    }

    $parent_post = Post::where("id", $reply_to);
    if (!$parent_post) {
        http_response_code(404);
        echo json_encode(["error" => "post not found"]);
        return;
    }

    $board = $parent_post->board;
    if (!Manager::boardExists($board)) {
        http_response_code(404);
        echo json_encode(["error" => "board doesn't exist"]);
        return;
    }

    try {
        $post = Post::create($content, $delete_key, $board, $reply_to);
        if ($post) {
            http_response_code(201);
            echo json_encode($post->toArray());
            return;
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(["error" => $e->getMessage()]);
        return;
    }

    http_response_code(400);
    echo json_encode(["error" => "unknown"]);
    return;
});

$router->get("/post/{id}", function ($id) {
    $limit = 50;
    $offset = 0;

    if (!empty($_GET["limit"])) {
        $limit = min(100, max(1, intval($_GET["limit"])));
    }

    if (!empty($_GET["offset"])) {
        $offset = max(0, intval($_GET["offset"]));
    }

    $post = Post::where("id", $id);
    if (!$post) {
        http_response_code(404);
        echo json_encode(["error" => "post not found"]);
        return;
    }

    $show_replies = filter_var($_GET["show_replies"], FILTER_VALIDATE_BOOL);

    $res = $post->toArray();
    if ($show_replies) {
        $replies = $post->getReplies();
        $reps = [];
        foreach ($replies as $reply) {
            $reps[] = $reply->toArray();
        }
        $res["replies"] = $reps;
    }

    echo json_encode($res);
    return;
});

$router->delete("/post/{id}", function ($id) {
    if (!Post::exists($id)) {
        http_response_code(404);
        echo json_encode(["error" => "post does not exist"]);
        return;
    }

    $delete_key = $_POST["delete_key"];
    if (empty($delete_key)) {
        http_response_code(400);
        echo json_encode(["error" => "missing delete key"]);
        return;
    }

    if (Post::find($id)->delete($delete_key)) {
        http_response_code(204);
        return;
    }

    http_response_code(401);
    echo json_encode(["error" => "deletion failed"]);
    return;
});
$router->run();
