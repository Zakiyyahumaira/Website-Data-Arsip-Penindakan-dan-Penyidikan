<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekAdmin();

$errors = [];
$msg    = $_GET['msg'] ?? '';
$tab    = $_GET['tab'] ?? 'jenis'; // jenis | wilayah

// ── JENIS PELANGGARAN ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah_jenis') {
    $nama = trim($_POST['nama_pelanggaran'] ?? '');
    $desc = trim($_POST['deskripsi'] ?? '');
    if (!$nama) { $errors[] = 'Nama pelanggaran wajib diisi.'; }
    else {
        $pdo->prepare("INSERT INTO jenis_pelanggaran (nama_pelanggaran, deskripsi) VALUES (?,?)")->execute([$nama, $desc]);
        header('Location: kategori.php?tab=jenis&msg=tambah'); exit;
    }
}
if (isset($_GET['hapus_jenis'])) {
    $kid = (int)$_GET['hapus_jenis'];
    $pdo->prepare("UPDATE arsip SET jenis_pelanggaran_id = NULL WHERE jenis_pelanggaran_id = ?")->execute([$kid]);
    $pdo->prepare("DELETE FROM jenis_pelanggaran WHERE id = ?")->execute([$kid]);
    header('Location: kategori.php?tab=jenis&msg=hapus'); exit;
}

// ── WILAYAH ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah_wilayah') {
    $nama = trim($_POST['nama_wilayah'] ?? '');
    if (!$nama) { $errors[] = 'Nama wilayah wajib diisi.'; }
    else {
        $pdo->prepare("INSERT INTO wilayah (nama_wilayah) VALUES (?)")->execute([$nama]);
        header('Location: kategori.php?tab=wilayah&msg=tambah'); exit;
    }
}

// ── KECAMATAN ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah_kecamatan') {
    $wid  = (int)($_POST['wilayah_id'] ?? 0);
    $nama = trim($_POST['nama_kecamatan'] ?? '');
    if (!$wid || !$nama) { $errors[] = 'Wilayah dan nama kecamatan wajib diisi.'; }
    else {
        $pdo->prepare("INSERT INTO kecamatan (wilayah_id, nama_kecamatan) VALUES (?,?)")->execute([$wid, $nama]);
        header('Location: kategori.php?tab=wilayah&msg=tambah'); exit;
    }
}
if (isset($_GET['hapus_kecamatan'])) {
    $kid = (int)$_GET['hapus_kecamatan'];
    $pdo->prepare("UPDATE arsip SET kecamatan_id = NULL WHERE kecamatan_id = ?")->execute([$kid]);
    $pdo->prepare("DELETE FROM kecamatan WHERE id = ?")->execute([$kid]);
    header('Location: kategori.php?tab=wilayah&msg=hapus'); exit;
}

$jenisList = $pdo->query(
    "SELECT jp.*, COUNT(a.id) as total FROM jenis_pelanggaran jp
     LEFT JOIN arsip a ON a.jenis_pelanggaran_id = jp.id
     GROUP BY jp.id ORDER BY jp.nama_pelanggaran"
)->fetchAll();

$wilayahs = $pdo->query("SELECT * FROM wilayah ORDER BY id")->fetchAll();

$kecamatans = $pdo->query(
    "SELECT k.*, w.nama_wilayah, COUNT(a.id) as total
     FROM kecamatan k
     LEFT JOIN wilayah w ON k.wilayah_id = w.id
     LEFT JOIN arsip a ON a.kecamatan_id = k.id
     GROUP BY k.id ORDER BY w.id, k.nama_kecamatan"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referensi Data — Arsip Kantor</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .tab-bar { display:flex; gap:4px; margin-bottom:20px; }
        .tab-btn { padding:9px 20px; border-radius:var(--radius); font-size:14px; font-weight:500;
                   border:1px solid var(--gray-200); background:#fff; cursor:pointer; color:var(--gray-600); text-decoration:none; }
        .tab-btn.active { background:var(--primary); color:#fff; border-color:var(--primary); }
    </style>
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>
    <div class="main-content collapsed">
        <div class="topbar">
            <button id="toggleSidebar" class="btn btn-ghost btn-sm">☰</button>
            <h1>Referensi Data</h1>
        </div>
        <div class="page-body">
            <?php if ($msg === 'tambah'): ?><div class="alert alert-success" data-dismiss="3000">Data berhasil ditambahkan.</div>
            <?php elseif ($msg === 'hapus'): ?><div class="alert alert-success" data-dismiss="3000">Data berhasil dihapus.</div><?php endif; ?>
            <?php if (!empty($errors)): ?><div class="alert alert-danger"><?= sanitize($errors[0]) ?></div><?php endif; ?>

            <div class="tab-bar">
                <a href="kategori.php?tab=jenis" class="tab-btn <?= $tab==='jenis'?'active':'' ?>">Jenis Pelanggaran</a>
                <a href="kategori.php?tab=wilayah" class="tab-btn <?= $tab==='wilayah'?'active':'' ?>">Wilayah & Kecamatan</a>
            </div>

            <?php if ($tab === 'jenis'): ?>
            <!-- TAB JENIS PELANGGARAN -->
            <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:20px;align-items:start">
                <div class="card">
                    <div class="card-header"><h3>Tambah Jenis Pelanggaran</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="tambah_jenis">
                            <div class="form-group">
                                <label class="form-label">Nama Pelanggaran *</label>
                                <input class="form-control" type="text" name="nama_pelanggaran" required placeholder="Nama jenis pelanggaran">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control" name="deskripsi" style="min-height:70px" placeholder="Keterangan singkat"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h3>Daftar Jenis Pelanggaran</h3></div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>#</th><th>Nama Pelanggaran</th><th>Deskripsi</th><th>Arsip</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ($jenisList as $i => $j): ?>
                            <tr>
                                <td style="color:#9ca3af"><?= $i+1 ?></td>
                                <td><strong><?= sanitize($j['nama_pelanggaran']) ?></strong></td>
                                <td style="font-size:13px;color:#6b7280"><?= sanitize($j['deskripsi'] ?? '-') ?></td>
                                <td><span class="badge badge-blue"><?= $j['total'] ?></span></td>
                                <td>
                                    <?php if ($j['total'] == 0): ?>
                                    <a href="kategori.php?tab=jenis&hapus_jenis=<?= $j['id'] ?>"
                                       onclick="return confirm('Hapus jenis pelanggaran ini?')"
                                       class="btn btn-danger btn-sm">Hapus</a>
                                    <?php else: ?><span style="font-size:12px;color:#9ca3af">Ada arsip</span><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- TAB WILAYAH & KECAMATAN -->
            <div style="display:grid;grid-template-columns:1fr 1.8fr;gap:20px;align-items:start">
                <div style="display:flex;flex-direction:column;gap:16px">
                    <!-- Form tambah kecamatan -->
                    <div class="card">
                        <div class="card-header"><h3>Tambah Kecamatan</h3></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="tambah_kecamatan">
                                <div class="form-group">
                                    <label class="form-label">Wilayah *</label>
                                    <select class="form-control" name="wilayah_id" required>
                                        <option value="">-- Pilih Wilayah --</option>
                                        <?php foreach ($wilayahs as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= sanitize($w['nama_wilayah']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nama Kecamatan *</label>
                                    <input class="form-control" type="text" name="nama_kecamatan" required placeholder="Nama kecamatan">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                            </form>
                        </div>
                    </div>
                    <!-- Ringkasan wilayah -->
                    <div class="card">
                        <div class="card-header"><h3>Ringkasan Wilayah</h3></div>
                        <div class="card-body">
                            <?php foreach ($wilayahs as $w): ?>
                            <?php $jml = count(array_filter($kecamatans, fn($k) => $k['wilayah_id'] == $w['id'])); ?>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:14px">
                                <span><?= sanitize($w['nama_wilayah']) ?></span>
                                <span class="badge badge-gray"><?= $jml ?> kecamatan</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Daftar kecamatan -->
                <div class="card">
                    <div class="card-header"><h3>Daftar Kecamatan (<?= count($kecamatans) ?>)</h3></div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>#</th><th>Kecamatan</th><th>Wilayah</th><th>Arsip</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ($kecamatans as $i => $k): ?>
                            <tr>
                                <td style="color:#9ca3af"><?= $i+1 ?></td>
                                <td><?= sanitize($k['nama_kecamatan']) ?></td>
                                <td><span class="badge badge-amber"><?= sanitize($k['nama_wilayah']) ?></span></td>
                                <td><span class="badge badge-blue"><?= $k['total'] ?></span></td>
                                <td>
                                    <?php if ($k['total'] == 0): ?>
                                    <a href="kategori.php?tab=wilayah&hapus_kecamatan=<?= $k['id'] ?>"
                                       onclick="return confirm('Hapus kecamatan ini?')"
                                       class="btn btn-danger btn-sm">Hapus</a>
                                    <?php else: ?><span style="font-size:12px;color:#9ca3af">Ada arsip</span><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
