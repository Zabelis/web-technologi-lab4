<?php
session_start();
require_once 'db.php'; // Подключение к БД
require_once 'header.php';

// Инициализация пустых массивов для избранного и корзины
$wishlist_game_ids = [];
$cart_game_ids = [];

// Проверка на авторизацию и получение списка игр в избранном
if (isset($_SESSION['user_id'])) {
    // Избранное
    $wishlistQuery = "SELECT game_id FROM favorites WHERE user_id = " . (int)$_SESSION['user_id'];
    $wishlistResult = mysqli_query($conn, $wishlistQuery);
    if ($wishlistResult === false) {
        die("Ошибка при выполнении запроса на избранное: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($wishlistResult)) {
        $wishlist_game_ids[] = $row['game_id'];
    }

    // Корзина
    $cartQuery = "SELECT game_id FROM cart WHERE user_id = " . (int)$_SESSION['user_id'];
    $cartResult = mysqli_query($conn, $cartQuery);
    if ($cartResult === false) {
        die("Ошибка при выполнении запроса на корзину: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($cartResult)) {
        $cart_game_ids[] = $row['game_id'];
    }
}

// Получаем данные для фильтров
$query = "SELECT * FROM genres";
$genres = mysqli_query($conn, $query);

$query = "SELECT * FROM features";
$features = mysqli_query($conn, $query);

$query = "SELECT * FROM developers";
$developers = mysqli_query($conn, $query);

// Фильтрация
$conditions = [];
$params = [];

// Обработка GET параметров
if (!empty($_GET['search'])) {
    $conditions[] = "games.title LIKE '%" . mysqli_real_escape_string($conn, $_GET['search']) . "%'";
}

if (!empty($_GET['genre'])) {
    $conditions[] = "game_genres.genre_id = " . (int)$_GET['genre'];
}

if (!empty($_GET['feature'])) {
    $conditions[] = "game_features.feature_id = " . (int)$_GET['feature'];
}

if (!empty($_GET['developer'])) {
    $conditions[] = "games.developer_id = " . (int)$_GET['developer'];
}

// Создание основного SQL-запроса с фильтрацией
$sql = "
    SELECT DISTINCT games.*
    FROM games
    LEFT JOIN game_genres ON games.game_id = game_genres.game_id
    LEFT JOIN game_features ON games.game_id = game_features.game_id
    LEFT JOIN developers ON games.developer_id = developers.developer_id
";

if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$filtered_games = mysqli_query($conn, $sql);

// Получаем список купленных игр
$owned_game_ids = [];
if (isset($_SESSION['user_id'])) {
    $ownedQuery = "SELECT game_id FROM transactions WHERE user_id = " . (int)$_SESSION['user_id'];
    $ownedResult = mysqli_query($conn, $ownedQuery);
    while ($row = mysqli_fetch_assoc($ownedResult)) {
        $owned_game_ids[] = $row['game_id'];
    }
}
?>


<div class="games-page container">
    <h2>Все игры</h2>

    <form class="filter-form" method="GET">
        <input type="text" name="search" placeholder="Поиск по названию..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

        <select name="genre">
            <option value="">Жанр</option>
            <?php while ($genre = mysqli_fetch_assoc($genres)): ?>
                <option value="<?= $genre['genre_id'] ?>" <?= ($_GET['genre'] ?? '') == $genre['genre_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($genre['genre_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="feature">
            <option value="">Особенности</option>
            <?php while ($feature = mysqli_fetch_assoc($features)): ?>
                <option value="<?= $feature['feature_id'] ?>" <?= ($_GET['feature'] ?? '') == $feature['feature_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($feature['feature_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="developer">
            <option value="">Разработчик</option>
            <?php while ($dev = mysqli_fetch_assoc($developers)): ?>
                <option value="<?= $dev['developer_id'] ?>" <?= ($_GET['developer'] ?? '') == $dev['developer_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dev['developer_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Фильтровать</button>
		<!-- Кнопка сброса фильтров -->
        <a href="games.php" class="btn">Сбросить фильтры</a>
    </form>

<div class="game-section">
    <h2>Все игры:</h2>
    <div class="game-grid">
        <?php while ($game = mysqli_fetch_assoc($filtered_games)): ?>
            <div class="game-card" onclick="location.href='game.php?id=<?= $game['game_id'] ?>'">
                <div class="game-image-container">
                    <img src="<?= $game['cover_image_path'] ?>" alt="<?= htmlspecialchars($game['title']) ?>">
                </div>
                <div class="game-info">
                    <h3><?= htmlspecialchars($game['title']) ?></h3>
                    <p><?= $game['price'] == 0 ? 'Бесплатно' : number_format($game['price'], 2) . ' ₽' ?></p>
                    
                    <!-- Кнопка избранного -->
                    <?php $isInWishlist = in_array($game['game_id'], $wishlist_game_ids); ?>
                    <button class="wishlist-btn <?= $isInWishlist ? 'active' : '' ?>"
                            data-game-id="<?= $game['game_id'] ?>"
                            onclick="event.stopPropagation(); handleWishlist(this)">
                        <?= $isInWishlist ? '✔ В избранном' : '♡ В список желаемого' ?>
                    </button>

                    <!-- Кнопка корзины -->
                    <?php if (in_array($game['game_id'], $owned_game_ids)): ?>
                        <button class="disabled-to-cart-btn" disabled>Игра уже куплена</button>
                    <?php else: ?>
                        <button class="add-to-cart-btn"
                                onclick="event.stopPropagation(); addToCart(this)"
                                data-game-id="<?= $game['game_id'] ?>">
                            <?= in_array($game['game_id'], $cart_game_ids) ? 'Удалить из корзины' : 'Добавить в корзину' ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

</div>

<!-- Модальное окно авторизации -->
<div id="authModal" class="modal-backdrop" style="display: none;">
    <div class="modal-window" onclick="event.stopPropagation()">
        <h3>Вход не выполнен</h3>
        <p>Чтобы добавить игру в список желаемого или корзину, сначала авторизуйтесь.</p>
        <a href="login.php" class="modal-btn">Войти</a>
        <button onclick="closeModal()" class="modal-close">Закрыть</button>
    </div>
</div>

<!-- Уведомления будут добавляться сюда -->
<div id="notification-container"></div>

<!-- Кнопка корзины -->
<div class="cart-button" onclick="openCart()">
    <img src="cart-icon.png" alt="Корзина">
</div>
<?php require_once 'footer.php'; ?>

<script>
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



function addToCart(button) {
    const gameId = button.getAttribute('data-game-id');

    <?php if (!isset($_SESSION['user_id'])): ?>
        showModal();
        return;
    <?php endif; ?>
  
    // Отправляем данные как x-www-form-urlencoded
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'game_id=' + gameId // Формируем строку с данными в формате URL
    }).then(response => response.json())
      .then(data => {
		  
          if (data.status === 'success') {
              button.textContent = data.action === 'add' ? 'Удалить из корзины' : 'Добавить в корзину';
              showNotification(data.message);  // Показываем уведомление с сообщением
          } else {
              showNotification(data.message);  // Показываем уведомление с ошибкой
          }
      });
}



	// Функция для отображения маленькой плашки уведомления
	function showNotification(message) {
		// Создаем элемент для уведомления
		const notification = document.createElement('div');
		notification.classList.add('notification');
		notification.textContent = message;

		// Добавляем уведомление в body
		document.body.appendChild(notification);

		// Убираем уведомление через 3 секунды
		setTimeout(() => {
			notification.classList.add('hide');
			setTimeout(() => notification.remove(), 300); // Удаляем уведомление после анимации
		}, 3000);
	}


	function showModal() {
		document.getElementById('authModal').style.display = 'flex';
	}

	function closeModal() {
		document.getElementById('authModal').style.display = 'none';
	}
	
	function openCart() {
    // Здесь вы можете открыть модальное окно с корзиной или перенаправить пользователя на страницу корзины
    window.location.href = 'cart.php';  // Например, перенаправление на страницу корзины
}
</script>

<style>
.disabled-to-cart-btn {
    background: gray;
	cursor: not-allowed;
	
}
.disabled-to-cart-btn:hover {
    background: gray;
	cursor: not-allowed;
}
</style>
