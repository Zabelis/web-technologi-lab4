<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("Ошибка: Вы не авторизованы.");
}

if (!isset($_POST['game_id'], $_POST['rating'])) {
    die("Ошибка: Недостаточно данных.");
}

$user_id = (int)$_SESSION['user_id'];
$game_id = (int)$_POST['game_id'];
$rating = (int)$_POST['rating'];

if ($rating < 0 || $rating > 5) {
    die("Ошибка: Некорректная оценка.");
}

// Проверяем, оценивал ли пользователь уже эту игру
$checkQuery = "SELECT * FROM reviews WHERE user_id = $user_id AND game_id = $game_id";
$checkResult = mysqli_query($conn, $checkQuery);
if ($rating === 0) {
    $query = "DELETE FROM reviews WHERE user_id = $user_id AND game_id = $game_id";
    if (mysqli_query($conn, $query)) {
        echo "Оценка удалена";
    } else {
        echo "Ошибка удаления оценки";
    }
}
elseif (mysqli_num_rows($checkResult) > 0) {
    // Обновляем оценку
    $updateQuery = "UPDATE reviews SET rating = $rating WHERE user_id = $user_id AND game_id = $game_id";
    mysqli_query($conn, $updateQuery);
} else {
    // Вставляем новую оценку
    $insertQuery = "INSERT INTO reviews (user_id, game_id, rating) VALUES ($user_id, $game_id, $rating)";
    mysqli_query($conn, $insertQuery);
}

echo "Оценка сохранена!";
