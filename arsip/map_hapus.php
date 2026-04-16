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

// Opsi: Hapus semua arsip dalam map, atau orphan-kan mereka
// Untuk aman, kita orphan-kan (set map_id = NULL) daripada delete
$stmt = $pdo->prepare("UPDATE arsip SET map_id = NULL WHERE map_id = ?");
$stmt->execute([$mapId]);

// Hapus map (beserta sub-map via ON DELETE CASCADE)
$stmt = $pdo->prepare("DELETE FROM map WHERE id = ?");
$stmt->execute([$mapId]);

logAktivitas($pdo, $_SESSION['user_id'], 'Hapus map: ' . $mapName, null);

header('Location: map.php?msg=hapus');
exit;
?>
