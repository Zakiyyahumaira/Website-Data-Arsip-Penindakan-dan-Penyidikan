<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$mapList = getSemuaMap($pdo);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Map Arsip — Arsip Kantor</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .map-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .map-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .map-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .map-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .map-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .map-stat {
            font-size: 12px;
            color: #6b7280;
            margin: 4px 0;
        }
        .map-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f3f4f6;
        }
        .map-actions a, .map-actions button {
            flex: 1;
            padding: 6px 8px;
            font-size: 12px;
            text-align: center;
            text-decoration: none;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .map-card-link { background: #f3f4f6; color: #374151; }
        .map-card-link:hover { background: #e5e7eb; }
        .map-card-edit { background: #dbeafe; color: #1e40af; }
        .map-card-edit:hover { background: #bfdbfe; }
        .map-card-del { background: #fee2e2; color: #991b1b; }
        .map-card-del:hover { background: #fecaca; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h1>📁 Daftar Map/Folder</h1>
            <div class="topbar-actions">
                <a href="map_tambah.php" class="btn btn-primary btn-sm">+ Tambah Map Baru</a>
                <a href="daftar.php" class="btn btn-ghost btn-sm">Ke Daftar Arsip</a>
            </div>
        </div>

        <div class="page-body">
            <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php
                $msg = $_GET['msg'];
                if ($msg === 'tambah') echo '✓ Map berhasil ditambahkan.';
                elseif ($msg === 'edit') echo '✓ Map berhasil diperbarui.';
                elseif ($msg === 'hapus') echo '✓ Map berhasil dihapus.';
                ?>
            </div>
            <?php endif; ?>

            <?php if (empty($mapList)): ?>
            <div class="alert alert-info">
                <strong>Belum ada map.</strong> Silakan <a href="map_tambah.php">buat map baru</a> terlebih dahulu.
            </div>
            <?php else: ?>

            <div class="map-grid">
                <?php foreach ($mapList as $map): 
                    $totalArsip = hitungArsipInMap($pdo, $map['id']);
                ?>
                <div class="map-card">
                    <div class="map-icon">📁</div>
                    <div class="map-title"><?= sanitize($map['nama_map']) ?></div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">
                        <?= sanitize($map['deskripsi'] ?? 'Tidak ada deskripsi') ?>
                    </div>
                    <div class="map-stat">📄 Arsip: <strong><?= $totalArsip ?></strong></div>
                    <div class="map-stat">📅 <?= formatTanggal($map['created_at']) ?></div>
                    
                    <div class="map-actions">
                        <a href="map_detail.php?id=<?= $map['id'] ?>" class="map-card-link">Buka</a>
                        <a href="map_edit.php?id=<?= $map['id'] ?>" class="map-card-edit">Edit</a>
                        <button onclick="confirmDelete(<?= $map['id'] ?>, '<?= sanitize($map['nama_map']) ?>')" class="map-card-del">Hapus</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmDelete(mapId, mapName) {
    if (confirm('Yakin hapus map "' + mapName + '" dan semua isinya?')) {
        window.location.href = 'map_hapus.php?id=' + mapId;
    }
}
</script>
</body>
</html>
