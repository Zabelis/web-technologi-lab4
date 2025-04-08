<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['game_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];
$game_id = (int)$_POST['game_id'];

mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id AND game_id = $game_id LIMIT 1");
?>
