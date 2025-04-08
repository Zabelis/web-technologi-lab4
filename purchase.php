<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit("<span style='color: red;'>Ошибка: пользователь не авторизован.</span>");
}

$user_id = $_SESSION['user_id'];

// Получаем все игры из корзины
$cart_query = "SELECT game_id FROM cart WHERE user_id = $user_id";
$cart_result = mysqli_query($conn, $cart_query);
$games = [];
while ($row = mysqli_fetch_assoc($cart_result)) {
    $games[] = (int)$row['game_id'];
}

// Если корзина пуста
if (empty($games)) {
    exit("<span style='color: red;'>Ошибка: ваша корзина пуста.</span>");
}

// Добавляем игры в транзакции
$values = [];
foreach ($games as $game_id) {
    $values[] = "($user_id, $game_id, NOW(), 1)";
}
$insert_query = "INSERT INTO transactions (user_id, game_id, purchase_date, status_id) VALUES " . implode(', ', $values);
if (!mysqli_query($conn, $insert_query)) {
    exit("<span style='color: red;'>Ошибка при оформлении покупки. Попробуйте позже.</span>");
}

// Очищаем корзину
mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");

// Выводим сообщение
exit("<span style='color: green;'>Ключи активации отправлены на вашу электронную почту! Спасибо за покупку игр на нашем сайте.</span>");
?>
