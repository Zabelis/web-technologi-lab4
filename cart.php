<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем игры в корзине
$cart_query = "
    SELECT g.game_id, g.title, g.price, d.developer_name 
    FROM cart c
    JOIN games g ON c.game_id = g.game_id
    JOIN developers d ON g.developer_id = d.developer_id
    WHERE c.user_id = $user_id";
$cart_items = mysqli_query($conn, $cart_query);

// Получаем жанры игр
$genres_query = "
    SELECT gg.game_id, GROUP_CONCAT(g.genre_name SEPARATOR ', ') AS genres
    FROM game_genres gg
    JOIN genres g ON gg.genre_id = g.genre_id
    WHERE gg.game_id IN (SELECT game_id FROM cart WHERE user_id = $user_id)
    GROUP BY gg.game_id";
$genres_result = mysqli_query($conn, $genres_query);
$genres = [];
while ($row = mysqli_fetch_assoc($genres_result)) {
    $genres[$row['game_id']] = explode(', ', $row['genres']);
}

require_once 'header.php';
?>

<div class="cart-container">
    <h2>Корзина</h2>

    <div id="cart-content">
        <?php if (mysqli_num_rows($cart_items) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Жанры</th>
                        <th>Разработчик</th>
                        <th>Цена</th>
                        <th>Удалить</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($cart_items)): ?>
                        <tr>
                            <td><a href="game.php?id=<?= $row['game_id'] ?>" class="game-link"><?= htmlspecialchars($row['title']) ?></a></td>
                            <td>
                                <?php foreach ($genres[$row['game_id']] as $genre): ?>
                                    <span class="genre"><?= htmlspecialchars($genre) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td><?= htmlspecialchars($row['developer_name']) ?></td>
                            <td><?= number_format($row['price'], 2) ?> ₽</td>
                            <td><button class="delete-btn" data-id="<?= $row['game_id'] ?>">✖</button></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <button id="purchase-btn">Приобрести</button>
        <?php else: ?>
            <p class="empty-cart">Ваша корзина пуста.</p>
        <?php endif; ?>
    </div>

    <p id="purchase-message"></p>
</div>

<script>
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const gameId = this.dataset.id;
        fetch('remove_from_cart.php', {
            method: 'POST',
            body: new URLSearchParams({ game_id: gameId })
        }).then(() => location.reload());
    });
});

document.getElementById('purchase-btn')?.addEventListener('click', function() {
    fetch('purchase.php', { method: 'POST' })
        .then(res => res.text())
        .then(response => {
            document.getElementById('cart-content').innerHTML = ''; // Удаляем таблицу
            document.getElementById('purchase-message').innerHTML = response; // Показываем сообщение
        });
});
</script>

<style>
.cart-container {
    max-width: 950px;
    margin: auto;
    padding: 20px;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 10px;
    border: 1px solid #444;
    text-align: center;
}
.genre {
    display: inline-block;
    padding: 5px;
    background: #222;
    color: #fff;
    border-radius: 5px;
    margin: 2px;
}
.delete-btn {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: red;
}
#purchase-btn {
    display: block;
    margin: 20px auto;
    padding: 10px;
    font-size: 18px;
    background: green;
    color: white;
    border: none;
    cursor: pointer;
}
.game-link {
    color: #e04e29;
    text-decoration: none;
}
.game-link:hover {
    text-decoration: underline;
}
.empty-cart, #purchase-message {
    text-align: center;
    font-size: 18px;
    color: #888;
    padding: 20px;
}
</style>

<?php require_once 'footer.php'; ?>
