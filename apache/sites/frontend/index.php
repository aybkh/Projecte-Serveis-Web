<?php
// Config DB
$mysqlHost = 'mysql';
$mysqlDb   = getenv('MYSQL_DATABASE') ?: 'serveis';
$mysqlUser = getenv('MYSQL_USER') ?: 'admin';
$mysqlPass = getenv('MYSQL_PASSWORD') ?: 'A123456@';

// Redis
$redisHost = 'redis';
$redisPort = 6379;

// Visites amb Redis
$visites = 0;
$redisError = null;
try {
    $redis = new Redis();
    $redis->connect($redisHost, $redisPort);
    $visites = $redis->incr('frontend_visites');
} catch (Exception $e) {
    $redisError = $e->getMessage();
}

// Articles MySQL
$articles = [];
$dbError = null;
try {
    $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8mb4", $mysqlUser, $mysqlPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT a.id, a.title, a.content, a.published_at, u.username 
                         FROM articles a 
                         JOIN users u ON a.user_id = u.id 
                         ORDER BY a.published_at DESC 
                         LIMIT 5");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>frontend.local – Projecte Final</title>
<style>
    body{margin:0;font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;background:linear-gradient(135deg,#141e30,#243b55);color:#fff;}
    .wrap{max-width:1000px;margin:40px auto;padding:30px;background:rgba(255,255,255,0.05);border-radius:20px;backdrop-filter:blur(10px);box-shadow:0 15px 40px rgba(0,0,0,0.5);}
    h1{margin-top:0;font-size:2.4rem;}
    .stats,.articles,.form{margin-top:25px;padding:20px;border-radius:15px;background:rgba(0,0,0,0.3);}
    .stat-number{font-size:2rem;font-weight:bold;}
    .article{padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.1);}
    label{display:block;margin-top:10px;}
    input[type=text],textarea{width:100%;padding:8px;border-radius:8px;border:none;margin-top:4px;}
    button{margin-top:15px;padding:10px 18px;border-radius:8px;border:none;background:#00c853;color:#fff;font-weight:bold;cursor:pointer;}
</style>
</head>
<body>
<div class="wrap">
    <h1>frontend.local – Projecte Final</h1>
    <p>Autor: <strong>Ayoub Khalifi</strong></p>

    <div class="stats">
        <h2>📊 Estadístiques</h2>
        <?php if ($redisError): ?>
            <p style="color:#ff8a80">Error Redis: <?= htmlspecialchars($redisError) ?></p>
        <?php else: ?>
            <p>Nombre de visites (Redis): <span class="stat-number"><?= (int)$visites ?></span></p>
        <?php endif; ?>
    </div>

    <div class="articles">
        <h2>📰 Últims 5 articles (MySQL)</h2>
        <?php if ($dbError): ?>
            <p style="color:#ff8a80">Error MySQL: <?= htmlspecialchars($dbError) ?></p>
        <?php elseif (!$articles): ?>
            <p>No hi ha articles.</p>
        <?php else: ?>
            <?php foreach ($articles as $a): ?>
                <div class="article">
                    <h3><?= htmlspecialchars($a['title']) ?></h3>
                    <small>Autor: <?= htmlspecialchars($a['username']) ?> – <?= $a['published_at'] ?></small>
                    <p><?= nl2br(htmlspecialchars($a['content'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="form">
        <h2>➕ Crear nou article</h2>
        <form action="https://api.local/api/articles" method="post">
            <!-- de moment user_id fix 1 per simplificar -->
            <input type="hidden" name="user_id" value="1">
            <label>Títol
                <input type="text" name="title" required>
            </label>
            <label>Contingut
                <textarea name="content" rows="4" required></textarea>
            </label>
            <button type="submit">Enviar</button>
        </form>
        <p style="margin-top:10px;font-size:0.9rem">El formulari envia el POST a <code>api.local/api/articles</code>.</p>
    </div>
</div>
</body>
</html>
