<?php
// Подключаем файлы
include('header.php');
include('db.php');
session_start();

// Запрос на получение 4 случайных игр для миникарточек
$gamesQuery = "SELECT * FROM games ORDER BY RAND() LIMIT 4";
$gamesResult = mysqli_query($conn, $gamesQuery);

if ($gamesResult === false) {
    die("Ошибка при выполнении запроса: " . mysqli_error($conn));
}

$games = mysqli_fetch_all($gamesResult, MYSQLI_ASSOC);

// Получаем путь к изображениям и название игр для вывода
$gameImages = [];
foreach ($games as $game) {
    $gameImages[] = [
        'id' => $game['game_id'],
        'title' => $game['title'],
        'cover_image' => $game['cover_image_path']
    ];
}

// Проверка на авторизацию и получение списка игр в избранном
$wishlist_game_ids = [];
if (isset($_SESSION['user_id'])) {
    $wishlistQuery = "SELECT game_id FROM favorites WHERE user_id = " . (int)$_SESSION['user_id'];
    $wishlistResult = mysqli_query($conn, $wishlistQuery);

    if ($wishlistResult === false) {
        die("Ошибка при выполнении запроса на избранное: " . mysqli_error($conn));
    }

    // Собираем список игр в избранном
    while ($row = mysqli_fetch_assoc($wishlistResult)) {
        $wishlist_game_ids[] = $row['game_id'];
    }
}
?>



<!-- Основной контейнер -->
<div class="main-container">
    <!-- Главная игра -->
    <div class="featured-game-wrapper">
        <div class="featured-game">
            <img id="main-game-image" src="covers_main_page/<?php echo $gameImages[0]['cover_image']; ?>" alt="<?php echo $gameImages[0]['title']; ?>">
            <div class="game-title">
                <h2><?php echo $gameImages[0]['title']; ?></h2>
                <a href="game.php?id=<?php echo $gameImages[0]['id']; ?>" class="btn-main">Перейти на страницу игры</a>
            </div>
        </div>
    </div>


    <!-- Мини-карточки -->
    <div class="mini-game-cards-container">
        <?php foreach ($gameImages as $index => $game): ?>
            <div class="mini-game-card" onclick="changeMainImage(<?php echo $index; ?>)">
                <img src="covers_main_page/<?php echo $game['cover_image']; ?>" alt="<?php echo $game['title']; ?>">
            </div>
        <?php endforeach; ?>
    </div>
</div>




<?php
// Получаем 4 самых новых игр
$result = mysqli_query($conn, "SELECT * FROM games ORDER BY release_date DESC LIMIT 4");
$new_games = mysqli_fetch_all($result, MYSQLI_ASSOC);


?>

<section class="discover-section">
    <h2>Откройте для себя что-то новое: Самые свежие релизы</h2>
    <div class="discover-grid">
        <?php foreach ($new_games as $game): ?>
            <div class="discover-card" onclick="location.href='game.php?id=<?= $game['game_id'] ?>'">
                <div class="discover-image-container">
                    <img src="<?= $game['cover_image_path'] ?>" alt="<?= htmlspecialchars($game['title']) ?>">
                    <?php $isInWishlist = in_array($game['game_id'], $wishlist_game_ids); ?>
					<button
						class="wishlist-btn <?= $isInWishlist ? 'active' : '' ?>"
						data-game-id="<?= $game['game_id'] ?>"
						onclick="event.stopPropagation(); handleWishlist(this)">
						<?= $isInWishlist ? '✔ В избранном' : '♡ В список желаемого' ?>
					</button>

                </div>
                <div class="discover-info">
                    <h3><?= htmlspecialchars($game['title']) ?></h3>
                    <p><?= $game['price'] == 0 ? 'Бесплатно' : number_format($game['price'], 2) . ' ₽' ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

	<div class="store-description">
		<h2>Добро пожаловать в GameStore – мир лучших игр!</h2>
		<p>
			GameStore – это место, где каждый геймер найдет что-то по душе! У нас представлены самые свежие новинки, легендарные хиты и эксклюзивные предложения. 
			Покупайте игры по выгодным ценам, добавляйте их в список желаемого и наслаждайтесь безграничными возможностями цифрового гейминга.
		</p>
		<p>
			Мы предлагаем удобную систему поиска, подборки по жанрам, рейтингам и рекомендациям. Будьте в курсе последних релизов и акций, 
			а также следите за игровыми новостями прямо на нашем сайте!
		</p>
	</div>

<div id="authModal" class="modal-backdrop" onclick="closeModal()">
  <div class="modal-window" onclick="event.stopPropagation()">
    <h3>Вход не выполнен</h3>
    <p>Чтобы добавить игру в список желаемого, сначала авторизуйтесь.</p>
    <a href="login.php" class="modal-btn">Войти</a>
    <button onclick="closeModal()" class="modal-close">Закрыть</button>
  </div>
</div>


<?php include 'footer.php'; ?>


<!-- Скрипт -->
<script>
    const games = <?php echo json_encode($gameImages); ?>;
    let gameIndex = 0;
    let intervalId;

    function changeMainImage(index) {
        const game = games[index];
        document.getElementById('main-game-image').src = "covers_main_page/" + game.cover_image;
        document.querySelector('.game-title h2').textContent = game.title;
        document.querySelector('.game-title a').href = "game.php?id=" + game.id;

        gameIndex = index; // Обновляем индекс текущей игры
        resetAutoChange(); // Перезапускаем интервал
    }

    function changeRandomImage() {
        gameIndex = (gameIndex + 1) % games.length;
        changeMainImage(gameIndex);
    }

    function resetAutoChange() {
        clearInterval(intervalId); // Останавливаем старый таймер
        intervalId = setInterval(changeRandomImage, 7000); // Запускаем новый
    }

    // Первый запуск
    window.onload = () => {
        intervalId = setInterval(changeRandomImage, 7000);
    };
	
function handleWishlist(button) {
    const gameId = button.getAttribute('data-game-id');

    <?php if (!isset($_SESSION['user_id'])): ?>
        showModal();
        return;
    <?php endif; ?>

    fetch('add_to_wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'game_id=' + gameId
    })
    .then(res => res.text())
    .then(response => {
        if (response.includes('Добавлено')) {
            // Если игра добавлена в избранное
            button.classList.add('active');
            button.textContent = '✔ В избранном';
        } else if (response.includes('удалена')) {
            // Если игра удалена из избранного
            button.classList.remove('active');
            button.textContent = '♡ В список желаемого';
        } else {
            alert(response); // Если ошибка
        }
    });
}

	function showModal() {
		document.getElementById('authModal').style.display = 'flex';
	}

	function closeModal() {
		document.getElementById('authModal').style.display = 'none';
	}
</script>
