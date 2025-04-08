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

// Обработка отправки формы для обновления новости
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Получаем данные из формы
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $status_id = isset($_POST['status_id']) ? $_POST['status_id'] : null;
    $image_path = mysqli_real_escape_string($conn, $_POST['image_path']);
    $news_id = $_GET['news_id'];
    // Проверка, что статус был выбран
    if (empty($status_id)) {
        die("Ошибка: не выбран статус новости.");
    }

    // Проверяем, нужно ли обновить дату публикации
    $update_date_query = "";
    if ($status_id == 1) { // Статус "Опубликован" (например, статус с id = 1)
        $update_date_query = ", published_at = NOW()"; // Обновляем дату публикации
    }

    // Обновляем данные в таблице news
    $update_query = "
        UPDATE news
        SET title = '$title', content = '$content', status_id = $status_id, image_path = '$image_path' $update_date_query, updated_at = NOW()
        WHERE news_id = $news_id
    ";

    if (mysqli_query($conn, $update_query)) {
        echo "<div class='notification success'>Новость успешно обновлена.</div>";
    } else {
        echo $update_query;
        echo "<div class='notification error'>Ошибка при обновлении новости: " . mysqli_error($conn) . "</div>";
    }
}

// Получаем новость по ID
$news_id = $_GET['news_id'];
$news_query = "
    SELECT n.news_id, n.title, n.content, n.published_at, n.updated_at, ns.status_name, n.image_path, n.status_id
    FROM news n
    JOIN news_status ns ON n.status_id = ns.status_id
    WHERE n.news_id = $news_id
";
$news_result = mysqli_query($conn, $news_query);

if (!$news_result) {
    die("Ошибка запроса: " . mysqli_error($conn));
}

$news = mysqli_fetch_assoc($news_result);

require_once 'header.php';
?>

<div class="news_container">
    <!-- Кнопка "Вернуться к списку статей" -->
    <a href="news_list.php" class="back_button">Вернуться к списку статей</a>

    <h2>Редактирование новости</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Заголовок новости:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($news['title']) ?>" required placeholder="Введите заголовок">

        <label>Статус:</label>
        <select name="status_id" required>
            <option value="">Выберите статус</option>
            <?php
            $status_query = "SELECT status_id, status_name FROM news_status";
            $status_result = mysqli_query($conn, $status_query);
            while ($status = mysqli_fetch_assoc($status_result)): ?>
                <option value="<?= $status['status_id'] ?>" <?= $status['status_id'] == $news['status_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($status['status_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Текст новости:</label>
        <textarea name="content" id="editor" required><?= htmlspecialchars($news['content']) ?></textarea>

        <label>Путь к изображению:</label>
        <input type="text" name="image_path" value="<?= htmlspecialchars($news['image_path']) ?>" placeholder="Например, images/news.jpg">

        <button type="submit">Сохранить новость</button>
    </form>
</div>

<!-- Подключаем легковесный редактор Trumbowyg -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.25.1/ui/trumbowyg.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.25.1/trumbowyg.min.js"></script>
<script>
    $(document).ready(function () {
        $('#editor').trumbowyg(); // Инициализация редактора
    });
</script>

<style>
/* Стилизация формы */
.news_container {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    background: #222;
    color: #fff;
    border-radius: 5px;
}

label {
    display: block;
    margin-top: 10px;
}

input, select, textarea {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border-radius: 5px;
    border: none;
    background: #333;
    color: #fff;
}

select {
    background: #333 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M7 10l5 5 5-5H7z"/></svg>') no-repeat right 10px center;
    background-size: 16px;
}

button {
    margin-top: 20px;
    padding: 10px 20px;
    background-color: #e04e29;
    border: none;
    color: white;
    border-radius: 5px;
    cursor: pointer;
}

button:hover {
    background-color: #c03e1e;
}

.trumbowyg-box {
    max-width: 100%;
    border: none !important; /* Убираем лишнюю границу */
}

.trumbowyg-button-pane {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
    gap: 5px;
    padding: 5px;
    border-bottom: none !important; /* Убираем лишнюю линию */
}

.trumbowyg-button-group {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.trumbowyg-button-pane button {
    flex: 1 1 auto;
    min-width: 36px;
}

/* Убираем странную линию между кнопками */
.trumbowyg-button-pane::after {
    display: none !important;
}

/* Делаем поле редактора белым, а текст в нем черным */
.trumbowyg-editor {
    background: #fff !important;
    color: #000 !important;
}

.notification {
    padding: 10px;
    margin-top: 10px;
    border-radius: 5px;
    text-align: center;
}

.notification.success {
    background: #28a745;
    color: #fff;
}

.notification.error {
    background: #dc3545;
    color: #fff;
}

/* Стили для кнопки "Вернуться к списку статей" */
.back_button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #e04e29;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-bottom: 20px;
}

.back_button:hover {
    background-color: #c03e1e;
}
</style>

<?php require_once 'footer.php'; ?>
