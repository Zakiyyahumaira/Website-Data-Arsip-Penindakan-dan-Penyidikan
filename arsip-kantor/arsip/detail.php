<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: daftar.php'); exit; }

$stmt = $pdo->prepare(
    "SELECT a.*, jp.nama_pelanggaran, w.nama_wilayah, k.nama_kecamatan, u.nama AS uploader
     FROM arsip a
     LEFT JOIN jenis_pelanggaran jp ON a.jenis_pelanggaran_id = jp.id
     LEFT JOIN wilayah w            ON a.wilayah_id = w.id
     LEFT JOIN kecamatan k          ON a.kecamatan_id = k.id
     LEFT JOIN users u              ON a.diunggah_oleh = u.id
     WHERE a.id = ?"
);
$stmt->execute([$id]);
$arsip = $stmt->fetch();
if (!$arsip) { header('Location: daftar.php'); exit; }

logAktivitas($pdo, $_SESSION['user_id'], 'Lihat arsip: ' . $arsip['nama_pegawai'], $id);

$ext = strtolower(pathinfo($arsip['file_name'] ?? '', PATHINFO_EXTENSION));
$fileIcons = ['pdf'=>'📄','doc'=>'📝','docx'=>'📝','xls'=>'📊','xlsx'=>'📊','jpg'=>'🖼','jpeg'=>'🖼','png'=>'🖼'];
$fileIcon  = $fileIcons[$ext] ?? '📎';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($arsip['nama_pegawai']) ?> — Arsip Kantor</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .info-row { display:flex; border-bottom:1px solid #f3f4f6; padding:12px 0; gap:16px; }
        .info-row:last-child { border-bottom:none; }
        .info-label { width:180px; flex-shrink:0; font-size:13px; font-weight:600; color:#6b7280; }
        .info-value { flex:1; font-size:14px; color:#1f2937; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <h1>Detail Arsip</h1>
            <div class="topbar-actions">
                <a href="daftar.php" class="btn btn-ghost btn-sm">&larr; Kembali</a>
                <a href="edit.php?id=<?= $arsip['id'] ?>" class="btn btn-outline btn-sm">&#9998; Edit</a>
                <button onclick="konfirmasiHapus(<?= $arsip['id'] ?>, '<?= addslashes(sanitize($arsip['nama_pegawai'])) ?>')"
                    class="btn btn-danger btn-sm">&#128465; Hapus</button>
            </div>
        </div>

        <div class="page-body">
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">

                <!-- Info utama -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3><?= sanitize($arsip['nama_pegawai']) ?></h3>
                            <code style="font-size:12px;background:#f3f4f6;padding:2px 8px;border-radius:4px;margin-top:4px;display:inline-block">
                                <?= sanitize($arsip['no_surat']) ?>
                            </code>
                        </div>
                        <span class="badge badge-blue"><?= sanitize($arsip['nama_pelanggaran'] ?? '-') ?></span>
                    </div>
                    <div class="card-body" style="padding:0 20px">
                        <div class="info-row">
                            <div class="info-label">No. Surat</div>
                            <div class="info-value"><code><?= sanitize($arsip['no_surat']) ?></code></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nama Pegawai</div>
                            <div class="info-value"><strong><?= sanitize($arsip['nama_pegawai']) ?></strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Jenis Pelanggaran</div>
                            <div class="info-value">
                                <span class="badge badge-blue"><?= sanitize($arsip['nama_pelanggaran'] ?? '-') ?></span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Wilayah</div>
                            <div class="info-value"><?= sanitize($arsip['nama_wilayah'] ?? '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Kecamatan</div>
                            <div class="info-value"><?= sanitize($arsip['nama_kecamatan'] ?? '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nama Tempat</div>
                            <div class="info-value"><?= $arsip['nama_tempat'] ? sanitize($arsip['nama_tempat']) : '<span style="color:#9ca3af">-</span>' ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Jumlah</div>
                            <div class="info-value">
                                <?php if ($arsip['jumlah'] !== null): ?>
                                    <strong><?= formatJumlah($arsip['jumlah'], $arsip['satuan']) ?></strong>
                                <?php else: ?>
                                    <span style="color:#9ca3af">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Tanggal Dokumen</div>
                            <div class="info-value"><?= formatTanggal($arsip['tanggal_dokumen']) ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Tanggal Upload</div>
                            <div class="info-value"><?= formatTanggal(substr($arsip['created_at'], 0, 10)) ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Diunggah Oleh</div>
                            <div class="info-value"><?= sanitize($arsip['uploader'] ?? '-') ?></div>
                        </div>
                        <?php if ($arsip['deskripsi']): ?>
                        <div class="info-row">
                            <div class="info-label">Keterangan</div>
                            <div class="info-value" style="line-height:1.7"><?= nl2br(sanitize($arsip['deskripsi'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- File & aksi -->
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div class="card">
                        <div class="card-header"><h3>File Dokumen</h3></div>
                        <div class="card-body">
                            <?php if ($arsip['file_path']): ?>
                            <div style="text-align:center;padding:20px 0">
                                <div style="font-size:48px;margin-bottom:10px"><?= $fileIcon ?></div>
                                <p style="font-size:13px;color:#374151;margin-bottom:4px;word-break:break-all"><?= sanitize($arsip['file_name']) ?></p>
                                <p style="font-size:12px;color:#9ca3af;margin-bottom:16px"><?= strtoupper($ext) ?> file</p>
                                <a href="../<?= $arsip['file_path'] ?>" target="_blank"
                                   class="btn btn-primary" style="justify-content:center;width:100%">
                                    &#11015; Unduh / Buka File
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="empty-state" style="padding:30px 0"><p>Tidak ada file terlampir</p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><h3>Aksi Cepat</h3></div>
                        <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
                            <a href="edit.php?id=<?= $arsip['id'] ?>" class="btn btn-outline" style="justify-content:center">&#9998; Edit Arsip</a>
                            <button onclick="window.print()" class="btn btn-ghost" style="justify-content:center">&#128424; Cetak Detail</button>
                            <button onclick="konfirmasiHapus(<?= $arsip['id'] ?>, '<?= addslashes(sanitize($arsip['nama_pegawai'])) ?>')"
                                class="btn btn-danger" style="justify-content:center">&#128465; Hapus Arsip</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
