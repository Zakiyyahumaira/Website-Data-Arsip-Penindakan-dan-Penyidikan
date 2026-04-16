<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $noSurat       = trim($_POST['no_surat'] ?? '');
    $namaPegawai   = trim($_POST['nama_pegawai'] ?? '');
    $deskripsi     = trim($_POST['deskripsi'] ?? '');
    $jenisId       = (int)($_POST['jenis_pelanggaran_id'] ?? 0);
    $wilayahId     = (int)($_POST['wilayah_id'] ?? 0);
    $kecamatanId   = (int)($_POST['kecamatan_id'] ?? 0);
    $namaTempat    = trim($_POST['nama_tempat'] ?? '');
    $jumlah        = $_POST['jumlah'] !== '' ? $_POST['jumlah'] : null;
    $satuan        = trim($_POST['satuan'] ?? '');
    $tglDokumen    = $_POST['tanggal_dokumen'] ?? '';
    $mapId         = (int)($_POST['map_id'] ?? 0);

    if (!$noSurat)     $errors[] = 'Nomor surat wajib diisi.';
    if (!$tglDokumen)  $errors[] = 'Tanggal dokumen wajib diisi.';
    if (!$namaPegawai) $errors[] = 'Nama pegawai wajib diisi.';
    if (!$mapId)       $errors[] = 'Map/folder wajib dipilih.';
    if (!$jenisId)     $errors[] = 'Jenis pelanggaran wajib dipilih.';
    if (!$wilayahId)   $errors[] = 'Wilayah wajib dipilih.';
    if (!$kecamatanId) $errors[] = 'Kecamatan wajib diisi.';
    if (!$namaTempat) $errors[] = 'Nama tempat wajib diisi.';
    if (!$jumlah) $errors[] = 'Jumlah wajib diisi.';
    if (!$satuan) $errors[] = 'Satuan wajib diisi.';
    if (!$mapId) $errors[] = 'Map wajib dipilih.';


    if ($noSurat) {
        $cek = $pdo->prepare("SELECT id FROM arsip WHERE no_surat = ?");
        $cek->execute([$noSurat]);
        if ($cek->fetch()) $errors[] = 'Nomor surat sudah digunakan.';
    }

    $filePath = null;
    $fileName = null;
    if (!empty($_FILES['file']['name'])) {
        $upload = uploadFile($_FILES['file'], '../uploads/');
        if (!$upload['ok']) $errors[] = $upload['msg'];
        else { $filePath = $upload['path']; $fileName = $upload['name']; }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO arsip
             (map_id, no_surat, nama_pegawai, deskripsi, jenis_pelanggaran_id,
              wilayah_id, kecamatan_id, nama_tempat, jumlah, satuan,
              tanggal_dokumen, file_path, file_name, diunggah_oleh)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $mapId ?: null, $noSurat, $namaPegawai, $deskripsi, $jenisId,
            $wilayahId ?: null, $kecamatanId ?: null, $namaTempat,
            $jumlah, $satuan, $tglDokumen,
            $filePath, $fileName, $_SESSION['user_id']
        ]);
        $newId = $pdo->lastInsertId();
        logAktivitas($pdo, $_SESSION['user_id'], 'Upload arsip: ' . $namaPegawai, $newId);
        header('Location: daftar.php?msg=tambah');
        exit;
    }
}

$jenisList = $pdo->query("SELECT * FROM jenis_pelanggaran ORDER BY nama_pelanggaran")->fetchAll();
$wilayahs  = $pdo->query("SELECT * FROM wilayah ORDER BY id")->fetchAll();
$maps      = $pdo->query("SELECT * FROM map ORDER BY nama_map")->fetchAll();

// Kecamatan untuk wilayah yang sudah dipilih (jika ada POST error)
$kecamatans = [];
if (!empty($old['wilayah_id'])) {
    $kecamatans = getKecamatanByWilayah($pdo, (int)$old['wilayah_id']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Arsip — Arsip Kantor</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h1>Upload Arsip Baru</h1>
            <div class="topbar-actions">
                <a href="daftar.php" class="btn btn-ghost btn-sm">&larr; Kembali</a>
            </div>
        </div>

        <div class="page-body">
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin:0;padding-left:18px">
                    <?php foreach ($errors as $e): ?>
                    <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="card" style="max-width:820px">
                <div class="card-header"><h3>Formulir Upload Arsip</h3></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

                        <div class="form-group">
                            <label class="form-label" for="map_id">📁 Map/Folder <span style="color:#dc2626">*</span></label>
                            <select class="form-control" id="map_id" name="map_id" required>
                                <option value="">-- Pilih Map --</option>
                                <?php foreach ($maps as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($old['map_id'] ?? '') == $m['id'] ? 'selected' : '' ?>>
                                    📁 <?= sanitize($m['nama_map']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-hint"><a href="map.php">Kelola map di sini</a></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="no_surat">No. Surat <span style="color:#dc2626">*</span></label>
                                <input class="form-control" type="text" id="no_surat" name="no_surat"
                                       value="<?= sanitize($old['no_surat'] ?? '') ?>"
                                       placeholder="Contoh: SBP-1/KBC.0102/2025" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="tanggal_dokumen">Tanggal Dokumen <span style="color:#dc2626">*</span></label>
                                <input class="form-control" type="date" id="tanggal_dokumen" name="tanggal_dokumen"
                                       value="<?= $old['tanggal_dokumen'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="nama_pegawai">Nama Pegawai <span style="color:#dc2626">*</span></label>
                            <input class="form-control" type="text" id="nama_pegawai" name="nama_pegawai"
                                   value="<?= sanitize($old['nama_pegawai'] ?? '') ?>"
                                   placeholder="Nama lengkap pegawai yang bersangkutan" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="jenis_pelanggaran_id">Jenis Pelanggaran <span style="color:#dc2626">*</span></label>
                            <select class="form-control" id="jenis_pelanggaran_id" name="jenis_pelanggaran_id" required>
                                <option value="">-- Pilih Jenis Pelanggaran --</option>
                                <?php foreach ($jenisList as $j): ?>
                                <option value="<?= $j['id'] ?>" <?= ($old['jenis_pelanggaran_id'] ?? '') == $j['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($j['nama_pelanggaran']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Pilih Map <span style="color:#dc2626">*</span></label>  
                            <select class="form-control" name="map_id" required>
                                <option value="">-- Pilih Map --</option>
                                <?php foreach ($maps as $m): ?>
                                <option value="<?= $m['id'] ?>">
                                    <?= htmlspecialchars($m['nama_map']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Wilayah & Kecamatan -->
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="wilayah_id">Wilayah <span style="color:#dc2626">*</span></label>
                                <select class="form-control" id="wilayah_id" name="wilayah_id" required
                                        onchange="loadKecamatan(this.value)">
                                    <option value="">-- Pilih Wilayah --</option>
                                    <?php foreach ($wilayahs as $w): ?>
                                    <option value="<?= $w['id'] ?>" <?= ($old['wilayah_id'] ?? '') == $w['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($w['nama_wilayah']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="kecamatan_id">Kecamatan <span style="color:#dc2626">*</span></label>
                                <select class="form-control" id="kecamatan_id" name="kecamatan_id" required>
                                    <option value="">-- Pilih Kecamatan --</option>
                                    <?php foreach ($kecamatans as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= ($old['kecamatan_id'] ?? '') == $k['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($k['nama_kecamatan']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="nama_tempat">Nama Tempat <span style="color:#dc2626">*</span> </label>
                            <input class="form-control" type="text" id="nama_tempat" name="nama_tempat" 
                                   value="<?= sanitize($old['nama_tempat'] ?? '') ?>"
                                   placeholder="Nama tempat kejadian penindakan" required>
                        </div>

                        <!-- Jumlah & Satuan -->
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="jumlah">Jumlah <span style="color:#dc2626">*</span></label>
                                <input class="form-control" type="number" id="jumlah" name="jumlah"
                                       step="0.01" min="0"
                                       value="<?= sanitize($old['jumlah'] ?? '') ?>"
                                       placeholder="Contoh: 5000000" required> 
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="satuan">Satuan <span style="color:#dc2626">*</span></label>
                                <input class="form-control" type="text" id="satuan" name="satuan"
                                       value="<?= sanitize($old['satuan'] ?? '') ?>"
                                       placeholder="Contoh: Batang, Pcs, Koli" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="deskripsi">Keterangan / Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi"
                                      placeholder="Keterangan singkat mengenai arsip ini..."><?= sanitize($old['deskripsi'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="fileInput">Upload File Dokumen</label>
                            <input class="form-control" type="file" id="fileInput" name="file"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <div class="form-hint" id="filePreview">PDF, Word, Excel, Gambar — maks. 10 MB</div>
                        </div>

                        <div style="display:flex;gap:12px;margin-top:8px">
                            <button type="submit" class="btn btn-primary">Simpan Arsip</button>
                            <a href="daftar.php" class="btn btn-ghost">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
<script>
function loadKecamatan(wilayahId) {
    const sel = document.getElementById('kecamatan_id');
    sel.innerHTML = '<option value="">-- Memuat... --</option>';
    if (!wilayahId) { sel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>'; return; }
    fetch('../config/api_kecamatan.php?wilayah_id=' + wilayahId)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
            data.forEach(k => {
                sel.innerHTML += '<option value="' + k.id + '">' + k.nama_kecamatan + '</option>';
            });
        })
        .catch(() => { sel.innerHTML = '<option value="">Gagal memuat</option>'; });
}
</script>
</body>
</html>
