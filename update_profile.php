<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Вы не авторизованы']);
    exit;
}

$user_id = $_SESSION['user_id'];
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');

if (empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Все поля должны быть заполнены']);
    exit;
}

// Обновляем данные пользователя
$query = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sssi", $first_name, $last_name, $email, $user_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Данные успешно обновлены']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ошибка обновления данных']);
}

mysqli_stmt_close($stmt);
?>
