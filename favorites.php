<?php
session_start();
require_once 'db.php'; // Подключение к БД
require_once 'header.php';

// Проверка на авторизацию
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу входа, если пользователь не авторизован
    exit;
}

// Получаем список избранных игр
$favorites_game_ids = [];
$favoriteQuery = "SELECT game_id FROM favorites WHERE user_id = " . (int)$_SESSION['user_id'];
$favoriteResult = mysqli_query($conn, $favoriteQuery);
if ($favoriteResult === false) {
    die("Ошибка при выполнении запроса на избранное: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($favoriteResult)) {
    $favorites_game_ids[] = $row['game_id'];
}

// Если массив $favorites_game_ids пуст, не выполняем запрос
if (empty($favorites_game_ids)) {
    echo '<style>.emptywish{
	text-align: center;
    font-size: 18px;
    color: #888;
    padding: 20px;
}</style><div class="games-page container">
	<h2>Список желаемого</h2>
	<p class="emptywish">У вас нет избранных игр.</p>
	</div>';
    exit;
}

// Получаем все игры, которые находятся в избранном
$sql = "SELECT * FROM games WHERE game_id IN (" . implode(",", $favorites_game_ids) . ")";
$favorites_games = mysqli_query($conn, $sql);
if (!$favorites_games) {
    die("Ошибка запроса: " . mysqli_error($conn));
}
?>

<div class="games-page container">
    <h2>Список желаемого</h2>

    <?php if (mysqli_num_rows($favorites_games) > 0): ?>
        <div class="game-grid">
            <?php while ($game = mysqli_fetch_assoc($favorites_games)): ?>
                <div class="game-card" onclick="location.href='game.php?id=<?= $game['game_id'] ?>'">
                    <div class="game-image-container">
                        <img src="<?= $game['cover_image_path'] ?>" alt="<?= htmlspecialchars($game['title']) ?>">
                    </div>
                    <div class="game-info">
                        <h3><?= htmlspecialchars($game['title']) ?></h3>
                        <p><?= $game['price'] == 0 ? 'Бесплатно' : number_format($game['price'], 2) . ' ₽' ?></p>
                        <button class="wishlist-btn active"
                                data-game-id="<?= $game['game_id'] ?>"
                                onclick="event.stopPropagation(); removeFromFavorites(this)">
                            ✔ В избранном
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<div id="notification-container"></div>

<?php require_once 'footer.php'; ?>

<script>
function removeFromFavorites(button) {
    const gameId = button.getAttribute('data-game-id');

    <?php if (!isset($_SESSION['user_id'])): ?>
        showModal();
        return;
    <?php endif; ?>

    // Отправляем данные для удаления игры из избранного
    fetch('remove_from_favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'game_id=' + gameId
    })
    .then(res => res.text())
    .then(response => {
        if (response.includes('удалено')) {
            button.closest('.game-card').remove();  // Удаляем карточку игры из DOM
            showNotification(response);
        } else {
            showNotification(response);  // Показываем уведомление с ошибкой
        }
    });
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.classList.add('notification');
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showModal() {
    document.getElementById('authModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('authModal').style.display = 'none';
}
</script>