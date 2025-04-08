<?php
session_start();
require_once 'db.php';

// Инициализация переменной для количества игр в корзине
$cart_count = 0;

if (isset($_SESSION['user_id'])) {
    // Запрос для подсчета количества игр в корзине
    $cartQuery = "SELECT COUNT(*) AS cart_count FROM cart WHERE user_id = " . (int)$_SESSION['user_id'];
    $cartResult = mysqli_query($conn, $cartQuery);
    if ($cartResult === false) {
        die("Ошибка при выполнении запроса на корзину: " . mysqli_error($conn));
    }

    $row = mysqli_fetch_assoc($cartResult);
    $cart_count = $row['cart_count'];
}

// Возвращаем количество игр в корзине в формате JSON
echo json_encode(['cart_count' => $cart_count]);
?>
