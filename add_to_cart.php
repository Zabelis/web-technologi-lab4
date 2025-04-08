<?php
session_start();
require_once 'db.php';

$response = ['status' => 'error', 'message' => 'Неизвестная ошибка', 'action' => 'add'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Требуется авторизация.';
    echo json_encode($response);
    exit;
}

if (isset($_POST['game_id'])) {
    $game_id = (int)$_POST['game_id'];
    $user_id = (int)$_SESSION['user_id'];

    // Проверяем, есть ли уже эта игра в корзине
    $check_query = "SELECT * FROM cart WHERE user_id = $user_id AND game_id = $game_id";
    $check_result = mysqli_query($conn, $check_query);
	
    if (mysqli_num_rows($check_result) > 0) {
        // Если игра уже в корзине, удаляем её
        $delete_query = "DELETE FROM cart WHERE user_id = $user_id AND game_id = $game_id";
        $delete_result = mysqli_query($conn, $delete_query);

        if ($delete_result) {
            $response['status'] = 'success';
            $response['message'] = 'Игра удалена из корзины';
			$response['action'] = 'remove';
        } else {
            $response['message'] = 'Ошибка при удалении из корзины: ' . mysqli_error($conn);
        }
    } else {
        // Если игры нет в корзине, добавляем её
        $insert_query = "INSERT INTO cart (user_id, game_id) VALUES ($user_id, $game_id)";
        $insert_result = mysqli_query($conn, $insert_query);

        if ($insert_result) {
            $response['status'] = 'success';
            $response['message'] = 'Игра добавлена в корзину';
        } else {
            $response['message'] = 'Ошибка при добавлении в корзину: ' . mysqli_error($conn);
			$response['action'] = 'remove';
        }
    }
}

echo json_encode($response);
?>
