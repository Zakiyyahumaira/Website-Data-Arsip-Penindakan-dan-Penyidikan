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

// Ambil arsip dalam map ini
$stmt = $pdo->prepare(
    "SELECT a.*, jp.nama_pelanggaran, p1.nama AS petugas_1, p2.nama AS petugas_2
     FROM arsip a
     LEFT JOIN petugas p1 ON a.petugas_1_id = p1.id
     LEFT JOIN petugas p2 ON a.petugas_2_id = p2.id
     LEFT JOIN jenis_pelanggaran jp ON a.jenis_pelanggaran_id = jp.id
     WHERE a.map_id = ?
     ORDER BY a.created_at DESC"
);
$stmt->execute([$mapId]);
$arsips = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($map['nama_map']) ?> — Arsip Kantor</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .map-header { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .arsip-table { width: 100%; border-collapse: collapse; }
        .arsip-table th { background: #f3f4f6; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #d1d5db; }
        .arsip-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        .arsip-table tr:hover { background: #f9fafb; }
        .btn-group { display: flex; gap: 8px; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h1>📁 <?= sanitize($map['nama_map']) ?></h1>
            <div class="topbar-actions">
                <a href="map.php" class="btn btn-ghost btn-sm">&larr; Kembali ke Daftar Map</a>
            </div>
        </div>

        <div class="page-body">
            <!-- Map Header Info -->
            <div class="map-header">
                <div><strong>Nama Map:</strong> <?= sanitize($map['nama_map']) ?></div>
                <div><strong>Deskripsi:</strong> <?= sanitize($map['deskripsi'] ?? 'Tidak ada') ?></div>
                <div><strong>Dibuat:</strong> <?= formatTanggal($map['created_at']) ?></div>
                <div style="margin-top: 10px;">
                    <a href="map_edit.php?id=<?= $map['id'] ?>" class="btn btn-sm" style="background:#dbeafe;color:#1e40af;">Edit</a>
                    <a href="tambah.php?map_id=<?= $map['id'] ?>" class="btn btn-primary btn-sm">+ Upload Arsip ke Map Ini</a>
                </div>
            </div>

            <!-- Daftar Arsip dalam Map -->
            <h3 style="margin-bottom: 15px;">📄 Arsip dalam Map Ini</h3>
            <?php if (empty($arsips)): ?>
            <div class="alert alert-info">
                Belum ada arsip dalam map ini. <a href="tambah.php?map_id=<?= $map['id'] ?>">Upload arsip sekarang</a>
            </div>
            <?php else: ?>
            <table class="arsip-table">
                <thead>
                    <tr>
                        <th>No. Surat</th>
                        <th>Petugas</th>
                        <th>Jenis Pelanggaran</th>
                        <th>Jumlah</th>
                        <th>Tanggal Dokumen</th>
                        <th>File</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($arsips as $a): ?>
                    <tr>
                        <td><?= sanitize($a['no_surat']) ?></td>
                        <td><?= sanitize($a['petugas_1'] . ' / ' . $a['petugas_2']) ?></td>
                        <td><?= sanitize($a['nama_pelanggaran'] ?? '') ?></td>
                        <td><?= formatJumlah($a['jumlah'], $a['satuan']) ?></td>
                        <td><?= formatTanggal($a['tanggal_dokumen']) ?></td>
                        <td>
                            <?php if ($a['file_path']): ?>
                            <a href="../<?= $a['file_path'] ?>" target="_blank" title="<?= sanitize($a['file_name']) ?>">📥 Download</a>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="detail.php?id=<?= $a['id'] ?>" class="btn btn-sm" style="background:#dbeafe;color:#1e40af;">Detail</a>
                                <a href="edit.php?id=<?= $a['id'] ?>" class="btn btn-sm" style="background:#dcfce7;color:#166534;">Edit</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
