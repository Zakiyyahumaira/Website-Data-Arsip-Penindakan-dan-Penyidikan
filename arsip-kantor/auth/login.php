<?php
session_start();
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = md5($_POST['password'] ?? '');

if (empty($username) || empty($_POST['password'])) {
    header('Location: ../index.php?error=wrong');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? LIMIT 1");
$stmt->execute([$username, $password]);
$user = $stmt->fetch();

if ($user) {
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['nama']     = $user['nama'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];
    session_regenerate_id(true);
    header('Location: ../dashboard.php');
} else {
    header('Location: ../index.php?error=wrong');
}
exit;
?>
