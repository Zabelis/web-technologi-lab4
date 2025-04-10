<?php
session_start();
require_once 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Ошибка: некорректный ID новости.");
}

$news_id = (int)$_GET['id'];

// Получаем новость по ID
$news_query = "
    SELECT n.title, n.content, n.published_at, u.first_name, u.last_name
    FROM news n
    JOIN users u ON n.author_id = u.user_id
    WHERE n.news_id = $news_id AND n.status_id = 2
";
$news_result = mysqli_query($conn, $news_query);

if (!$news_result || mysqli_num_rows($news_result) == 0) {
    die("Ошибка: новость не найдена или не опубликована.");
}

$news = mysqli_fetch_assoc($news_result);

require_once 'header.php';
?>

<div class="news-item-container">
    <h1><?= htmlspecialchars($news['title']) ?></h1>
    <div class="news-meta">
        <span><?= htmlspecialchars($news['published_at']) ?></span> | 
        <span>Автор: <?= htmlspecialchars($news['first_name'] . ' ' . $news['last_name']) ?></span>
    </div>
    <hr>
    <div class="news-content">
        <?= $news['content'] ?> <!-- Позволяем HTML-теги -->
    </div>
</div>

<style>
.news-item-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #222;
    color: #fff;
    border-radius: 5px;
}

h1 {
    font-size: 28px;
    text-align: center;
    margin-bottom: 10px;
}

.news-meta {
    text-align: center;
    font-size: 14px;
    color: #bbb;
    margin-bottom: 15px;
}

hr {
    border: 1px solid #444;
    margin-bottom: 15px;
}

.news-content {
    font-size: 16px;
    line-height: 1.6;
}

.news-content h2, .news-content h3 {
    color: #e04e29;
    margin-top: 20px;
}

.news-content p {
    margin-bottom: 15px;
}

.news-content img {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
    margin: 10px 0;
}

.news-content ul, .news-content ol {
    padding-left: 20px;
    margin-bottom: 15px;
}

.news-content a {
    color: #e04e29;
    text-decoration: underline;
}

.news-content a:hover {
    text-decoration: none;
}
</style>

<?php require_once 'footer.php'; ?>
