<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekAdmin();

$errors = [];
$msg    = $_GET['msg'] ?? '';
$tab    = $_GET['tab'] ?? 'jenis'; // jenis | wilayah
$show   = $_GET['show'] ?? '';

// Tampilkan form tambah jika proses POST tambah jenis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah_jenis') {
    $show = 'tambah';
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
    $tab  = 'wilayah';
    $show = 'tambah_wilayah';
    $nama = trim($_POST['nama_wilayah'] ?? '');
    if (!$nama) { $errors[] = 'Nama wilayah wajib diisi.'; }
    else {
        $pdo->prepare("INSERT INTO wilayah (nama_wilayah) VALUES (?)")->execute([$nama]);
        header('Location: kategori.php?tab=wilayah&msg=tambah'); exit;
    }
}

// ── KECAMATAN ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah_kecamatan') {
    $tab  = 'wilayah';
    $show = 'tambah_kecamatan';
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
    <title>Jenis Pelanggaran — Arsip Kantor</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .tab-bar { display:flex; gap:4px; margin-bottom:20px; }
        .tab-btn { padding:9px 20px; border-radius:var(--radius); font-size:14px; font-weight:500;
                   border:1px solid var(--gray-200); background:#fff; cursor:pointer; color:var(--gray-600); text-decoration:none; }
        .tab-btn.active { background:var(--primary); color:#fff; border-color:var(--primary); }
        .action-link:hover { text-decoration: underline; }
        .action-links { display: flex; flex-direction: column; gap: 8px; }
        .action-link { display: flex; align-items: center; gap: 6px; padding: 0; background: transparent; border: none; cursor: pointer; font-size: inherit; color: inherit; text-decoration: none; }
        .modal-backdrop { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(15,23,42,0.55); opacity: 0; pointer-events: none; transition: opacity .2s ease; z-index: 1000; }
        .modal-backdrop.open { opacity: 1; pointer-events: auto; }
        .modal-panel { width: min(95vw, 520px); background: #ffffff; border-radius: 18px; box-shadow: 0 20px 60px rgba(15,23,42,.18); transform: translateY(-20px); transition: transform .2s ease; overflow: hidden; }
        .modal-backdrop.open .modal-panel { transform: translateY(0); }
        .modal-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 20px; border-bottom: 1px solid #e5e7eb; }
        .modal-title { margin: 0; font-size: 18px; font-weight: 700; }
        .modal-body { padding: 20px; }
        .modal-close { width: 36px; height: 36px; border-radius: 12px; border: none; background: #f3f4f6; cursor: pointer; font-size: 18px; color: #111827; }
        .modal-close:hover { background: #e5e7eb; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require '../config/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button id="toggleSidebar" class="hamburger-btn">☰</button>
            <h1><?= $tab === 'wilayah' ? 'Wilayah & Kecamatan' : 'Jenis Pelanggaran' ?></h1>
            <div class="topbar-actions">
                <?php if ($tab === 'jenis'): ?>
                <?php endif; ?>
            </div>
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
            <div style="display:grid;grid-template-columns:1fr;gap:20px;align-items:start">
                <div class="card">
                    <div class="card-header" style="flex-direction: row; justify-content: space-between; align-items: center;">
                        <h3>Daftar Jenis Pelanggaran</h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openJenisModal()">+ Tambah Jenis Pelanggaran</button>
                    </div>
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
            <div style="display:grid;grid-template-columns:1fr;gap:20px;align-items:start">
                <!-- Ringkasan wilayah -->
                <div class="card">
                    <div class="card-header" style="flex-direction: row; justify-content: space-between; align-items: center;">
                        <h3 style="margin:0;">Ringkasan Wilayah</h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openWilayahModal()">+ Tambah Wilayah</button>
                    </div>
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

                <!-- Daftar kecamatan -->
                <div class="card">
                    <div class="card-header" style="flex-direction: row; justify-content: space-between; align-items: center;">
                        <h3 style="margin:0;">Daftar Kecamatan (<?= count($kecamatans) ?>)</h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openKecamatanModal()">+ Tambah Kecamatan</button>
                    </div>
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
                                <td style="text-align:center;">
                                    <?php if ($k['total'] == 0): ?>
                                    <div class="action-links" style="align-items:center;">
                                        <a href="kategori.php?tab=wilayah&hapus_kecamatan=<?= $k['id'] ?>"
                                           onclick="return confirm('Hapus kecamatan ini?')"
                                           class="action-link" style="color:#dc2626;" title="Hapus">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                            Hapus
                                        </a>
                                    </div>
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
    <div class="backdrop"></div>
    <div id="jenisModal" class="modal-backdrop" onclick="closeJenisModal(event)">
        <div class="modal-panel" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2 class="modal-title">Tambah Jenis Pelanggaran</h2>
                <button type="button" class="modal-close" onclick="closeJenisModal()">×</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="jenisForm">
                    <input type="hidden" name="action" value="tambah_jenis">
                    <div class="form-group">
                        <label class="form-label">Nama Pelanggaran *</label>
                        <input class="form-control" type="text" name="nama_pelanggaran" required placeholder="Nama jenis pelanggaran"
                               value="<?= sanitize($_POST['nama_pelanggaran'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" style="min-height:70px" placeholder="Keterangan singkat"><?= sanitize($_POST['deskripsi'] ?? '') ?></textarea>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;margin-top:16px">
                        <button type="button" class="btn btn-ghost" onclick="closeJenisModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="wilayahModal" class="modal-backdrop" onclick="closeWilayahModal(event)">
        <div class="modal-panel" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2 class="modal-title">Tambah Wilayah</h2>
                <button type="button" class="modal-close" onclick="closeWilayahModal()">×</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="wilayahForm">
                    <input type="hidden" name="action" value="tambah_wilayah">
                    <div class="form-group">
                        <label class="form-label">Nama Wilayah *</label>
                        <input class="form-control" type="text" name="nama_wilayah" required placeholder="Nama wilayah"
                               value="<?= sanitize($_POST['nama_wilayah'] ?? '') ?>">
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;margin-top:16px">
                        <button type="button" class="btn btn-ghost" onclick="closeWilayahModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="kecamatanModal" class="modal-backdrop" onclick="closeKecamatanModal(event)">
        <div class="modal-panel" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2 class="modal-title">Tambah Kecamatan</h2>
                <button type="button" class="modal-close" onclick="closeKecamatanModal()">×</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="kecamatanForm">
                    <input type="hidden" name="action" value="tambah_kecamatan">
                    <div class="form-group">
                        <label class="form-label">Wilayah *</label>
                        <select class="form-control" name="wilayah_id" required>
                            <option value="">-- Pilih Wilayah --</option>
                            <?php foreach ($wilayahs as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= (isset($_POST['wilayah_id']) && $_POST['wilayah_id'] == $w['id']) ? 'selected' : '' ?>><?= sanitize($w['nama_wilayah']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Kecamatan *</label>
                        <input class="form-control" type="text" name="nama_kecamatan" required placeholder="Nama kecamatan"
                               value="<?= sanitize($_POST['nama_kecamatan'] ?? '') ?>">
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;margin-top:16px">
                        <button type="button" class="btn btn-ghost" onclick="closeKecamatanModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
<script>
function openJenisModal() {
    document.getElementById('jenisModal').classList.add('open');
}
function closeJenisModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('jenisModal').classList.remove('open');
}
function openWilayahModal() {
    document.getElementById('wilayahModal').classList.add('open');
}
function closeWilayahModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('wilayahModal').classList.remove('open');
}
function openKecamatanModal() {
    document.getElementById('kecamatanModal').classList.add('open');
}
function closeKecamatanModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('kecamatanModal').classList.remove('open');
}
<?php if ($show === 'tambah'): ?>
openJenisModal();
<?php elseif ($show === 'tambah_wilayah'): ?>
openWilayahModal();
<?php elseif ($show === 'tambah_kecamatan'): ?>
openKecamatanModal();
<?php endif; ?>
</script>
</body>
</html>
