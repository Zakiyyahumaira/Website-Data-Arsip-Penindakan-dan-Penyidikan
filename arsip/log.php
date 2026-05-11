<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekAdmin();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$total     = $pdo->query("SELECT COUNT(*) FROM log_aktivitas")->fetchColumn();
$totalPage = ceil($total / $perPage);

$logs = $pdo->query(
    "SELECT l.*, u.nama AS nama_user
     FROM log_aktivitas l
     LEFT JOIN users u ON l.user_id = u.id
     ORDER BY l.waktu DESC
     LIMIT $perPage OFFSET $offset"
)->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas — Arsip Kantor</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .action-link:hover { text-decoration: underline; }
        .action-links { display: flex; flex-direction: column; gap: 8px; }
        .action-link { display: flex; align-items: center; gap: 6px; padding: 0; background: transparent; border: none; cursor: pointer; font-size: inherit; color: inherit; text-decoration: none; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <button id="toggleSidebar" class="hamburger-btn">☰</button>
            <h1>Log Aktivitas</h1>
            <div class="topbar-actions">
                <?php if ($total > 0): ?>
                <form method="GET" action="hapus_log.php" style="display:inline;">
                    <input type="hidden" name="action" value="all">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus SEMUA log aktivitas?')" title="Hapus Semua Log">🗑 Hapus Semua</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-body">
            <?php if ($msg === 'hapus'): ?><div class="alert alert-success" data-dismiss="4000">Log aktivitas berhasil dihapus.</div>
            <?php elseif ($msg === 'hapus_semua'): ?><div class="alert alert-success" data-dismiss="4000">Semua log aktivitas berhasil dihapus.</div>
            <?php elseif ($msg === 'error'): ?><div class="alert alert-danger" data-dismiss="4000">Error: <?= htmlspecialchars($_GET['detail'] ?? 'Terjadi kesalahan') ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pengguna</th>
                                <th>Aktivitas</th>
                                <th>ID Arsip</th>
                                <th>Waktu</th>
                                <th style="width:70px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:#9ca3af">Belum ada log aktivitas</td></tr>
                        <?php else: ?>
                        <?php foreach ($logs as $i => $l): ?>
                        <tr>
                            <td style="color:#9ca3af"><?= $offset + $i + 1 ?></td>
                            <td><strong><?= sanitize($l['nama_user'] ?? 'Sistem') ?></strong></td>
                            <td><?= sanitize($l['aksi']) ?></td>
                            <td>
                                <?php if ($l['arsip_id']): ?>
                                <a href="detail.php?id=<?= $l['arsip_id'] ?>">#<?= $l['arsip_id'] ?></a>
                                <?php else: ?>
                                <span style="color:#9ca3af">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12px;color:#6b7280;white-space:nowrap">
                                <?= date('d/m/Y H:i', strtotime($l['waktu'])) ?>
                            </td>
                            <td>
                                <div class="action-links">
                                    <form method="GET" action="hapus_log.php" style="display:inline;">
                                        <input type="hidden" name="action" value="single">
                                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                        <button type="submit" class="action-link" style="color:#dc2626;" onclick="return confirm('Hapus log aktivitas ini?')" title="Hapus">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPage > 1): ?>
                <div style="padding:16px 20px;border-top:1px solid #e5e7eb">
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>" class="page-btn">&lsaquo; Sebelumnya</a>
                        <?php endif; ?>
                        <?php for ($p = max(1,$page-2); $p <= min($totalPage,$page+2); $p++): ?>
                        <a href="?page=<?= $p ?>" class="page-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPage): ?>
                        <a href="?page=<?= $page+1 ?>" class="page-btn">Berikutnya &rsaquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="backdrop"></div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
