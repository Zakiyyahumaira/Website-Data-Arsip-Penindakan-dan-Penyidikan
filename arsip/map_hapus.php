<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$mapId = (int)($_GET['id'] ?? 0);
if (!$mapId) {
    header('Location: map.php');
    exit;
}

$map = getMapDetail($pdo, $mapId);
if (!$map) {
    header('Location: map.php?error=tidak_ditemukan');
    exit;
}

$mapName = $map['nama_map'];

// Hapus semua arsip yang berada di map ini, termasuk file fisik
$stmt = $pdo->prepare("SELECT * FROM arsip WHERE map_id = ?");
$stmt->execute([$mapId]);
$arsips = $stmt->fetchAll();
foreach ($arsips as $arsip) {
    if ($arsip['file_path'] && file_exists('../' . $arsip['file_path'])) {
        @unlink('../' . $arsip['file_path']);
    }
}
$pdo->prepare("DELETE FROM arsip WHERE map_id = ?")->execute([$mapId]);

// Hapus map itu sendiri
$stmt = $pdo->prepare("DELETE FROM map WHERE id = ?");
$stmt->execute([$mapId]);

logAktivitas($pdo, $_SESSION['user_id'], 'Hapus map dan arsip di dalamnya: ' . $mapName, null);

header('Location: map.php?msg=hapus');
exit;
?>
