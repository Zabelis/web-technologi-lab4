<?php
session_start();
require_once 'db.php'; // Подключение к базе данных
require_once 'header.php'; // Подключение хедера

// Если пользователь уже авторизован, перенаправляем на главную страницу
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Запрос для поиска пользователя по имени
    $query = "SELECT * FROM users WHERE user_name = '$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Проверяем правильность пароля
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            header('Location: profile.php'); // Перенаправляем на главную страницу
            exit;
        } else {
            $error_message = "Неверный пароль!";
        }
    } else {
        $error_message = "Пользователь с таким именем не найден!";
    }
}
?>

<div class="login-page container">
    <h2>Вход в аккаунт</h2>
    
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?= $error_message ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST" class="login-form">
        <input type="text" name="username" placeholder="Имя пользователя" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
    </form>

    <p class="register-link">
        Ещё не зарегистрированы? <a href="register.php">Зарегистрироваться</a>
    </p>
</div>

<?php require_once 'footer.php'; ?>

