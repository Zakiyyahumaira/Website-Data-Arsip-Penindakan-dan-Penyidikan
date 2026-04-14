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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas — Arsip Kantor</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h1>Log Aktivitas</h1>
            <span style="font-size:13px;color:#6b7280"><?= $total ?> total aktivitas</span>
        </div>

        <div class="page-body">
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
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:40px;color:#9ca3af">Belum ada log aktivitas</td></tr>
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
</div>
<script src="../js/main.js"></script>
</body>
</html>
