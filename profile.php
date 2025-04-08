<?php
session_start();
require_once 'db.php';

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
$user_query = "SELECT first_name, last_name, email, user_name, role_id FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Получаем данные для фильтров
$statuses = mysqli_query($conn, "SELECT * FROM status");
$developers = mysqli_query($conn, "SELECT * FROM developers");

// Обработка фильтров с сохранением значений
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$developer_filter = $_GET['developer'] ?? '';

// Фильтрация транзакций
$whereClauses = ["t.user_id = $user_id"];
if (!empty($status_filter)) {
    $whereClauses[] = "t.status_id = " . (int)$status_filter;
}
if (!empty($date_filter)) {
    $whereClauses[] = "DATE(t.purchase_date) = '" . mysqli_real_escape_string($conn, $date_filter) . "'";
}
if (!empty($developer_filter)) {
    $whereClauses[] = "g.developer_id = " . (int)$developer_filter;
}
$whereSql = implode(" AND ", $whereClauses);

// Постраничный вывод
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Получаем транзакции пользователя
$transactions_query = "
    SELECT t.transaction_id, g.title AS game_title, d.developer_name, s.status_name, t.purchase_date
    FROM transactions t
    JOIN games g ON t.game_id = g.game_id
    JOIN developers d ON g.developer_id = d.developer_id
    JOIN status s ON t.status_id = s.status_id
    WHERE $whereSql
    LIMIT $limit OFFSET $offset
";
$transactions = mysqli_query($conn, $transactions_query);
if (!$transactions) {
    die("Ошибка запроса: " . mysqli_error($conn));
}

// Получаем общее количество записей
$count_query = "SELECT COUNT(*) AS total FROM transactions t 
    JOIN games g ON t.game_id = g.game_id 
    JOIN developers d ON g.developer_id = d.developer_id
    JOIN status s ON t.status_id = s.status_id
    WHERE $whereSql";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

require_once 'header.php';
?>

<div class="profile-container">
    <div class="profile-header">
		<h2>Личный кабинет</h2>
		<a href="logout.php" class="logout-button">Выйти</a>
	</div>
	<p>Привет, <?= htmlspecialchars($user['user_name']) ?>!</p>
    <div class="profile-section">
        <h3>Личные данные</h3>
        <form id="profile-form">
            <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" disabled>
            <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" disabled>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            <button type="button" id="edit-profile">Редактировать</button>
            <button type="submit" id="save-profile" style="display: none;">Сохранить</button>
        </form>

        <h3>Изменить пароль</h3>
        <form id="password-form">
            <input type="password" name="old_password" placeholder="Старый пароль">
            <input type="password" name="new_password" placeholder="Новый пароль">
            <input type="password" name="confirm_password" placeholder="Повторите новый пароль">
            <button type="submit">Обновить пароль</button>
        </form>
		<p class="support-profile">Если вам необходимо сменить имя пользователя, то обратитесь в <a href="support.php">Центр поддержки</a></p>
    </div>
	
	    <!-- Добавляем секцию для редакторов -->
    <?php if ($user['role_id'] == 2): // Если пользователь редактор ?>
    <div class="editor-section">
        <h3>Управление статьями</h3>
        <a href="create_news.php" class="button">Создать новую статью</a>
        <a href="edit_articles.php" class="button">Редактировать список статей</a>
    </div>
    <?php endif; ?>

    <div class="transactions-section">
        <h3>Купленные игры</h3>

		<form id="filter-form" class="filter-form">
			<select name="status">
				<option value="">Все статусы</option>
				<?php while ($status = mysqli_fetch_assoc($statuses)): ?>
					<option value="<?= $status['status_id'] ?>" <?= ($status_filter == $status['status_id']) ? 'selected' : '' ?>>
						<?= htmlspecialchars($status['status_name']) ?>
					</option>
				<?php endwhile; ?>
			</select>

			<input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">

			<select name="developer">
				<option value="">Все разработчики</option>
				<?php while ($dev = mysqli_fetch_assoc($developers)): ?>
					<option value="<?= $dev['developer_id'] ?>" <?= ($developer_filter == $dev['developer_id']) ? 'selected' : '' ?>>
						<?= htmlspecialchars($dev['developer_name']) ?>
					</option>
				<?php endwhile; ?>
			</select>

			<button type="submit">Фильтровать</button>
		</form>

		<!-- Контейнер для купленных игр -->
		<div id="transactions-container">
			<?php include 'transactions_table.php'; ?>
		</div>



        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Уведомления будут добавляться сюда -->
<div id="notification-container"></div>

<script>
document.getElementById('edit-profile').addEventListener('click', function() {
    document.querySelectorAll('#profile-form input').forEach(input => input.removeAttribute('disabled'));
    this.style.display = 'none';
    document.getElementById('save-profile').style.display = 'inline-block';
});

function showNotification(message, type) {
    let notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Обновление профиля
document.getElementById('profile-form').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('update_profile.php', {
        method: 'POST',
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        showNotification(data.message, data.status);
        if (data.status === 'success') {
            document.querySelectorAll('#profile-form input').forEach(input => input.setAttribute('disabled', 'true'));
            document.getElementById('edit-profile').style.display = 'inline-block';
            document.getElementById('save-profile').style.display = 'none';
        }
    });
});

// Обновление пароля
document.getElementById('password-form').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('update_password.php', {
        method: 'POST',
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        showNotification(data.message, data.status);
        if (data.status === 'success') {
            document.getElementById('password-form').reset();
        }
    });
});

document.getElementById('filter-form').addEventListener('submit', function(e) {
    e.preventDefault(); // Отменяем стандартное обновление страницы

    let formData = new FormData(this);

    fetch('transactions_table.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('transactions-container').innerHTML = html; // Обновляем только таблицу
    })
    .catch(error => console.error('Ошибка:', error));
});

</script>

<style>

/* Стилизация секции для редакторов */
.editor-section {
    margin-top: 20px;
    background: #141414;
    padding: 20px;
    border-radius: 8px;
	margin-bottom: 20px;
}

.editor-section h3 {
    margin-bottom: 10px;
    color: #fff;
}

.editor-section .button {
    display: inline-block;
    padding: 10px 20px;
    margin-top: 10px;
    background: #ff5722;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
}

.editor-section .button:hover {
    background: #e64a19;
}


.profile-container {
    max-width: 800px;
    margin: auto;
    padding: 20px;
}
.profile-section, .transactions-section {
    margin-bottom: 20px;
    padding: 15px;
    border-radius: 8px;
    background: #141414;
}
input, select, button {
    display: block;
    width: 100%;
    margin-bottom: 10px;
    padding: 8px;
}
.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
}
.pagination a {
    padding: 5px 10px;
    background: #ddd;
    text-decoration: none;
}
.pagination .active {
    background: #333;
    color: white;
}
.no-transactions {
    text-align: center;
    font-size: 18px;
    color: #888;
    padding: 20px;
}

.support-profile a {
    color: #e04e29;
    text-decoration: none;
}

table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 10px;
    border: 1px solid #444;
    text-align: center;
}
.genre {
    display: inline-block;
    padding: 5px;
    background: #222;
    color: #fff;
    border-radius: 5px;
    margin: 2px;
}
.game-link {
    text-decoration: none;
    color: white;
}
.game-link:hover {
    text-decoration: underline;
}
.profile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logout-button {
    padding: 8px 15px;
    background: #e04e29;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
}

.logout-button:hover {
    background: #c03d20;
}


</style>

<?php require_once 'footer.php'; ?>
