<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$mysql_host = getenv('MYSQL_HOST');
$mysql_user = getenv('MYSQL_USER');
$mysql_pass = getenv('MYSQL_PASSWORD');
$mysql_db = getenv('MYSQL_DATABASE');
$redis_host = getenv('REDIS_HOST');

$mysqli = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $mysqli->connect_error]);
    exit;
}

$redis = new Redis();
try {
    $redis->connect($redis_host, 6379);
} catch (Exception $e) {
    // Redis might be down, but API should try to work
}

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$request_path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($request_path, '/');

// Normalize path (handle /api/ prefix if present)
if (strpos($path, 'api/') === 0) {
    $path = substr($path, 4);
}

if ($path === 'articles') {
    if ($request_method === 'GET') {
        $result = $mysqli->query("SELECT * FROM articles");
        $articles = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $articles[] = $row;
            }
        }
        echo json_encode($articles);
    } elseif ($request_method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['title']) && isset($data['content'])) {
            $title = $mysqli->real_escape_string($data['title']);
            $content = $mysqli->real_escape_string($data['content']);
            $user_id = 1;
            $sql = "INSERT INTO articles (user_id, title, content) VALUES ('$user_id', '$title', '$content')";
            if ($mysqli->query($sql)) {
                http_response_code(201);
                echo json_encode(["message" => "Article created", "id" => $mysqli->insert_id]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to create article"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid input"]);
        }
    }
} elseif ($path === 'stats') {
    if ($request_method === 'GET') {
        $visits = $redis->get('visits');
        $user_count = $mysqli->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
        $article_count = $mysqli->query("SELECT COUNT(*) as c FROM articles")->fetch_assoc()['c'];
        
        echo json_encode([
            "visits" => $visits ? (int)$visits : 0,
            "users" => (int)$user_count,
            "articles" => (int)$article_count
        ]);
    }
} else {
    http_response_code(404);
    echo json_encode(["error" => "Not Found", "path" => $path]);
}
?>
