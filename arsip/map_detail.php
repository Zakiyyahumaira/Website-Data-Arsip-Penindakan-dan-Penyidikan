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
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .action-link:hover {
            text-decoration: underline;
        }
        .map-header {
        background-color: #f9fafb !important;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        }
        .map-header strong {
        font-weight: normal;
        display: inline-block;
        width: 150px;
        position: relative;
        }

        /* rapikan posisi titik dua */
        .map-header strong::after {
        content: ":";
        position: absolute;
        right: 7px;
        }

        /* hilangkan titik dua asli */
        .map-header strong {
        text-align: left;
        }
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; padding: 0; margin: 0; }
        .arsip-table { width: 100%; min-width: 760px; border-collapse: collapse; }
        .arsip-table th, .arsip-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .arsip-table td:nth-child(2) { white-space: normal; overflow: visible; text-overflow: clip; }
        .arsip-table th { background: #f3f4f6; text-align: left; font-weight: 600; border-bottom: 2px solid #d1d5db; }
        .arsip-table tr:hover { background: #f9fafb; }
        .btn-group { display: flex; gap: 8px; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <button id="toggleSidebar" class="hamburger-btn">☰</button>
            <h1><?= sanitize($map['nama_map']) ?></h1>
            <div class="topbar-actions">
                <a href="map.php" class="btn btn-ghost btn-sm">&larr; Kembali ke Daftar Map</a>
            </div>
        </div>

        <div class="page-body">
            <!-- Map Header Info -->
            <div class="map-header">
                <!-- Bagian 1: Info Map (3 kolom horizontal) -->
                <div class="map-info-section">
                    <div class="info-column">
                        <div class="info-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 7 7 2h10l5 5v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7z"/>
                                <polyline points="9 11 12 14 22 4"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Nama Map</div>
                            <div class="info-value"><?= sanitize($map['nama_map']) ?></div>
                        </div>
                    </div>
                    <div class="info-column">
                        <div class="info-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/>
                                <polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Deskripsi</div>
                            <div class="info-value"><?= sanitize($map['deskripsi'] ?? 'Tidak ada') ?></div>
                        </div>
                    </div>
                    <div class="info-column">
                        <div class="info-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Dibuat</div>
                            <div class="info-value"><?= formatTanggal($map['created_at']) ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Bagian 2: Tombol Actions -->
                <div class="map-actions-section">
                    <a href="map_edit.php?id=<?= $map['id'] ?>" class="btn btn-sm btn-edit" style="background:#dbeafe;color:#1e40af;">Edit</a>
                    <a href="tambah.php?map_id=<?= $map['id'] ?>" class="btn btn-primary btn-sm">+ Upload Arsip ke Map Ini</a>
                </div>
            </div>

            <!-- Daftar Arsip dalam Map -->
            <div class="card">
                <div class="card-header"><h3>Arsip dalam Map Ini</h3></div>
                <div class="card-body">
            <?php if (empty($arsips)): ?>
            <div class="alert alert-info">
                Belum ada arsip dalam map ini. <a href="tambah.php?map_id=<?= $map['id'] ?>">Upload arsip sekarang</a>
            </div>
            <?php else: ?>
            <div class="table-wrap" style="padding:0;margin:0">
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
                        <td><a href="detail.php?id=<?= $a['id'] ?>" style="color:#1e40af;text-decoration:none"><?= sanitize($a['petugas_1'] . ' / ' . $a['petugas_2']) ?></a></td>
                        <td><?= sanitize($a['nama_pelanggaran'] ?? '') ?></td>
                        <td><?= formatJumlah($a['jumlah'], $a['satuan']) ?></td>
                        <td><?= formatTanggal($a['tanggal_dokumen']) ?></td>
                        <td>
                            <?php if ($a['file_path']): ?>
                            <a href="../<?= sanitize(normalizeFilePath($a['file_path'])) ?>" target="_blank" title="<?= sanitize($a['file_name']) ?>">📥 Download</a>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;flex-direction:column;gap:6px">
                                <a href="detail.php?id=<?= $a['id'] ?>" class="action-link" style="display:flex;align-items:center;gap:6px;padding:0;background:transparent;color:#1e40af;border:none;cursor:pointer;font-size:inherit" title="Detail">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                    Detail
                                </a>
                                <a href="edit.php?id=<?= $a['id'] ?>" class="action-link" style="display:flex;align-items:center;gap:6px;padding:0;background:transparent;color:#166534;border:none;cursor:pointer;font-size:inherit" title="Edit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="backdrop"></div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
