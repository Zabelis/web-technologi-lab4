<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Вы не авторизованы']);
    exit;
}

$user_id = $_SESSION['user_id'];
$old_password = $_POST['old_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Все поля должны быть заполнены']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'Пароли не совпадают']);
    exit;
}

// Получаем текущий хеш пароля
$query = "SELECT password FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $hashed_password);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Проверяем старый пароль
if (!password_verify($old_password, $hashed_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Неверный старый пароль']);
    exit;
}

// Хешируем новый пароль
$new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

// Обновляем пароль
$update_query = "UPDATE users SET password = ? WHERE user_id = ?";
$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($update_stmt, "si", $new_hashed_password, $user_id);

if (mysqli_stmt_execute($update_stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Пароль успешно обновлён']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ошибка обновления пароля']);
}

mysqli_stmt_close($update_stmt);
?>
