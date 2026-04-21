<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekAdmin();

$action = $_GET['action'] ?? 'single';
$id = (int)($_GET['id'] ?? 0);

try {
    if ($action === 'single' && $id) {
        // Hapus satu log berdasarkan ID (tidak perlu logging)
        $stmt = $pdo->prepare("DELETE FROM log_aktivitas WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: log.php?msg=hapus');
    } elseif ($action === 'all') {
        // Hapus semua log (tidak perlu logging)
        $stmt = $pdo->prepare("DELETE FROM log_aktivitas");
        $stmt->execute();
        header('Location: log.php?msg=hapus_semua');
    } else {
        header('Location: log.php');
    }
} catch (Exception $e) {
    header('Location: log.php?msg=error&detail=' . urlencode($e->getMessage()));
}
exit;
?>
