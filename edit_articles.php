<?php
session_start();
require_once 'db.php';

// Проверка, что пользователь авторизован и имеет роль редактора
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем роль пользователя
$role_query = "SELECT role_id FROM users WHERE user_id = $user_id";
$role_result = mysqli_query($conn, $role_query);
$role = mysqli_fetch_assoc($role_result)['role_id'];
if ($role != 2) { // Если роль не "editor"
    header("Location: index.php"); // Перенаправляем на главную
    exit;
}

// Получаем список новостей, созданных этим редактором
$news_query = "
    SELECT n.news_id, n.title, n.content, n.published_at, n.updated_at, ns.status_name, n.image_path
    FROM news n
    JOIN news_status ns ON n.status_id = ns.status_id
    WHERE n.author_id = $user_id
    ORDER BY n.updated_at DESC
";
$news_result = mysqli_query($conn, $news_query);

if (!$news_result) {
    die("Ошибка запроса: " . mysqli_error($conn));
}

require_once 'header.php';
?>

<div class="news-edit-container">
    <h2>Редактирование новостей</h2>
    
    <?php if (mysqli_num_rows($news_result) > 0): ?>
        <div class="news-list">
            <?php while ($news = mysqli_fetch_assoc($news_result)): ?>
                <div class="news-card">
                    <!-- Проверка на наличие картинки -->
                    <?php if (!empty($news['image_path'])): ?>
                        <img src="<?= htmlspecialchars($news['image_path']) ?>" alt="Image" class="news-image">
                    <?php else: ?>
                        <div class="no-image">Нет изображения</div>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($news['title']) ?></h3>
                    <p><strong>Статус:</strong> <?= htmlspecialchars($news['status_name']) ?></p>
                    <p><strong>Опубликовано:</strong> 
                        <?php 
                        // Проверка на статус Черновик или Архив
                        if ($news['status_name'] == 'Черновик' || $news['status_name'] == 'Архив') {
                            echo '-';
                        } else {
                            echo date("d-m-Y H:i", strtotime($news['published_at']));
                        }
                        ?>
                    </p>
                    <p><strong>Обновлено:</strong> <?= date("d-m-Y H:i", strtotime($news['updated_at'])) ?></p>
                    <p><a href="edit_news.php?news_id=<?= $news['news_id'] ?>" class="edit-link">Редактировать статью</a></p>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>У вас нет созданных новостей.</p>
    <?php endif; ?>
</div>

<style>
.news-edit-container {
    max-width: 900px;
    margin: auto;
    padding: 20px;
}
.news-list {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.news-card {
    width: 300px;
    padding: 15px;
    background: #1c1c1c;
    border-radius: 8px;
    color: white;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Добавлена тень для выделения */
    transition: transform 0.3s ease;
}

.news-card:hover {
    transform: translateY(-5px); /* Эффект подъема карточки при наведении */
}

.news-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 5px;
    margin-bottom: 10px;
}

.no-image {
    background-color: #444;
    color: white;
    padding: 10px;
    text-align: center;
    border-radius: 5px;
    margin-bottom: 10px;
    font-style: italic;
}

.news-card h3 {
    margin: 10px 0;
    font-size: 18px;
    color: #e04e29;
}

.news-card p {
    font-size: 14px;
    margin: 5px 0;
}

.edit-link {
    color: #e04e29;
    text-decoration: none;
    font-weight: bold;
}

.edit-link:hover {
    text-decoration: underline;
}
</style>

<?php require_once 'footer.php'; ?>
