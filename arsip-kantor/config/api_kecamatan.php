<?php
require 'database.php';
header('Content-Type: application/json');

$wilayah_id = (int)($_GET['wilayah_id'] ?? 0);
if (!$wilayah_id) { echo '[]'; exit; }

$stmt = $pdo->prepare("SELECT id, nama_kecamatan FROM kecamatan WHERE wilayah_id = ? ORDER BY nama_kecamatan");
$stmt->execute([$wilayah_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
