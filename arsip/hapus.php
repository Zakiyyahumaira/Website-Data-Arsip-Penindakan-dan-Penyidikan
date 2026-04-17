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
    $petugasNama = ($arsip['petugas_1'] ?? $arsip['nama_pegawai']) . ' / ' . ($arsip['petugas_2'] ?? '-');
    logAktivitas($pdo, $_SESSION['user_id'], 'Hapus arsip: ' . $petugasNama, $id);
    $pdo->prepare("DELETE FROM arsip WHERE id = ?")->execute([$id]);
}

header('Location: daftar.php?msg=hapus');
exit;
?>
