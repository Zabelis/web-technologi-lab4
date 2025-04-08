<?php
session_start();
require_once 'db.php'; // Подключение к БД
require_once 'header.php'; // Подключение хедера

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Проверка на совпадение паролей
    if ($password !== $confirm_password) {
        $error_message = "Пароли не совпадают!";
    } else {
        // Проверка на существование пользователя с таким именем или почтой
        $query = "SELECT * FROM users WHERE user_name = '$username' OR email = '$email'";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            // Если запрос не выполнен, выводим ошибку
            $error_message = "Ошибка при выполнении запроса: " . mysqli_error($conn);
        } else {
            // Проверяем количество строк в результате
            if (mysqli_num_rows($result) > 0) {
                $error_message = "Пользователь с таким именем или почтой уже существует!";
            } else {
                // Хешируем пароль перед сохранением
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Вставка нового пользователя в базу данных
                $insert_query = "INSERT INTO users (first_name, last_name, user_name, email, password, role_id) 
                                 VALUES ('$first_name', '$last_name', '$username', '$email', '$hashed_password', 3)";
                if (mysqli_query($conn, $insert_query)) {
                    $_SESSION['user_name'] = $username;
                    $_SESSION['user_id'] = mysqli_insert_id($conn);
                    header('Location: index.php'); // Перенаправляем на главную страницу после регистрации
                    exit;
                } else {
                    $error_message = "Ошибка при регистрации. Попробуйте снова. " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<div class="register-page container">
    <h2>Регистрация</h2>
    
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?= $error_message ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST" class="register-form">
        <input type="text" name="first_name" placeholder="Имя" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
        <input type="text" name="last_name" placeholder="Фамилия" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
        <input type="text" name="username" placeholder="Имя пользователя" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        <input type="email" name="email" placeholder="Электронная почта" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="password" name="password" placeholder="Пароль" required>
        <input type="password" name="confirm_password" placeholder="Подтверждение пароля" required>
        <button type="submit">Зарегистрироваться</button>
    </form>

    <p class="login-link">
        Уже зарегистрированы? <a href="login.php">Войти</a>
    </p>
</div>

<?php require_once 'footer.php'; ?>
