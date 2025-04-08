<?php
require_once 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

// Фильтры
$status_filter = $_POST['status'] ?? '';
$date_filter = $_POST['date'] ?? '';
$developer_filter = $_POST['developer'] ?? '';

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

// Получаем транзакции
$transactions_query = "
    SELECT t.transaction_id, g.title AS game_title, d.developer_name, s.status_name, t.purchase_date
    FROM transactions t
    JOIN games g ON t.game_id = g.game_id
    JOIN developers d ON g.developer_id = d.developer_id
    JOIN status s ON t.status_id = s.status_id
    WHERE $whereSql
";
$transactions = mysqli_query($conn, $transactions_query);
?>

<?php if (mysqli_num_rows($transactions) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Название</th>
                <th>Жанры</th>
                <th>Разработчик</th>
                <th>Статус</th>
                <th>Дата покупки</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($transactions)): ?>
                <?php
                $game_id = $row['transaction_id'];
                $genres_query = "
                    SELECT GROUP_CONCAT(g.genre_name SEPARATOR ', ') AS genres
                    FROM game_genres gg
                    JOIN genres g ON gg.genre_id = g.genre_id
                    WHERE gg.game_id = $game_id";
                $genres_result = mysqli_query($conn, $genres_query);
                $genres_row = mysqli_fetch_assoc($genres_result);
                $genres = explode(', ', $genres_row['genres']);
                ?>
                <tr>
                    <td><a href="game.php?id=<?= $game_id ?>" class="game-link"><?= htmlspecialchars($row['game_title']) ?></a></td>
                    <td>
                        <?php foreach ($genres as $genre): ?>
                            <span class="genre"><?= htmlspecialchars($genre) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td><?= htmlspecialchars($row['developer_name']) ?></td>
                    <td><?= htmlspecialchars($row['status_name']) ?></td>
                    <td><?= date('d.m.Y', strtotime($row['purchase_date'])) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="no-transactions">У вас еще нет совершенных покупок.</p>
<?php endif; ?>
