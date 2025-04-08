<?php
session_start();
require_once 'db.php';

require_once 'header.php';

// Получаем все опубликованные новости
$news_query = "
    SELECT n.news_id, n.title, n.image_path, n.published_at, u.first_name, u.last_name
    FROM news n
    JOIN users u ON n.author_id = u.user_id
    WHERE n.status_id = 2
    ORDER BY n.published_at DESC
";
$news_result = mysqli_query($conn, $news_query);
?>

<div class="news-container">
    <h2>Новости</h2>
    <div class="news-grid">
        <?php while ($news = mysqli_fetch_assoc($news_result)): ?>
            <div class="news-card">
                <img src="<?= !empty($news['image_path']) ? htmlspecialchars($news['image_path']) : 'logo.jpg' ?>" alt="Изображение новости">
                <div class="news-content">
                    <h3><?= htmlspecialchars($news['title']) ?></h3>
                    <p class="author">Автор: <?= htmlspecialchars($news['first_name'] . ' ' . $news['last_name']) ?></p>
                    <a href="news_item.php?id=<?= $news['news_id'] ?>" class="read-more">Читать далее</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<style>
.news-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: #222;
    color: #fff;
    border-radius: 5px;
}

h2 {
    text-align: center;
}

.news-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
}

.news-card {
    width: 300px;
    background: #333;
    border-radius: 10px;
    overflow: hidden;
    text-align: center;
    transition: transform 0.3s ease;
}

.news-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.news-card .news-content {
    padding: 15px;
}

.news-card h3 {
    font-size: 18px;
    margin-bottom: 10px;
}

.news-card .author {
    font-size: 14px;
    color: #bbb;
    margin-bottom: 10px;
}

.read-more {
    display: inline-block;
    padding: 8px 15px;
    background: #e04e29;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s ease;
}

.read-more:hover {
    background: #c03e1e;
}

.news-card:hover {
    transform: translateY(-5px);
}
</style>

<?php require_once 'footer.php'; ?>
