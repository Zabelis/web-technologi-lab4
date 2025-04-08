<?php
session_start();
require_once 'db.php'; // Подключение к базе данных
require_once 'header.php'; // Шаблон хедера

// Проверяем, передан ли game_id
if (!isset($_GET['id'])) {
    die("Идентификатор игры не передан.");
}

$game_id = (int)$_GET['id'];

// Запрос на получение информации об игре
$query = "SELECT * FROM games WHERE game_id = $game_id";
$gameResult = mysqli_query($conn, $query);
if (!$gameResult) {
    die("Ошибка при получении данных игры: " . mysqli_error($conn));
}

$game = mysqli_fetch_assoc($gameResult);
if (!$game) {
    die("Игра не найдена.");
}

// Получаем скриншоты из папки
$screenshot_folder = "{$game['screenshot_path']}"; 
$screenshots = glob($screenshot_folder . "*.jpg");

// Получаем жанры и их ID
$genresQuery = "SELECT genres.genre_name, genres.genre_id FROM genres
                 INNER JOIN game_genres ON genres.genre_id = game_genres.genre_id
                 WHERE game_genres.game_id = $game_id";
$genresResult = mysqli_query($conn, $genresQuery);

if (!$genresResult) {
    // Ошибка в запросе, выводим сообщение
    die("Ошибка при выполнении запроса жанров: " . mysqli_error($conn));
}

$genres = [];
while ($genre = mysqli_fetch_assoc($genresResult)) {
    $genres[] = $genre; // Сохраняем и название, и ID жанра
}


// Получаем особенности и их ID
$featuresQuery = "SELECT features.feature_name, features.feature_id FROM features
                  INNER JOIN game_features ON features.feature_id = game_features.feature_id
                  WHERE game_features.game_id = $game_id";
$featuresResult = mysqli_query($conn, $featuresQuery);

if (!$featuresResult) {
    // Ошибка в запросе, выводим сообщение
    die("Ошибка при выполнении запроса особенностей: " . mysqli_error($conn));
}

$features = [];
while ($feature = mysqli_fetch_assoc($featuresResult)) {
    $features[] = $feature; // Сохраняем и название, и ID особенности
}





// Получаем рейтинг игры
$ratingQuery = "SELECT AVG(rating) AS average_rating FROM reviews WHERE game_id = $game_id";
$ratingResult = mysqli_query($conn, $ratingQuery);
$rating = mysqli_fetch_assoc($ratingResult)['average_rating'];
$rating = round($rating); // Округляем до целого числа

// Проверяем, есть ли эта игра в корзине или в избранном
$isInWishlist = false;
$isInCart = false;
$isPurchased = false;
if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];

    // Проверка на избранное
    $wishlistQuery = "SELECT * FROM favorites WHERE user_id = $user_id AND game_id = $game_id";
    $wishlistResult = mysqli_query($conn, $wishlistQuery);
    if (mysqli_num_rows($wishlistResult) > 0) {
        $isInWishlist = true;
    }

    // Проверка на корзину
    $cartQuery = "SELECT * FROM cart WHERE user_id = $user_id AND game_id = $game_id";
    $cartResult = mysqli_query($conn, $cartQuery);
    if (mysqli_num_rows($cartResult) > 0) {
        $isInCart = true;
    }

    // Проверка на покупку
    $purchaseQuery = "SELECT * FROM transactions WHERE user_id = $user_id AND game_id = $game_id";
    $purchaseResult = mysqli_query($conn, $purchaseQuery);
    if (mysqli_num_rows($purchaseResult) > 0) {
        $isPurchased = true;
    }
}
?>

<div class="game-page container">
    <h1><?= htmlspecialchars($game['title']) ?></h1>

    <div class="game-details">
        <!-- Слайдшоу с изображениями -->
        <div class="game-slideshow">
            <div class="slideshow-container">
                <?php foreach ($screenshots as $index => $screenshot): ?>
                    <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                        <img src="<?= $screenshot ?>" alt="Screenshot <?= $index + 1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="prev" onclick="changeSlide(-1)">&#10094;</button>
            <button class="next" onclick="changeSlide(1)">&#10095;</button>
        </div>

        <!-- Блок для кнопок корзины и избранного под слайдшоу -->
        <div class="game-actions">
			<!-- Кнопка избранного -->
			<button class="wishlist <?= $isInWishlist ? 'active' : '' ?>"
					data-game-id="<?= $game['game_id'] ?>"
					onclick="event.stopPropagation(); handleWishlist(this)">
				<?= $isInWishlist ? '✔ В избранном' : '♡ В список желаемого' ?>
			</button>

			<!-- Кнопка корзины -->
			<?php if ($isPurchased): ?>
				<button class="disabled-to-cart" disabled>Игра уже куплена</button>
			<?php elseif ($isInCart): ?>
				<button class="remove-from-cart"
						onclick="event.stopPropagation(); removeFromCart(this)"
						data-game-id="<?= $game['game_id'] ?>">
					Удалить из корзины
				</button>
			<?php else: ?>
				<button class="add-to-cart"
						onclick="event.stopPropagation(); addToCart(this)"
						data-game-id="<?= $game['game_id'] ?>">
					Добавить в корзину
				</button>
			<?php endif; ?>
		</div>

    </div>

    <div class="game-description">
        <h3 class="logline"><?= nl2br(htmlspecialchars($game['logline'])) ?></h3>
        <hr>
		<h3 class="logline">Стоимость игры: <?=  $game['price'] == 0 ? 'Бесплатно' : number_format($game['price'], 2) . ' ₽' ?></h3>
		<hr>
		<!-- Жанры -->
		<div class="game-genres">
			<strong>Жанры:</strong>
			<div class="genre-box">
				<?php foreach ($genres as $genre): ?>
					<!-- Ссылка на страницу games.php с фильтрацией по ID жанра -->
					<span class="genre">
						<a href="games.php?search=&genre=<?= urlencode($genre['genre_id']) ?>"> <?= htmlspecialchars($genre['genre_name']) ?></a>
					</span>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Особенности -->
		<div class="game-features">
			<strong>Особенности:</strong>
			<div class="feature-box">
				<?php foreach ($features as $feature): ?>
					<!-- Ссылка на страницу games.php с фильтрацией по ID особенности -->
					<span class="feature">
						<a href="games.php?search=&feature=<?= urlencode($feature['feature_id']) ?>"> <?= htmlspecialchars($feature['feature_name']) ?></a>
					</span>
				<?php endforeach; ?>
			</div>
		</div>


        <hr>

        <div class="detailed-description">
            <h2>Описание:</h2>
            <?= nl2br(htmlspecialchars($game['description'])) ?>
        </div>
    </div>

    <div class="game-rating">
        <h2>Рейтинг:</h2>
        <div class="rating-stars_total">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= $i <= $rating ? 'filled' : '' ?>">&#9733;</span>
            <?php endfor; ?>
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
<?php if ($isPurchased && isset($_SESSION['user_id'])): ?>
<div class="game-rating-section">
    <div class="game-review">
        <h3>Нам важно знать ваше мнение! Оцените, пожалуйста, игру!</h3>
        <p>Это поможет другим игрокам определиться с выбором.</p>
        
        <div class="rating-stars" data-game-id="<?= $game_id ?>">
            <?php
            // Проверяем, оставлял ли пользователь отзыв
            $user_id = (int)$_SESSION['user_id'];
            $userRatingQuery = "SELECT rating FROM reviews WHERE game_id = $game_id AND user_id = $user_id";
            $userRatingResult = mysqli_query($conn, $userRatingQuery);
            $userRating = mysqli_fetch_assoc($userRatingResult)['rating'] ?? 0;
            
            for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= ($i <= $userRating) ? 'filled' : '' ?>" data-rating="<?= $i ?>">&#9733;</span>
            <?php endfor; ?>
        </div>
        <p id="review-status"><?= $userRating ? "Ваша оценка: $userRating/5" : "Поставьте свою оценку" ?></p>
    </div>
</div>
<?php endif; ?>



<script>
    // Слайдшоу
    let currentSlide = 0;

    function changeSlide(direction) {
        const slides = document.querySelectorAll('.slideshow-container .slide');
        currentSlide = (currentSlide + direction + slides.length) % slides.length;
        slides.forEach((slide, index) => {
            slide.classList.toggle('active', index === currentSlide);
        });
    }

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
        body: 'game_id=' + gameId
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

function removeFromCart(button) {
    const gameId = button.getAttribute('data-game-id');

    <?php if (!isset($_SESSION['user_id'])): ?>
        showModal();
        return;
    <?php endif; ?>
  
    fetch('remove_from_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'game_id=' + gameId
    }).then(response => response.text())
      .then(response => {
          if (response === 'Игра удалена из корзины') {
              button.textContent = 'Добавить в корзину';
              button.classList.remove('remove-from-cart');
              button.classList.add('add-to-cart');
              showNotification(response);
          } else {
              showNotification(response);
          }
      });
}

document.querySelectorAll(".rating-stars .star").forEach(star => {
    star.addEventListener("click", function () {
        const rating = parseInt(this.getAttribute("data-rating"), 10);
        const container = this.parentElement;
        const gameId = container.getAttribute("data-game-id");

        // Определяем текущий рейтинг
        const filledStars = container.querySelectorAll(".star.filled");
        const currentRating = filledStars.length ? parseInt(filledStars[filledStars.length - 1].getAttribute("data-rating"), 10) : 0;

        // Если клик по уже установленной оценке — сбрасываем рейтинг
        const newRating = (rating === currentRating) ? 0 : rating;

        fetch("submit_rating.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `game_id=${gameId}&rating=${newRating}`
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes("Оценка сохранена") || data.includes("Оценка удалена")) {
                document.getElementById("review-status").textContent = newRating ? `Ваша оценка: ${newRating}/5` : "Поставьте свою оценку";

                container.setAttribute("data-user-rating", newRating);

                container.querySelectorAll(".star").forEach(s => {
                    const sRating = parseInt(s.getAttribute("data-rating"), 10);
                    s.classList.toggle("filled", sRating <= newRating);
                });
            } else {
                alert("Ошибка: " + data);
            }
        });
    });
});






</script>

<style>
    /* Стили для страницы игры */
    .game-page {
        display: flex;
        flex-direction: column;
        align-items: center; /* Центрируем контент */
        margin: 0 auto;
        padding: 20px;
        max-width: 1100px;
        width: 100%;
    }

    .game-details {
        display: flex;
        justify-content: center; /* Центрируем блок с деталями игры */
        flex-wrap: wrap;
        gap: 20px;
        width: 100%; /* Даем максимальную ширину */
    }

	.game-slideshow {
		position: relative;
		width: 80%; /* Увеличиваем ширину */
		max-width: 1200px; /* Максимальная ширина для слайдшоу */
		height: 600px; /* Увеличиваем высоту */
		margin-bottom: 20px;
		overflow: hidden;
		display: flex;
		justify-content: center; /* Центрируем слайдшоу */
		align-items: center;
	}


    .slideshow-container .slide {
        display: none;
        width: 100%;
        height: 100%;
    }

    .slideshow-container .slide.active {
        display: block;
    }

    .slideshow-container img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    button.prev, button.next {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background-color: rgba(0, 0, 0, 0.5);
        color: white;
        font-size: 2rem;
        border: none;
        padding: 10px;
        cursor: pointer;
        opacity: 0.5;
        transition: opacity 0.3s;
        z-index: 10;
    }

    button.prev {
        left: 0;
    }

    button.next {
        right: 0;
    }

    button.prev:hover, button.next:hover {
        opacity: 1;
    }

    .game-actions {
        display: flex;
        justify-content: center; /* Центрируем кнопки */
        gap: 12px;
        margin-top: 20px;
        width: 100%; /* Чтобы кнопки располагались по центру */
    }

    .wishlist, .add-to-cart, .remove-from-cart, .disabled-to-cart {
        display: block;
        padding: 12px;
        background-color: #007bff;
        border: none;
        color: #fff;
        font-size: 1rem;
        border-radius: 8px;
        cursor: pointer;
        text-align: center;
    }

    .wishlist.active {
        background-color: #28a745;
    }

    .disabled-to-cart {
        background-color: #ccc;
        cursor: not-allowed;
    }

    .wishlist:hover, .add-to-cart:hover, .remove-from-cart:hover {
        background-color: #0056b3;
    }

    /* Стиль для жанров и особенностей */
    .game-genres, .game-features {
        margin-bottom: 20px;
    }

    .genre-box, .feature-box {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 8px;
    }

    .genre, .feature {
        background-color: #2c2c2c;
        color: #fff;
        padding: 6px 12px;
        border-radius: 12px;
        font-size: 0.9rem;
    }

    /* Стиль для рейтинга */
    .game-rating .rating-stars_total {
        display: flex;
        gap: 8px;
    }

    .rating-stars_total .star {
        font-size: 32px;
    }

    .rating-stars_total .star.filled {
        color: gold;
    }
	
	.game-review {
		margin-top: 30px;
		text-align: center;
	}

	.rating-stars {
		display: flex;
		justify-content: center;
		gap: 5px;
		cursor: pointer;
		font-size: 32px;
	}

	.rating-stars .star {
		color: gray;
		transition: color 0.2s;
	}

	.rating-stars .star.filled {
		color: gold;
	}
	
	.game-rating-section {
		background: #222; /* Темный фон */
		padding: 20px;
		border-radius: 10px;
		color: white;
		text-align: center;
		margin-top: 30px;
	}

	a {
		color: white; /* Белый цвет текста */
		text-decoration: none; /* Убираем подчеркивание */
	}

	a:hover {
		color: #ccc; /* Светлый цвет при наведении */
	}

</style>


<?php require_once 'footer.php'; ?>
