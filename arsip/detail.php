<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: daftar.php'); exit; }

$stmt = $pdo->prepare(
    "SELECT a.*, jp.nama_pelanggaran, w.nama_wilayah, k.nama_kecamatan, u.nama AS uploader,
            p1.nama AS petugas_1, p1.nip AS petugas_1_nip, p1.pangkat AS petugas_1_pangkat, p1.jabatan AS petugas_1_jabatan,
            p2.nama AS petugas_2, p2.nip AS petugas_2_nip, p2.pangkat AS petugas_2_pangkat, p2.jabatan AS petugas_2_jabatan
     FROM arsip a
     LEFT JOIN petugas p1 ON a.petugas_1_id = p1.id
     LEFT JOIN petugas p2 ON a.petugas_2_id = p2.id
     LEFT JOIN jenis_pelanggaran jp ON a.jenis_pelanggaran_id = jp.id
     LEFT JOIN wilayah w            ON a.wilayah_id = w.id
     LEFT JOIN kecamatan k          ON a.kecamatan_id = k.id
     LEFT JOIN users u              ON a.diunggah_oleh = u.id
     WHERE a.id = ?"
);
$stmt->execute([$id]);
$arsip = $stmt->fetch();
if (!$arsip) { header('Location: daftar.php'); exit; }

// Ambil data pelaku
$pelakuList = $pdo->prepare("SELECT * FROM pelaku WHERE arsip_id = ? ORDER BY id");
$pelakuList->execute([$id]);
$pelakuList = $pelakuList->fetchAll();

// Ambil data barang hasil penindakan
$barangList = $pdo->prepare("SELECT * FROM barang_hasil_penindakan WHERE arsip_id = ? ORDER BY id");
$barangList->execute([$id]);
$barangList = $barangList->fetchAll();

logAktivitas($pdo, $_SESSION['user_id'], 'Lihat arsip: ' . ($arsip['petugas_1'] ?? $arsip['nama_pegawai']) . ' / ' . ($arsip['petugas_2'] ?? '-'), $id);

$ext = strtolower(pathinfo($arsip['file_name'] ?? '', PATHINFO_EXTENSION));
$fileIcons = ['pdf'=>'📄','doc'=>'📝','docx'=>'📝','xls'=>'📊','xlsx'=>'📊','jpg'=>'🖼','jpeg'=>'🖼','png'=>'🖼'];
$fileIcon  = $fileIcons[$ext] ?? '📎';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize(($arsip['petugas_1'] ?? '-') . ' / ' . ($arsip['petugas_2'] ?? '-')) ?> — Arsip Kantor</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .info-row { display:flex; border-bottom:1px solid #f3f4f6; padding:12px 0; gap:16px; }
        .info-row:last-child { border-bottom:none; }
        .info-label { width:180px; flex-shrink:0; font-size:13px; font-weight:600; color:#6b7280; }
        .info-value { flex:1; font-size:14px; color:#1f2937; }
        .table-list { width:100%; border-collapse:collapse; font-size:13px; margin-top:12px; }
        .table-list th { background:#f3f4f6; padding:8px; text-align:left; font-weight:600; border:1px solid #e5e7eb; }
        .table-list td { padding:8px; border:1px solid #e5e7eb; }
        .table-list tr:nth-child(even) { background:#fafafa; }
        .repeat-section { margin-top:12px; }
        .entry-card { border:1px solid #d1d5db; border-radius:10px; padding:18px; margin-bottom:16px; background:#ffffff; }
        .entry-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:12px; }
        .entry-header h5 { margin:0; font-size:15px; color:#111827; }
        .entry-group { display:grid; gap:14px; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
        .field-item { display:flex; flex-direction:column; gap:6px; }
        .field-label { font-size:13px; font-weight:600; color:#4b5563; }
        .field-value { font-size:14px; color:#111827; line-height:1.6; white-space:pre-line; }
        .field-value.empty { color:#6b7280; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>
    <div class="main-content collapsed">
        <div class="topbar">
            <button id="toggleSidebar" class="btn btn-ghost btn-sm">☰</button>
            <h1>Detail Arsip</h1>
            <div class="topbar-actions">
                <a href="daftar.php" class="btn btn-ghost btn-sm">&larr; Kembali</a>
                <a href="edit.php?id=<?= $arsip['id'] ?>" class="btn btn-outline btn-sm">&#9998; Edit</a>
                <button onclick="konfirmasiHapus(<?= $arsip['id'] ?>, '<?= addslashes(sanitize(($arsip['petugas_1'] ?? $arsip['nama_pegawai']) . ' / ' . ($arsip['petugas_2'] ?? '-'))) ?>')"
                    class="btn btn-danger btn-sm">&#128465; Hapus</button>
            </div>
        </div>

        <div class="page-body">
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">

                <!-- Info utama -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3><?= sanitize(($arsip['petugas_1'] ?? '-') . ' / ' . ($arsip['petugas_2'] ?? '-')) ?></h3>
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
                            <div class="info-label">Petugas 1</div>
                            <div class="info-value">
                                <strong><?= sanitize($arsip['petugas_1'] ?? $arsip['nama_pegawai']) ?></strong><br>
                                <small>NIP: <?= sanitize($arsip['petugas_1_nip'] ?? '-') ?></small><br>
                                <small>Pangkat: <?= sanitize($arsip['petugas_1_pangkat'] ?? '-') ?></small><br>
                                <small>Jabatan: <?= sanitize($arsip['petugas_1_jabatan'] ?? '-') ?></small>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Petugas 2</div>
                            <div class="info-value">
                                <strong><?= sanitize($arsip['petugas_2'] ?? '-') ?></strong><br>
                                <small>NIP: <?= sanitize($arsip['petugas_2_nip'] ?? '-') ?></small><br>
                                <small>Pangkat: <?= sanitize($arsip['petugas_2_pangkat'] ?? '-') ?></small><br>
                                <small>Jabatan: <?= sanitize($arsip['petugas_2_jabatan'] ?? '-') ?></small>
                            </div>
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
                            <div class="info-label">Waktu Penindakan</div>
                            <div class="info-value"><?= $arsip['waktu_penindakan'] ? sanitize($arsip['waktu_penindakan']) . ' WIB' : '<span style="color:#9ca3af">-</span>' ?></div>
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
                        <?php if ($arsip['deskripsi']): ?>
                        <div class="info-row">
                            <div class="info-label">Jenis Uraian Barang</div>
                            <div class="info-value" style="line-height:1.7"><?= nl2br(sanitize($arsip['deskripsi'])) ?></div>
                        </div>
                        <?php endif; ?>
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
                        
                        <!-- Data Pelaku -->
                        <?php if (!empty($pelakuList)): ?>
                        <div style="margin-top:20px;padding-top:20px;border-top:2px solid #e5e7eb">
                            <h4 style="margin:0 0 12px 0;color:#1f2937">👤 Data Pelaku</h4>
                            <div class="repeat-section">
                                <?php foreach ($pelakuList as $idx => $p): ?>
                                <div class="entry-card">
                                    <div class="entry-header">
                                        <h5>Pelaku #<?= $idx + 1 ?></h5>
                                    </div>
                                    <div class="entry-group">
                                        <div class="field-item">
                                            <div class="field-label">Nama</div>
                                            <div class="field-value"><?= sanitize($p['nama']) ?></div>
                                        </div>
                                        <div class="field-item">
                                            <div class="field-label">Identitas</div>
                                            <div class="field-value"><?= sanitize($p['identitas']) ?></div>
                                        </div>
                                        <div class="field-item">
                                            <div class="field-label">No. Identitas</div>
                                            <div class="field-value"><?= sanitize($p['no_identitas']) ?></div>
                                        </div>
                                        <div class="field-item">
                                            <div class="field-label">Jenis Kelamin</div>
                                            <div class="field-value"><?= sanitize($p['jenis_kelamin']) ?></div>
                                        </div>
                                        <div class="field-item" style="grid-column:1 / -1">
                                            <div class="field-label">Alamat</div>
                                            <div class="field-value"><?= sanitize($p['alamat']) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Data Barang Hasil Penindakan -->
                        <?php if (!empty($barangList)): ?>
                        <div style="margin-top:20px;padding-top:20px;border-top:2px solid #e5e7eb">
                            <h4 style="margin:0 0 12px 0;color:#1f2937">📦 Barang Hasil Penindakan</h4>
                            <div class="repeat-section">
                                <?php foreach ($barangList as $idx => $b): ?>
                                <div class="entry-card">
                                    <div class="entry-header">
                                        <h5>Barang #<?= $idx + 1 ?></h5>
                                    </div>
                                    <div class="entry-group">
                                        <div class="field-item">
                                            <div class="field-label">Nama Barang</div>
                                            <div class="field-value"><?= sanitize($b['nama_barang']) ?></div>
                                        </div>
                                        <div class="field-item">
                                            <div class="field-label">Jenis Barang</div>
                                            <div class="field-value"><?= sanitize($b['jenis_barang']) ?></div>
                                        </div>
                                        <div class="field-item">
                                            <div class="field-label">Jumlah</div>
                                            <div class="field-value"><?= formatJumlah($b['jumlah_barang']) ?></div>
                                        </div>
                                        <div class="field-item">
                                            <div class="field-label">Satuan</div>
                                            <div class="field-value"><?= sanitize($b['satuan']) ?></div>
                                        </div>
                                        <div class="field-item" style="grid-column:1 / -1">
                                            <div class="field-label">Jenis Uraian Barang</div>
                                            <div class="field-value"><?= sanitize($b['jenis_uraian_barang'] ?? '-') ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
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
                            <button onclick="konfirmasiHapus(<?= $arsip['id'] ?>, '<?= addslashes(sanitize(($arsip['petugas_1'] ?? $arsip['nama_pegawai']) . ' / ' . ($arsip['petugas_2'] ?? '-'))) ?>')"
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
