<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: daftar.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM arsip WHERE id = ?");
$stmt->execute([$id]);
$arsip = $stmt->fetch();
if (!$arsip) { header('Location: daftar.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noSurat     = trim($_POST['no_surat'] ?? '');
    $namaPegawai = trim($_POST['nama_pegawai'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $jenisId     = (int)($_POST['jenis_pelanggaran_id'] ?? 0);
    $wilayahId   = (int)($_POST['wilayah_id'] ?? 0);
    $kecamatanId = (int)($_POST['kecamatan_id'] ?? 0);
    $namaTempat  = trim($_POST['nama_tempat'] ?? '');
    $jumlah      = $_POST['jumlah'] !== '' ? $_POST['jumlah'] : null;
    $satuan      = trim($_POST['satuan'] ?? '');
    $tglDokumen  = $_POST['tanggal_dokumen'] ?? '';

    if (!$noSurat)     $errors[] = 'Nomor surat wajib diisi.';
    if (!$namaPegawai) $errors[] = 'Nama pegawai wajib diisi.';
    if (!$jenisId)     $errors[] = 'Jenis pelanggaran wajib dipilih.';
    if (!$wilayahId)   $errors[] = 'Wilayah wajib dipilih.';

    if ($noSurat && $noSurat !== $arsip['no_surat']) {
        $cek = $pdo->prepare("SELECT id FROM arsip WHERE no_surat = ? AND id != ?");
        $cek->execute([$noSurat, $id]);
        if ($cek->fetch()) $errors[] = 'Nomor surat sudah digunakan.';
    }

    $filePath = $arsip['file_path'];
    $fileName = $arsip['file_name'];
    if (!empty($_FILES['file']['name'])) {
        $upload = uploadFile($_FILES['file'], '../uploads/');
        if (!$upload['ok']) {
            $errors[] = $upload['msg'];
        } else {
            if ($filePath && file_exists('../' . $filePath)) @unlink('../' . $filePath);
            $filePath = $upload['path'];
            $fileName = $upload['name'];
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "UPDATE arsip SET
             no_surat=?, nama_pegawai=?, deskripsi=?, jenis_pelanggaran_id=?,
             wilayah_id=?, kecamatan_id=?, nama_tempat=?, jumlah=?, satuan=?,
             tanggal_dokumen=?, file_path=?, file_name=?
             WHERE id=?"
        );
        $stmt->execute([
            $noSurat, $namaPegawai, $deskripsi, $jenisId,
            $wilayahId ?: null, $kecamatanId ?: null, $namaTempat,
            $jumlah, $satuan, $tglDokumen ?: null,
            $filePath, $fileName, $id
        ]);
        logAktivitas($pdo, $_SESSION['user_id'], 'Edit arsip: ' . $namaPegawai, $id);
        header('Location: daftar.php?msg=edit');
        exit;
    }

    // Kembalikan ke form dengan nilai POST
    $arsip = array_merge($arsip, $_POST, ['file_path' => $filePath, 'file_name' => $fileName]);
}

$jenisList  = $pdo->query("SELECT * FROM jenis_pelanggaran ORDER BY nama_pelanggaran")->fetchAll();
$wilayahs   = $pdo->query("SELECT * FROM wilayah ORDER BY id")->fetchAll();
$kecamatans = $arsip['wilayah_id'] ? getKecamatanByWilayah($pdo, $arsip['wilayah_id']) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Arsip — Arsip Kantor</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <h1>Edit Arsip</h1>
            <div class="topbar-actions">
                <a href="detail.php?id=<?= $id ?>" class="btn btn-ghost btn-sm">&larr; Kembali</a>
            </div>
        </div>

        <div class="page-body">
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin:0;padding-left:18px">
                    <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="card" style="max-width:820px">
                <div class="card-header"><h3>Edit Data Arsip</h3></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">No. Surat *</label>
                                <input class="form-control" type="text" name="no_surat"
                                       value="<?= sanitize($arsip['no_surat']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tanggal Dokumen</label>
                                <input class="form-control" type="date" name="tanggal_dokumen"
                                       value="<?= $arsip['tanggal_dokumen'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Pegawai *</label>
                            <input class="form-control" type="text" name="nama_pegawai"
                                   value="<?= sanitize($arsip['nama_pegawai']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jenis Pelanggaran *</label>
                            <select class="form-control" name="jenis_pelanggaran_id" required>
                                <option value="">-- Pilih --</option>
                                <?php foreach ($jenisList as $j): ?>
                                <option value="<?= $j['id'] ?>" <?= $arsip['jenis_pelanggaran_id'] == $j['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($j['nama_pelanggaran']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Wilayah *</label>
                                <select class="form-control" name="wilayah_id" id="wilayah_id" required
                                        onchange="loadKecamatan(this.value)">
                                    <option value="">-- Pilih Wilayah --</option>
                                    <?php foreach ($wilayahs as $w): ?>
                                    <option value="<?= $w['id'] ?>" <?= $arsip['wilayah_id'] == $w['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($w['nama_wilayah']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kecamatan</label>
                                <select class="form-control" name="kecamatan_id" id="kecamatan_id">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    <?php foreach ($kecamatans as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= $arsip['kecamatan_id'] == $k['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($k['nama_kecamatan']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Tempat</label>
                            <input class="form-control" type="text" name="nama_tempat"
                                   value="<?= sanitize($arsip['nama_tempat'] ?? '') ?>"
                                   placeholder="Nama tempat kejadian / instansi">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Jumlah</label>
                                <input class="form-control" type="number" name="jumlah"
                                       step="0.01" min="0"
                                       value="<?= sanitize($arsip['jumlah'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Satuan</label>
                                <input class="form-control" type="text" name="satuan"
                                       value="<?= sanitize($arsip['satuan'] ?? '') ?>"
                                       placeholder="Rupiah, Hari, Lembar, dst.">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Keterangan / Deskripsi</label>
                            <textarea class="form-control" name="deskripsi"><?= sanitize($arsip['deskripsi'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Ganti File Dokumen</label>
                            <?php if ($arsip['file_name']): ?>
                            <p style="font-size:13px;color:#6b7280;margin-bottom:6px">
                                File saat ini: <strong><?= sanitize($arsip['file_name']) ?></strong>
                            </p>
                            <?php endif; ?>
                            <input class="form-control" type="file" id="fileInput" name="file"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <div class="form-hint" id="filePreview">Kosongkan jika tidak ingin ganti file</div>
                        </div>

                        <div style="display:flex;gap:12px;margin-top:8px">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <a href="detail.php?id=<?= $id ?>" class="btn btn-ghost">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
<script>
function loadKecamatan(wilayahId, selectedId) {
    const sel = document.getElementById('kecamatan_id');
    sel.innerHTML = '<option value="">-- Memuat... --</option>';
    if (!wilayahId) { sel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>'; return; }
    fetch('../config/api_kecamatan.php?wilayah_id=' + wilayahId)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
            data.forEach(k => {
                const opt = document.createElement('option');
                opt.value = k.id;
                opt.textContent = k.nama_kecamatan;
                if (selectedId && k.id == selectedId) opt.selected = true;
                sel.appendChild(opt);
            });
        });
}
</script>
</body>
</html>
