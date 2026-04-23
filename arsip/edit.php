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
    $noSurat           = trim($_POST['no_surat'] ?? '');
    $petugas1Id        = (int)($_POST['petugas_1_id'] ?? 0);
    $petugas2Id        = (int)($_POST['petugas_2_id'] ?? 0);
    $deskripsi         = trim($_POST['deskripsi'] ?? '');
    $jenisId           = (int)($_POST['jenis_pelanggaran_id'] ?? 0);
    $wilayahId         = (int)($_POST['wilayah_id'] ?? 0);
    $kecamatanId       = (int)($_POST['kecamatan_id'] ?? 0);
    $namaTempat        = trim($_POST['nama_tempat'] ?? '');
    $waktuPenindakan   = $_POST['waktu_penindakan'] ?? '';
    $jumlah            = $_POST['jumlah'] !== '' ? $_POST['jumlah'] : null;
    $satuan            = trim($_POST['satuan'] ?? '');
    $tglDokumen        = $_POST['tanggal_dokumen'] ?? '';

    if (!$noSurat)          $errors[] = 'Nomor surat wajib diisi.';
    if (!$petugas1Id)       $errors[] = 'Petugas 1 wajib dipilih.';
    if (!$petugas2Id)       $errors[] = 'Petugas 2 wajib dipilih.';
    if (!$waktuPenindakan)  $errors[] = 'Waktu penindakan wajib diisi.';
    if ($petugas1Id && $petugas2Id && $petugas1Id === $petugas2Id) $errors[] = 'Petugas 1 dan Petugas 2 tidak boleh sama.';
    if (!$jenisId)          $errors[] = 'Jenis pelanggaran wajib dipilih.';
    if (!$wilayahId)        $errors[] = 'Wilayah wajib dipilih.';

    if ($noSurat && $noSurat !== $arsip['no_surat']) {
        $cek = $pdo->prepare("SELECT id FROM arsip WHERE no_surat = ? AND id != ?");
        $cek->execute([$noSurat, $id]);
        if ($cek->fetch()) $errors[] = 'Nomor surat sudah digunakan.';
    }

    // Validasi pelaku
    $namaPelaku = $_POST['nama_pelaku'] ?? [];
    $identitasPelaku = $_POST['identitas_pelaku'] ?? [];
    $noIdentitasPelaku = $_POST['no_identitas_pelaku'] ?? [];
    $jenisKelaminPelaku = $_POST['jenis_kelamin_pelaku'] ?? [];
    $alamatPelaku = $_POST['alamat_pelaku'] ?? [];

    $dataPelaku = [];
    if (!empty($namaPelaku)) {
        foreach ($namaPelaku as $idx => $nama) {
            $nama = trim($nama ?? '');
            if ($nama) {
                $identitas = trim($identitasPelaku[$idx] ?? '');
                $noIdentitas = trim($noIdentitasPelaku[$idx] ?? '');
                $jenisKelamin = $jenisKelaminPelaku[$idx] ?? '';
                $alamat = trim($alamatPelaku[$idx] ?? '');

                if (!$identitas) $errors[] = "Identitas pelaku #" . ($idx+1) . " wajib diisi.";
                if (!$noIdentitas) $errors[] = "No. identitas pelaku #" . ($idx+1) . " wajib diisi.";
                if (!$jenisKelamin) $errors[] = "Jenis kelamin pelaku #" . ($idx+1) . " wajib dipilih.";
                if (!$alamat) $errors[] = "Alamat pelaku #" . ($idx+1) . " wajib diisi.";

                if ($identitas && $noIdentitas && $jenisKelamin && $alamat) {
                    $dataPelaku[] = [
                        'nama' => $nama,
                        'identitas' => $identitas,
                        'no_identitas' => $noIdentitas,
                        'jenis_kelamin' => $jenisKelamin,
                        'alamat' => $alamat
                    ];
                }
            }
        }
    }

    if (empty($dataPelaku)) {
        $errors[] = 'Minimal harus ada satu data pelaku.';
    }

    // Validasi barang hasil penindakan
    $namaBarang = $_POST['nama_barang'] ?? [];
    $jenisBarang = $_POST['jenis_barang'] ?? [];
    $jumlahBarang = $_POST['jumlah_barang'] ?? [];
    $satuanBarang = $_POST['satuan_barang'] ?? [];
    $uraianBarang = $_POST['jenis_uraian_barang'] ?? [];

    $dataBarang = [];
    if (!empty($namaBarang)) {
        foreach ($namaBarang as $idx => $nama) {
            $nama = trim($nama ?? '');
            if ($nama) {
                $jenis = trim($jenisBarang[$idx] ?? '');
                $jumlah = $_POST['jumlah_barang'][$idx] ?? '';
                $satuan = trim($satuanBarang[$idx] ?? '');
                $uraian = trim($uraianBarang[$idx] ?? '');

                if (!$jenis) $errors[] = "Jenis barang #" . ($idx+1) . " wajib diisi.";
                if ($jumlah === '' || $jumlah < 0) $errors[] = "Jumlah barang #" . ($idx+1) . " wajib diisi dan valid.";
                if (!$satuan) $errors[] = "Satuan barang #" . ($idx+1) . " wajib diisi.";

                if ($jenis && $jumlah !== '' && $satuan) {
                    $dataBarang[] = [
                        'nama_barang' => $nama,
                        'jenis_barang' => $jenis,
                        'jumlah_barang' => (float)$jumlah,
                        'satuan' => $satuan,
                        'jenis_uraian_barang' => $uraian
                    ];
                }
            }
        }
    }

    if (empty($dataBarang)) {
        $errors[] = 'Minimal harus ada satu data barang hasil penindakan.';
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
        try {
            $pdo->beginTransaction();

            $stmtPetugas1 = $pdo->prepare("SELECT nama FROM petugas WHERE id = ?");
            $stmtPetugas1->execute([$petugas1Id]);
            $petugas1 = $stmtPetugas1->fetch();

            $stmtPetugas2 = $pdo->prepare("SELECT nama FROM petugas WHERE id = ?");
            $stmtPetugas2->execute([$petugas2Id]);
            $petugas2 = $stmtPetugas2->fetch();

            $namaPegawai = $petugas1['nama'] . ' / ' . $petugas2['nama'];

            $stmt = $pdo->prepare(
                "UPDATE arsip SET
                 no_surat=?, nama_pegawai=?, petugas_1_id=?, petugas_2_id=?, deskripsi=?, jenis_pelanggaran_id=?,
                 wilayah_id=?, kecamatan_id=?, nama_tempat=?, waktu_penindakan=?,
                 tanggal_dokumen=?, file_path=?, file_name=?
                 WHERE id=?"
            );
            $stmt->execute([
                $noSurat, $namaPegawai, $petugas1Id ?: null, $petugas2Id ?: null,
                $deskripsi, $jenisId,
                $wilayahId ?: null, $kecamatanId ?: null, $namaTempat, $waktuPenindakan,
                $tglDokumen ?: null, $filePath, $fileName, $id
            ]);

            // Delete dan re-insert pelaku
            $stmtDelPelaku = $pdo->prepare("DELETE FROM pelaku WHERE arsip_id = ?");
            $stmtDelPelaku->execute([$id]);

            $stmtPelaku = $pdo->prepare(
                "INSERT INTO pelaku (arsip_id, nama, identitas, no_identitas, jenis_kelamin, alamat)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($dataPelaku as $pelaku) {
                $stmtPelaku->execute([
                    $id, $pelaku['nama'], $pelaku['identitas'], 
                    $pelaku['no_identitas'], $pelaku['jenis_kelamin'], $pelaku['alamat']
                ]);
            }

            // Delete dan re-insert barang
            $stmtDelBarang = $pdo->prepare("DELETE FROM barang_hasil_penindakan WHERE arsip_id = ?");
            $stmtDelBarang->execute([$id]);

            $stmtBarang = $pdo->prepare(
                "INSERT INTO barang_hasil_penindakan (arsip_id, nama_barang, jenis_barang, jumlah_barang, satuan, jenis_uraian_barang)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($dataBarang as $barang) {
                $stmtBarang->execute([
                    $id, $barang['nama_barang'], $barang['jenis_barang'],
                    $barang['jumlah_barang'], $barang['satuan'], $barang['jenis_uraian_barang']
                ]);
            }

            $pdo->commit();
            logAktivitas($pdo, $_SESSION['user_id'], 'Edit arsip: ' . $namaPegawai, $id);
            header('Location: daftar.php?msg=edit');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }

    // Kembalikan ke form dengan nilai POST
    $arsip = array_merge($arsip, $_POST, ['file_path' => $filePath, 'file_name' => $fileName]);
}

$jenisList  = $pdo->query("SELECT * FROM jenis_pelanggaran ORDER BY nama_pelanggaran")->fetchAll();
$wilayahs   = $pdo->query("SELECT * FROM wilayah ORDER BY id")->fetchAll();
$petugasList = $pdo->query("SELECT * FROM petugas ORDER BY nama")->fetchAll();
$kecamatans = $arsip['wilayah_id'] ? getKecamatanByWilayah($pdo, $arsip['wilayah_id']) : [];

// Ambil data pelaku
$pelakuList = $pdo->prepare("SELECT * FROM pelaku WHERE arsip_id = ? ORDER BY id");
$pelakuList->execute([$id]);
$pelakuList = $pelakuList->fetchAll();

// Ambil data barang hasil penindakan
$barangList = $pdo->prepare("SELECT * FROM barang_hasil_penindakan WHERE arsip_id = ? ORDER BY id");
$barangList->execute([$id]);
$barangList = $barangList->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Arsip — Arsip Kantor</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-section { margin-top: 24px; padding-top: 16px; border-top: 2px solid #e5e7eb; }
        .repeat-section { margin-top: 12px; }
        .entry-card { border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #ffffff; }
        .entry-header { font-weight: 700; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .entry-header span { color: #111827; }
        .repeat-field { margin-bottom: 12px; }
        .repeat-field label { display: block; margin-bottom: 6px; font-weight: 600; }
        .repeat-field input,
        .repeat-field select,
        .repeat-field textarea { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        .repeat-field textarea { min-height: 80px; resize: vertical; }
        .repeat-field-inline { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .repeat-field-2col { display: grid; gap: 12px; grid-template-columns: repeat(2, 1fr); }
        .btn-remove { padding: 8px 12px; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .btn-remove:hover { background: #991b1b; }
        .btn-add { padding: 10px 14px; background: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; margin-top: 8px; }
        .btn-add:hover { background: #047857; }
    </style>
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

            <div class="card" style="max-width:1000px">
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

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Petugas 1 *</label>
                                <select class="form-control" name="petugas_1_id" required>
                                    <option value="">-- Pilih Petugas 1 --</option>
                                    <?php foreach ($petugasList as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($arsip['petugas_1_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($p['nama']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Petugas 2 *</label>
                                <select class="form-control" name="petugas_2_id" required>
                                    <option value="">-- Pilih Petugas 2 --</option>
                                    <?php foreach ($petugasList as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($arsip['petugas_2_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($p['nama']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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

                        <!-- Wilayah & Kecamatan -->
                        <div class="form-section">
                            <label class="form-label" for="map_id" style="font-size: 15px; color: black" > Lokasi (Locus) </label>
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

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nama Tempat</label>
                                <input class="form-control" type="text" name="nama_tempat"
                                    value="<?= sanitize($arsip['nama_tempat'] ?? '') ?>"
                                    placeholder="Nama tempat kejadian / instansi">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Waktu Penindakan (Tempus) *</label>
                                <input class="form-control" type="time" name="waktu_penindakan"
                                    value="<?= sanitize($arsip['waktu_penindakan'] ?? '') ?>" required>
                                <div class="form-hint">Format: HH:MM WIB </div>
                            </div>
                        </div>

                        <!-- Section: Data Pelaku -->
                        <div class="form-section">
                            <label style="font-size: 15px; color: black; font-weight: 600"> Data Pelaku</label>
                            <div class="repeat-section" id="pelakuContainer">
                                <?php
                                if (!empty($pelakuList)) {
                                    foreach ($pelakuList as $i => $p):
                                        $namaPelakuVal = sanitize($p['nama'] ?? '');
                                        $identitasVal = sanitize($p['identitas'] ?? '');
                                        $noIdentitasVal = sanitize($p['no_identitas'] ?? '');
                                        $jenisKelaminVal = $p['jenis_kelamin'] ?? '';
                                        $alamatVal = sanitize($p['alamat'] ?? '');
                                ?>
                                <div class="entry-card">
                                    <div class="entry-header">
                                        <span>Pelaku #<?= $i + 1 ?></span>
                                        <button type="button" class="btn-remove" onclick="removePelaku(this)">Hapus</button>
                                    </div>
                                    <div class="repeat-field-2col">
                                        <div class="repeat-field">
                                            <label class="form-label">Nama <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="nama_pelaku[]" value="<?= $namaPelakuVal ?>" placeholder="Nama lengkap">
                                        </div>
                                        <div class="repeat-field">
                                            <label class="form-label">Identitas <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="identitas_pelaku[]" value="<?= $identitasVal ?>" placeholder="Misal: KTP, SIM">
                                        </div>
                                    </div>
                                    <div class="repeat-field-2col">
                                        <div class="repeat-field">
                                            <label class="form-label">No. Identitas <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="no_identitas_pelaku[]" value="<?= $noIdentitasVal ?>" placeholder="No. identitas">
                                        </div>
                                        <div class="repeat-field">
                                            <label class="form-label">Jenis Kelamin <span style="color:#dc2626">*</span></label>
                                            <select name="jenis_kelamin_pelaku[]">
                                                <option value="">Pilih</option>
                                                <option value="Laki-laki" <?= $jenisKelaminVal === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                                <option value="Perempuan" <?= $jenisKelaminVal === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="repeat-field">
                                        <label class="form-label">Alamat <span style="color:#dc2626">*</span></label>
                                        <textarea name="alamat_pelaku[]" placeholder="Alamat lengkap"><?= $alamatVal ?></textarea>
                                    </div>
                                </div>
                                <?php endforeach; } else { ?>
                                <div class="entry-card">
                                    <div class="entry-header">
                                        <span>Pelaku #1</span>
                                        <button type="button" class="btn-remove" onclick="removePelaku(this)">Hapus</button>
                                    </div>
                                    <div class="repeat-field-2col">
                                        <div class="repeat-field">
                                            <label class="form-label">Nama <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="nama_pelaku[]" placeholder="Nama lengkap">
                                        </div>
                                        <div class="repeat-field">
                                            <label class="form-label">Identitas <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="identitas_pelaku[]" placeholder="Misal: KTP, SIM">
                                        </div>
                                    </div>
                                    <div class="repeat-field-2col">
                                        <div class="repeat-field">
                                            <label class="form-label">No. Identitas <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="no_identitas_pelaku[]" placeholder="No. identitas">
                                        </div>
                                        <div class="repeat-field">
                                            <label class="form-label">Jenis Kelamin <span style="color:#dc2626">*</span></label>
                                            <select name="jenis_kelamin_pelaku[]">
                                                <option value="">Pilih</option>
                                                <option value="Laki-laki">Laki-laki</option>
                                                <option value="Perempuan">Perempuan</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="repeat-field">
                                        <label class="form-label">Alamat <span style="color:#dc2626">*</span></label>
                                        <textarea name="alamat_pelaku[]" placeholder="Alamat lengkap"></textarea>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                            <button type="button" class="btn-add" onclick="addPelaku()">+ Tambah Pelaku</button>
                        </div>

                        <!-- Section: Barang Hasil Penindakan -->
                        <div class="form-section">
                            <label style="font-size: 15px; color: black; font-weight: 600"> Barang Hasil Penindakan</label>
                            <div class="repeat-section" id="barangContainer">
                                <?php
                                if (!empty($barangList)) {
                                    foreach ($barangList as $i => $b):
                                        $namaBarangVal = sanitize($b['nama_barang'] ?? '');
                                        $jenisBarangVal = sanitize($b['jenis_barang'] ?? '');
                                        $jumlahBarangVal = sanitize($b['jumlah_barang'] ?? '');
                                        $satuanBarangVal = sanitize($b['satuan'] ?? '');
                                        $uraianBarangVal = sanitize($b['jenis_uraian_barang'] ?? '');
                                ?>
                                <div class="entry-card">
                                    <div class="entry-header">
                                        <span>Barang #<?= $i + 1 ?></span>
                                        <button type="button" class="btn-remove" onclick="removeBarang(this)">Hapus</button>
                                    </div>
                                    <div class="repeat-field-2col">
                                        <div class="repeat-field">
                                            <label class="form-label">Nama Barang <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="nama_barang[]" value="<?= $namaBarangVal ?>" placeholder="Nama barang">
                                        </div>
                                        <div class="repeat-field">
                                            <label class="form-label">Jenis Barang <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="jenis_barang[]" value="<?= $jenisBarangVal ?>" placeholder="Jenis">
                                        </div>
                                    </div>
                                    <div class="repeat-field-2col">
                                        <div class="repeat-field">
                                            <label class="form-label">Jumlah <span style="color:#dc2626">*</span></label>
                                            <input type="number" name="jumlah_barang[]" step="0.01" min="0" value="<?= $jumlahBarangVal ?>" placeholder="Jumlah">
                                        </div>
                                        <div class="repeat-field">
                                            <label class="form-label">Satuan <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="satuan_barang[]" value="<?= $satuanBarangVal ?>" placeholder="Satuan">
                                        </div>
                                    </div>
                                    <div class="repeat-field">
                                        <label class="form-label">Jenis Uraian Barang</label>
                                        <textarea name="jenis_uraian_barang[]" placeholder="Deskripsi barang"><?= $uraianBarangVal ?></textarea>
                                    </div>
                                </div>
                                <?php endforeach; } else { ?>
                                <div class="entry-card">
                                    <div class="entry-header">
                                        <span>Barang #1</span>
                                        <button type="button" class="btn-remove" onclick="removeBarang(this)">Hapus</button>
                                    </div>
                                    <div class="repeat-field-2col">
                                        <div class="repeat-field">
                                            <label class="form-label">Nama Barang <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="nama_barang[]" placeholder="Nama barang">
                                        </div>
                                        <div class="repeat-field">
                                            <label class="form-label">Jenis Barang <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="jenis_barang[]" placeholder="Jenis">
                                        </div>
                                    </div>
                                    <div class="repeat-field-2col">
                                        <div class="repeat-field">
                                            <label class="form-label">Jumlah <span style="color:#dc2626">*</span></label>
                                            <input type="number" name="jumlah_barang[]" step="0.01" min="0" placeholder="Jumlah">
                                        </div>
                                        <div class="repeat-field">
                                            <label class="form-label">Satuan <span style="color:#dc2626">*</span></label>
                                            <input type="text" name="satuan_barang[]" placeholder="Satuan">
                                        </div>
                                    </div>
                                    <div class="repeat-field">
                                        <label class="form-label">Jenis Uraian Barang</label>
                                        <textarea name="jenis_uraian_barang[]" placeholder="Deskripsi barang"></textarea>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                            <button type="button" class="btn-add" onclick="addBarang()">+ Tambah Barang</button>
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
let idxPelakuGlobal = <?= count($pelakuList) ?>;
let idxBarangGlobal = <?= count($barangList) ?>;

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

function addPelaku() {
    const container = document.getElementById('pelakuContainer');
    const index = container.querySelectorAll('.entry-card').length + 1;
    const card = document.createElement('div');
    card.className = 'entry-card';
    card.innerHTML = `
        <div class="entry-header">
            <span>Pelaku #${index}</span>
            <button type="button" class="btn-remove" onclick="removePelaku(this)">Hapus</button>
        </div>
        <div class="repeat-field-2col">
            <div class="repeat-field">
                <label class="form-label">Nama <span style="color:#dc2626">*</span></label>
                <input type="text" name="nama_pelaku[]" placeholder="Nama lengkap">
            </div>
            <div class="repeat-field">
                <label class="form-label">Identitas <span style="color:#dc2626">*</span></label>
                <input type="text" name="identitas_pelaku[]" placeholder="Misal: KTP, SIM">
            </div>
        </div>
        <div class="repeat-field-2col">
            <div class="repeat-field">
                <label class="form-label">No. Identitas <span style="color:#dc2626">*</span></label>
                <input type="text" name="no_identitas_pelaku[]" placeholder="No. identitas">
            </div>
            <div class="repeat-field">
                <label class="form-label">Jenis Kelamin <span style="color:#dc2626">*</span></label>
                <select name="jenis_kelamin_pelaku[]">
                    <option value="">Pilih</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>
        </div>
        <div class="repeat-field">
            <label class="form-label">Alamat <span style="color:#dc2626">*</span></label>
            <textarea name="alamat_pelaku[]" placeholder="Alamat lengkap"></textarea>
        </div>
    `;
    container.appendChild(card);
}

function removePelaku(btn) {
    const container = document.getElementById('pelakuContainer');
    if (container.querySelectorAll('.entry-card').length <= 1) {
        alert('Minimal harus ada satu data pelaku');
        return;
    }
    btn.closest('.entry-card').remove();
    updateEntryHeaders('pelakuContainer', 'Pelaku');
}

function addBarang() {
    const container = document.getElementById('barangContainer');
    const index = container.querySelectorAll('.entry-card').length + 1;
    const card = document.createElement('div');
    card.className = 'entry-card';
    card.innerHTML = `
        <div class="entry-header">
            <span>Barang #${index}</span>
            <button type="button" class="btn-remove" onclick="removeBarang(this)">Hapus</button>
        </div>
        <div class="repeat-field-2col">
            <div class="repeat-field">
                <label class="form-label">Nama Barang <span style="color:#dc2626">*</span></label>
                <input type="text" name="nama_barang[]" placeholder="Nama barang">
            </div>
            <div class="repeat-field">
                <label class="form-label">Jenis Barang <span style="color:#dc2626">*</span></label>
                <input type="text" name="jenis_barang[]" placeholder="Jenis">
            </div>
        </div>
        <div class="repeat-field-2col">
            <div class="repeat-field">
                <label class="form-label">Jumlah <span style="color:#dc2626">*</span></label>
                <input type="number" name="jumlah_barang[]" step="0.01" min="0" placeholder="Jumlah">
            </div>
            <div class="repeat-field">
                <label class="form-label">Satuan <span style="color:#dc2626">*</span></label>
                <input type="text" name="satuan_barang[]" placeholder="Satuan">
            </div>
        </div>
        <div class="repeat-field">
            <label class="form-label">Jenis Uraian Barang</label>
            <textarea name="jenis_uraian_barang[]" placeholder="Deskripsi barang"></textarea>
        </div>
    `;
    container.appendChild(card);
}

function removeBarang(btn) {
    const container = document.getElementById('barangContainer');
    if (container.querySelectorAll('.entry-card').length <= 1) {
        alert('Minimal harus ada satu data barang');
        return;
    }
    btn.closest('.entry-card').remove();
    updateEntryHeaders('barangContainer', 'Barang');
}

function updateEntryHeaders(containerId, label) {
    const container = document.getElementById(containerId);
    container.querySelectorAll('.entry-card').forEach((card, idx) => {
        const header = card.querySelector('.entry-header span');
        if (header) header.textContent = `${label} #${idx + 1}`;
    });
}
</script>
</body>
</html>
