<?php
session_start();
require 'config/database.php';
require 'config/functions.php';
cekLogin();

$totalArsip    = $pdo->query("SELECT COUNT(*) FROM arsip")->fetchColumn();
$totalJenis    = $pdo->query("SELECT COUNT(*) FROM jenis_pelanggaran")->fetchColumn();
$totalUser     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$arsipBulanIni = $pdo->query("SELECT COUNT(*) FROM arsip WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

$totalMap = $pdo->query("SELECT COUNT(*) FROM map")->fetchColumn();

$arsipTerbaru = $pdo->query(
    "SELECT a.*, jp.nama_pelanggaran, w.nama_wilayah, m.nama_map, u.nama AS uploader,
            p1.nama AS petugas_1, p2.nama AS petugas_2
     FROM arsip a
     LEFT JOIN petugas p1 ON a.petugas_1_id = p1.id
     LEFT JOIN petugas p2 ON a.petugas_2_id = p2.id
     LEFT JOIN jenis_pelanggaran jp ON a.jenis_pelanggaran_id = jp.id
     LEFT JOIN wilayah w            ON a.wilayah_id = w.id
     LEFT JOIN map m               ON a.map_id = m.id
     LEFT JOIN users u              ON a.diunggah_oleh = u.id
     ORDER BY a.created_at DESC LIMIT 8"
)->fetchAll();

$perJenis = $pdo->query(
    "SELECT jp.nama_pelanggaran, COUNT(a.id) as total
     FROM jenis_pelanggaran jp
     LEFT JOIN arsip a ON a.jenis_pelanggaran_id = jp.id
     GROUP BY jp.id ORDER BY total DESC"
)->fetchAll();

$perWilayah = $pdo->query(
    "SELECT w.nama_wilayah, COUNT(a.id) as total
     FROM wilayah w
     LEFT JOIN arsip a ON a.wilayah_id = w.id
     GROUP BY w.id ORDER BY total DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Data Arsip</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<!-- App Layout Container - Responsive Sidebar -->
<div class="app-layout">
    <?php require 'config/sidebar.php'; ?>
    
    <!-- Main Content -->
    <!-- Default: margin-left: 240px (sidebar visible) -->
    <!-- Tambahkan class .sidebar-collapsed untuk mode collapse -->
    <div class="main-content">
        <div class="topbar">
            <!-- Hamburger button is now in sidebar.php -->
            <button id="toggleSidebar" class="hamburger-btn">☰</button>
            <h1>Dashboard</h1>
            <div class="topbar-actions">
                <span style="font-size:13px;color:#6b7280"><?= date('l, d F Y') ?></span>
                <a href="arsip/tambah.php" class="btn btn-primary btn-sm">+ Input Arsip</a>
            </div>
        </div>

        <div class="page-body">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></div>
                    <div><div class="stat-num"><?= $totalArsip ?></div><div class="stat-label">Total Arsip</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
                    <div><div class="stat-num"><?= $arsipBulanIni ?></div><div class="stat-label">Arsip Bulan Ini</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
                    <div><div class="stat-num"><?= $totalJenis ?></div><div class="stat-label">Jenis Pelanggaran</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon violet"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg></div>
                    <div><div class="stat-num"><?= $totalMap ?></div><div class="stat-label">Total Map</div></div>
                </div>
            </div>
        


            <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;align-items:flex-start">
                <!-- Arsip terbaru -->
                <div class="card">
                    <div class="card-header" style="flex-direction: row; justify-content: space-between; align-items: center;">
                        <h3>Arsip Terbaru</h3>
                        <a href="arsip/daftar.php" class="btn btn-primary btn-sm">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div class="table-wrap">
                            <table>
                            <thead><tr><th>No. Surat</th><th>Petugas</th><th>Map</th><th>Jenis Pelanggaran</th><th>Wilayah</th><th>Tanggal</th></tr></thead>
                            <tbody>
                            <?php if (empty($arsipTerbaru)): ?>
                                <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:30px">Belum ada arsip</td></tr>
                            <?php else: ?>
                                <?php foreach ($arsipTerbaru as $a): ?>
                                <tr>
                                    <td><code style="font-size:12px;padding:2px 6px;border-radius:4px;font-family:'Poppins',sans-serif"><?= sanitize($a['no_surat']) ?></code></td>
                                    <td><a href="arsip/detail.php?id=<?= $a['id'] ?>"><?= sanitize($a['petugas_1'] . ' / ' . $a['petugas_2']) ?></a></td>
                                    <td style="font-size:13px"><?= sanitize($a['nama_map'] ?? '-') ?></td>
                                    <td><span class="badge badge-blue"><?= sanitize($a['nama_pelanggaran'] ?? '-') ?></span></td>
                                    <td style="font-size:13px"><?= sanitize($a['nama_wilayah'] ?? '-') ?></td>
                                    <td style="color:#6b7280;font-size:13px"><?= formatTanggal(substr($a['created_at'],0,10)) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <!-- Sidebar charts -->
                <div style="display:flex;flex-direction:column;gap:16px">
                    <!-- Per Jenis Pelanggaran -->
                    <div class="card">
                        <div class="card-header"><h3>Jenis Pelanggaran</h3></div>
                        <div class="card-body">
                            <?php foreach ($perJenis as $j): ?>
                            <?php $pct = $totalArsip > 0 ? round($j['total'] / $totalArsip * 100) : 0; ?>
                            <div style="margin-bottom:12px">
                                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px">
                                    <span style="color:#374151"><?= sanitize($j['nama_pelanggaran']) ?></span>
                                    <span style="color:#6b7280"><?= $j['total'] ?></span>
                                </div>
                                <div style="background:#e5e7eb;border-radius:6px;height:7px">
                                    <div style="width:<?= $pct ?>%;background:#2563eb;height:7px;border-radius:6px"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($perJenis)): ?><p style="color:#9ca3af;font-size:13px;text-align:center">Belum ada data</p><?php endif; ?>
                        </div>
                    </div>

                    <!-- Per Wilayah -->
                    <div class="card">
                        <div class="card-header"><h3>Wilayah</h3></div>
                        <div class="card-body">
                            <?php foreach ($perWilayah as $w): ?>
                            <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #f3f4f6;font-size:13px">
                                <span><?= sanitize($w['nama_wilayah']) ?></span>
                                <span class="badge badge-gray"><?= $w['total'] ?> arsip</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<div class="backdrop"></div>
</div>
<!-- End .app-layout -->

<!-- Load Sidebar JavaScript -->
<script src="js/main.js"></script>
</body>
</html> 
