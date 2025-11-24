<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // p.ex. /api/articles

// Config DB & Redis
$mysqlHost = 'mysql';
$mysqlDb   = getenv('MYSQL_DATABASE') ?: 'serveis';
$mysqlUser = getenv('MYSQL_USER') ?: 'admin';
$mysqlPass = getenv('MYSQL_PASSWORD') ?: 'A123456@';

$redisHost = 'redis';
$redisPort = 6379;

try {
    $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8mb4", $mysqlUser, $mysqlPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $redis = new Redis();
    $redis->connect($redisHost, $redisPort);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de connexió a backend', 'detail' => $e->getMessage()]);
    exit;
}

function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// ── Rutes ─────────────────────────

if ($uri === '/api/articles' && $method === 'GET') {
    $stmt = $pdo->query("SELECT a.id, a.title, a.content, a.published_at, u.username 
                         FROM articles a 
                         JOIN users u ON a.user_id = u.id 
                         ORDER BY a.published_at DESC");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json_response($articles);
}

if ($uri === '/api/articles' && $method === 'POST') {
    $user_id = $_POST['user_id'] ?? 1;
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        json_response(['error' => 'Falten camps'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO articles (user_id, title, content) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $title, $content]);

    // incrementem comptador d'articles a Redis
    $redis->incr('stats_articles_creats');

    // Per fer-ho simple, redirigim al frontend
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        json_response(['status' => 'ok']);
    }
    exit;
}

if ($uri === '/api/stats' && $method === 'GET') {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalArticles = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    $visites = $redis->get('frontend_visites') ?: 0;

    json_response([
        'visites'  => (int)$visites,
        'usuaris'  => (int)$totalUsers,
        'articles' => (int)$totalArticles
    ]);
}

// Ruta no trobada
json_response(['error' => 'Not found'], 404);
