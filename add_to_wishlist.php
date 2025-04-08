<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "Требуется авторизация.";
    exit;
}

if (isset($_POST['game_id'])) {
    $game_id = (int)$_POST['game_id'];
    $user_id = (int)$_SESSION['user_id'];

    // Проверка, есть ли уже игра в избранном
    $check_query = "SELECT * FROM favorites WHERE user_id = $user_id AND game_id = $game_id";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Если игра уже в избранном, удаляем её
        $delete_query = "DELETE FROM favorites WHERE user_id = $user_id AND game_id = $game_id";
        $delete_result = mysqli_query($conn, $delete_query);

        if ($delete_result) {
            echo "Игра удалена из списка желаемого.";
        } else {
            echo "Ошибка при удалении из списка желаемого: " . mysqli_error($conn);
        }
    } else {
        // Если игры нет в избранном, добавляем её
        $insert_query = "INSERT INTO favorites (user_id, game_id) VALUES ($user_id, $game_id)";
        $insert_result = mysqli_query($conn, $insert_query);

        if ($insert_result) {
            echo "Добавлено в список желаемого!";
        } else {
            echo "Ошибка при добавлении в список желаемого: " . mysqli_error($conn);
        }
    }
}
?>
