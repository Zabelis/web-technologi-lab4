<?php
session_start();
require_once 'db.php';

// Проверка на авторизацию
if (!isset($_SESSION['user_id'])) {
    echo "Ошибка: Пожалуйста, авторизуйтесь.";
    exit;
}

if (isset($_POST['game_id'])) {
    $game_id = (int)$_POST['game_id'];
    $user_id = (int)$_SESSION['user_id'];

    // Удаление игры из избранного
    $query = "DELETE FROM favorites WHERE user_id = $user_id AND game_id = $game_id";
    $result = mysqli_query($conn, $query);

    if ($result) {
        echo "Игра удалено из избранного.";
    } else {
        echo "Ошибка при удалении игры из избранного: " . mysqli_error($conn);
    }
} else {
    echo "Ошибка: Игра не указана.";
}
?>
