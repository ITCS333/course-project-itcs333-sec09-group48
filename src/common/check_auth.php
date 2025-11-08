<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Get current user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

if(!$current_user) {
    session_destroy();
    header('Location: ../../index.php');
    exit;
}
?>