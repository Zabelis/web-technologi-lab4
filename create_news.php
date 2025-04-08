<?php
session_start();
require_once 'db.php'; // Подключение к базе данных
require_once 'header.php'; // Подключение шапки сайта

// Проверка авторизации пользователя
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Если пользователь не авторизован
    exit;
}

// Проверка роли пользователя в базе данных
$user_id = $_SESSION['user_id'];
$query = "SELECT role_id FROM users WHERE user_id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Разрешены только роли editor (1) и admin (2)
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: index.php');
    exit;
}

// Получаем список статусов новости
$statusQuery = "SELECT * FROM news_status";
$statusResult = mysqli_query($conn, $statusQuery);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $status_id = (int)$_POST['status_id'];

    // Загрузка изображения
    $image_path = mysqli_real_escape_string($conn, $_POST['image_path']);


    // Устанавливаем дату публикации, если статус "Опубликовано" (ID = 2)
    $published_at = date('Y-m-d H:i:s');

    // Вставка в базу
    $query = "INSERT INTO news (title, content, author_id, published_at, updated_at, status_id, image_path) 
              VALUES ('$title', '$content', $user_id, '$published_at', NOW(), $status_id, '$image_path')";

    if (mysqli_query($conn, $query)) {
        echo '<div class="notification succes">Новость успешно создана!</div>';
    } else {
        echo '<div class="notification error">Ошибка: ' . mysqli_error($conn) . '</div>';
    }
}
?>

<div class="news_container">
    <h2>Создание новости</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Заголовок новости:</label>
        <input type="text" name="title" required placeholder="Введите заголовок">

        <label>Статус:</label>
        <select name="status_id" required>
            <option value="">Выберите статус</option>
            <?php while ($status = mysqli_fetch_assoc($statusResult)): ?>
                <option value="<?= $status['status_id'] ?>"><?= htmlspecialchars($status['status_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Текст новости:</label>
        <textarea name="content" id="editor" required></textarea>

        <label>Путь к изображению:</label>
		<input type="text" name="image_path" placeholder="Например, images/news.jpg">




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
	
	document.getElementById("imageUpload").addEventListener("change", function() {
    var fileName = this.files.length > 0 ? this.files[0].name : "Файл не выбран";
    document.getElementById("fileName").textContent = fileName;
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

input {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border-radius: 5px;
    border: none;
    background: #333;
    color: #fff;
}

select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border-radius: 5px;
    border: none;
    background: #333 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M7 10l5 5 5-5H7z"/></svg>') no-repeat right 10px center;
    background-size: 16px;
    color: #fff;
    appearance: none; /* Убираем стандартный стиль */
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
}

/* При фокусе сохраняем кастомную стрелку */
select:focus {
    outline: none;
    background-color: #444; /* Немного изменим фон */
}


button {
    width: 100%;
    padding: 10px;
    margin-top: 15px;
    background: #ff5722;
    color: #fff;
    border: none;
    cursor: pointer;
    font-size: 16px;
    border-radius: 5px;
}

button:hover {
    background: #e64a19;
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




</style>

<?php require_once 'footer.php'; ?>
