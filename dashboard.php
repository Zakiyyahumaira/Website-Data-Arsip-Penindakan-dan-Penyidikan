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
    "SELECT a.*, jp.nama_pelanggaran, w.nama_wilayah, m.nama_map, u.nama AS uploader
     FROM arsip a
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
    <title>Dashboard — Arsip Kantor</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="wrapper">
    <?php require 'config/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <h1>Dashboard</h1>
            <div class="topbar-actions">
                <span style="font-size:13px;color:#6b7280"><?= date('l, d F Y') ?></span>
                <a href="arsip/tambah.php" class="btn btn-primary btn-sm">+ Upload Arsip</a>
            </div>
        </div>

        <div class="page-body">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">&#128193;</div>
                    <div><div class="stat-num"><?= $totalArsip ?></div><div class="stat-label">Total Arsip</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">&#128200;</div>
                    <div><div class="stat-num"><?= $arsipBulanIni ?></div><div class="stat-label">Arsip Bulan Ini</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber">&#9888;</div>
                    <div><div class="stat-num"><?= $totalJenis ?></div><div class="stat-label">Jenis Pelanggaran</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon violet">&#128194;</div>
                    <div><div class="stat-num"><?= $totalMap ?></div><div class="stat-label">Total Map</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">&#128101;</div>
                    <div><div class="stat-num"><?= $totalUser ?></div><div class="stat-label">Pengguna</div></div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;align-items:start">
                <!-- Arsip terbaru -->
                <div class="card">
                    <div class="card-header">
                        <h3>Arsip Terbaru</h3>
                        <a href="arsip/daftar.php" class="btn btn-ghost btn-sm">Lihat Semua</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>No. Surat</th><th>Nama Pegawai</th><th>Map</th><th>Jenis Pelanggaran</th><th>Wilayah</th><th>Tanggal</th></tr></thead>
                            <tbody>
                            <?php if (empty($arsipTerbaru)): ?>
                                <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:30px">Belum ada arsip</td></tr>
                            <?php else: ?>
                                <?php foreach ($arsipTerbaru as $a): ?>
                                <tr>
                                    <td><code style="font-size:12px;background:#f3f4f6;padding:2px 6px;border-radius:4px"><?= sanitize($a['no_surat']) ?></code></td>
                                    <td><a href="arsip/detail.php?id=<?= $a['id'] ?>"><?= sanitize($a['nama_pegawai']) ?></a></td>
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

                <!-- Sidebar charts -->
                <div style="display:flex;flex-direction:column;gap:16px">
                    <!-- Per Jenis Pelanggaran -->
                    <div class="card">
                        <div class="card-header"><h3>Per Jenis Pelanggaran</h3></div>
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
                        <div class="card-header"><h3>Per Wilayah</h3></div>
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
</div>
<script src="js/main.js"></script>
</body>
</html>
