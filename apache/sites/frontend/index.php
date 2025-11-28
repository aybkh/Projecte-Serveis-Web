<?php
$mysql_host = getenv('MYSQL_HOST');
$mysql_user = getenv('MYSQL_USER');
$mysql_pass = getenv('MYSQL_PASSWORD');
$mysql_db = getenv('MYSQL_DATABASE');
$redis_host = getenv('REDIS_HOST');

// Connect to Redis
$redis = new Redis();
try {
    $redis->connect($redis_host, 6379);
    $visits = $redis->incr('visits');
} catch (Exception $e) {
    $visits = "Redis Error: " . $e->getMessage();
}

// Connect to MySQL
$mysqli = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = $mysqli->real_escape_string($_POST['title']);
    $content = $mysqli->real_escape_string($_POST['content']);
    $user_id = 1; // Hardcoded for now
    $sql = "INSERT INTO articles (user_id, title, content) VALUES ('$user_id', '$title', '$content')";
    $mysqli->query($sql);
    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch Articles
$result = $mysqli->query("SELECT * FROM articles ORDER BY published_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frontend - Projecte Final</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 900px; margin: 0 auto; padding: 40px; background-color: #f0f2f5; color: #333; }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 40px; }
        .container { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .stats { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); height: fit-content; }
        .stats h3 { margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 10px; }
        .stats p { font-size: 1.2em; }
        .article { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; transition: transform 0.2s; }
        .article:hover { transform: translateY(-2px); }
        .article h2 { margin-top: 0; color: #2c3e50; }
        .article small { color: #888; display: block; margin-top: 10px; }
        form { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        input, textarea { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: inherit; transition: border-color 0.3s; }
        input:focus, textarea:focus { border-color: #667eea; outline: none; }
        button { background: #667eea; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; transition: background 0.3s; }
        button:hover { background: #5a6fd1; }
        .create-article h3 { margin-top: 0; color: #2c3e50; }
    </style>
</head>
<body>
    <h1>Projecte Final Integration</h1>
    
    <div class="container">
        <div class="main-content">
            <div class="create-article">
                <h3>Create New Article</h3>
                <form method="POST">
                    <input type="text" name="title" placeholder="Article Title" required>
                    <textarea name="content" placeholder="Write something amazing..." rows="5" required></textarea>
                    <button type="submit">Publish Article</button>
                </form>
            </div>

            <h3 style="margin-top: 30px;">Latest Articles</h3>
            <?php if ($result): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="article">
                        <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                        <p><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                        <small>Published: <?php echo $row['published_at']; ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No articles found or database error.</p>
            <?php endif; ?>
        </div>

        <div class="sidebar">
            <div class="stats">
                <h3>Live Statistics</h3>
                <p>Total Page Visits: <strong><?php echo $visits; ?></strong></p>
                <p>Database Status: <strong><?php echo $mysqli->ping() ? 'Connected' : 'Disconnected'; ?></strong></p>
                <p>Redis Status: <strong><?php echo $redis->ping() ? 'Connected' : 'Disconnected'; ?></strong></p>
            </div>
        </div>
    </div>
</body>
</html>
