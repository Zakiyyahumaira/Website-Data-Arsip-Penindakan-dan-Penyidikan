<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: daftar.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM arsip WHERE id = ?");
$stmt->execute([$id]);
$arsip = $stmt->fetch();

if ($arsip) {
    // Hapus file fisik jika ada
    if ($arsip['file_path'] && file_exists('../' . $arsip['file_path'])) {
        @unlink('../' . $arsip['file_path']);
    }
    logAktivitas($pdo, $_SESSION['user_id'], 'Hapus arsip: ' . $arsip['nama_pegawai'], $id);
    $pdo->prepare("DELETE FROM arsip WHERE id = ?")->execute([$id]);
}

header('Location: daftar.php?msg=hapus');
exit;
?>
