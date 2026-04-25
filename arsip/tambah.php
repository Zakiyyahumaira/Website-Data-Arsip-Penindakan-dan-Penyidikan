<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $noSurat           = trim($_POST['no_surat'] ?? '');
    $deskripsi         = trim($_POST['deskripsi'] ?? '');
    $jenisId           = (int)($_POST['jenis_pelanggaran_id'] ?? 0);
    $wilayahId         = (int)($_POST['wilayah_id'] ?? 0);
    $kecamatanId       = (int)($_POST['kecamatan_id'] ?? 0);
    $namaTempat        = trim($_POST['nama_tempat'] ?? '');
    $waktuPenindakan   = $_POST['waktu_penindakan'] ?? '';
    $mapId             = (int)($_POST['map_id'] ?? 0);
    $tglDokumen        = $_POST['tanggal_dokumen'] ?? '';
    $petugas1Id        = (int)($_POST['petugas_1_id'] ?? 0);
    $petugas2Id        = (int)($_POST['petugas_2_id'] ?? 0);

    // Validasi field utama
    if (!$noSurat)          $errors[] = 'Nomor surat wajib diisi.';
    if (!$tglDokumen)       $errors[] = 'Tanggal dokumen wajib diisi.';
    if (!$petugas1Id)       $errors[] = 'Petugas 1 wajib dipilih.';
    if (!$petugas2Id)       $errors[] = 'Petugas 2 wajib dipilih.';
    if (!$mapId)            $errors[] = 'Map/folder wajib dipilih.';
    if (!$jenisId)          $errors[] = 'Jenis pelanggaran wajib dipilih.';
    if (!$wilayahId)        $errors[] = 'Wilayah wajib dipilih.';
    if (!$kecamatanId)      $errors[] = 'Kecamatan wajib diisi.';
    if (!$namaTempat)       $errors[] = 'Nama tempat wajib diisi.';
    if (!$waktuPenindakan)  $errors[] = 'Waktu penindakan wajib diisi.';
    
    if ($petugas1Id && $petugas2Id && $petugas1Id === $petugas2Id) 
        $errors[] = 'Petugas 1 dan Petugas 2 tidak boleh sama.';

    if ($noSurat) {
        $cek = $pdo->prepare("SELECT id FROM arsip WHERE no_surat = ?");
        $cek->execute([$noSurat]);
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

    // Upload file
    $filePath = null;
    $fileName = null;
    if (!empty($_FILES['file']['name'])) {
        $upload = uploadFile($_FILES['file'], '../uploads/');
        if (!$upload['ok']) $errors[] = $upload['msg'];
        else { $filePath = $upload['path']; $fileName = $upload['name']; }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Ambil nama petugas
            $stmtPetugas1 = $pdo->prepare("SELECT nama FROM petugas WHERE id = ?");
            $stmtPetugas1->execute([$petugas1Id]);
            $petugas1 = $stmtPetugas1->fetch();

            $stmtPetugas2 = $pdo->prepare("SELECT nama FROM petugas WHERE id = ?");
            $stmtPetugas2->execute([$petugas2Id]);
            $petugas2 = $stmtPetugas2->fetch();

            $namaPegawai = $petugas1['nama'] . ' / ' . $petugas2['nama'];

            // Insert arsip utama
            $stmt = $pdo->prepare(
                "INSERT INTO arsip
                 (map_id, no_surat, nama_pegawai, petugas_1_id, petugas_2_id, deskripsi, jenis_pelanggaran_id,
                  wilayah_id, kecamatan_id, nama_tempat, waktu_penindakan,
                  tanggal_dokumen, file_path, file_name, diunggah_oleh)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                $mapId ?: null, $noSurat, $namaPegawai, $petugas1Id ?: null, $petugas2Id ?: null,
                $deskripsi, $jenisId,
                $wilayahId ?: null, $kecamatanId ?: null, $namaTempat, $waktuPenindakan,
                $tglDokumen, $filePath, $fileName, $_SESSION['user_id']
            ]);
            $arsipId = $pdo->lastInsertId();

            // Insert pelaku
            $stmtPelaku = $pdo->prepare(
                "INSERT INTO pelaku (arsip_id, nama, identitas, no_identitas, jenis_kelamin, alamat)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($dataPelaku as $pelaku) {
                $stmtPelaku->execute([
                    $arsipId, $pelaku['nama'], $pelaku['identitas'], 
                    $pelaku['no_identitas'], $pelaku['jenis_kelamin'], $pelaku['alamat']
                ]);
            }

            // Insert barang hasil penindakan
            $stmtBarang = $pdo->prepare(
                "INSERT INTO barang_hasil_penindakan (arsip_id, nama_barang, jenis_barang, jumlah_barang, satuan, jenis_uraian_barang)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($dataBarang as $barang) {
                $stmtBarang->execute([
                    $arsipId, $barang['nama_barang'], $barang['jenis_barang'],
                    $barang['jumlah_barang'], $barang['satuan'], $barang['jenis_uraian_barang']
                ]);
            }

            $pdo->commit();
            logAktivitas($pdo, $_SESSION['user_id'], 'Upload arsip: ' . $namaPegawai, $arsipId);
            header('Location: daftar.php?msg=tambah');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}

$jenisList = $pdo->query("SELECT * FROM jenis_pelanggaran ORDER BY nama_pelanggaran")->fetchAll();
$wilayahs  = $pdo->query("SELECT * FROM wilayah ORDER BY id")->fetchAll();
$maps      = [];
$petugasList = $pdo->query("SELECT * FROM petugas ORDER BY nama")->fetchAll();
try {
    $maps = $pdo->query("SELECT * FROM map ORDER BY nama_map")->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Data map tidak dapat dimuat. Pastikan tabel map sudah tersedia.';
}

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
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
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

    <div class="main-content collapsed">
        <div class="topbar">
            <button id="toggleSidebar" class="btn btn-ghost btn-sm">☰</button>
            <h1>Upload Arsip Baru</h1>
            <div class="topbar-actions">
                <button id="toggleSidebar" class="btn btn-ghost btn-sm">☰</button>
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

            <div class="card">
                <div class="card-header"><h3>Formulir Upload Arsip</h3></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

                        <!-- Section 1: Map dan Dokumen Dasar -->
                        <div class="form-group">
                            <label class="form-label" for="map_id"> Map <span style="color:#dc2626">*</span></label>
                            <select class="form-control" id="map_id" name="map_id" required>
                                <option value="">-- Pilih Map --</option>
                                <?php foreach ($maps as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($old['map_id'] ?? '') == $m['id'] ? 'selected' : '' ?>>
                                     <?= sanitize($m['nama_map']) ?>
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

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="petugas_1_id">Petugas 1 <span style="color:#dc2626">*</span></label>
                                <select class="form-control" id="petugas_1_id" name="petugas_1_id" required>
                                    <option value="">-- Pilih Petugas 1 --</option>
                                    <?php foreach ($petugasList as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($old['petugas_1_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($p['nama']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="petugas_2_id">Petugas 2 <span style="color:#dc2626">*</span></label>
                                <select class="form-control" id="petugas_2_id" name="petugas_2_id" required>
                                    <option value="">-- Pilih Petugas 2 --</option>
                                    <?php foreach ($petugasList as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($old['petugas_2_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($p['nama']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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

                        <!-- Wilayah & Kecamatan -->
                        <div class="form-section">
                            <label class="form-label" for="map_id" style="font-size: 15px; color: black" > Lokasi (Locus) </label>
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
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="nama_tempat">Nama Tempat <span style="color:#dc2626">*</span> </label>
                                <input class="form-control" type="text" id="nama_tempat" name="nama_tempat" 
                                    value="<?= sanitize($old['nama_tempat'] ?? '') ?>"
                                    placeholder="Nama tempat kejadian penindakan" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="waktu_penindakan">Waktu Penindakan (Tempus) <span style="color:#dc2626">*</span></label>
                                <input class="form-control" type="time" id="waktu_penindakan" name="waktu_penindakan"
                                    value="<?= $old['waktu_penindakan'] ?? '' ?>" required>
                                <div class="form-hint">Format: HH:MM WIB</div>
                            </div>
                        </div>

                        <!-- Section: Data Pelaku -->
                        <div class="form-section">
                            <label style="font-size: 15px; color: black; font-weight: 600">Data Pelaku</label>
                            <div class="repeat-section" id="pelakuContainer">
                                <?php
                                $pelakuCount = max(1, count($old['nama_pelaku'] ?? []));
                                for ($i = 0; $i < $pelakuCount; $i++):
                                    $namaPelakuVal = sanitize($old['nama_pelaku'][$i] ?? '');
                                    $identitasVal = sanitize($old['identitas_pelaku'][$i] ?? '');
                                    $noIdentitasVal = sanitize($old['no_identitas_pelaku'][$i] ?? '');
                                    $jenisKelaminVal = $old['jenis_kelamin_pelaku'][$i] ?? '';
                                    $alamatVal = sanitize($old['alamat_pelaku'][$i] ?? '');
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
                                <?php endfor; ?>
                            </div>
                            <button type="button" class="btn-add" onclick="addPelaku()">+ Tambah Pelaku</button>
                        </div>

                        <!-- Section: Barang Hasil Penindakan -->
                        <div class="form-section">
                            <label style="font-size: 15px; color: black; font-weight: 600">Barang Hasil Penindakan</label>
                            <div class="repeat-section" id="barangContainer">
                                <?php
                                $barangCount = max(1, count($old['nama_barang'] ?? []));
                                for ($i = 0; $i < $barangCount; $i++):
                                    $namaBarangVal = sanitize($old['nama_barang'][$i] ?? '');
                                    $jenisBarangVal = sanitize($old['jenis_barang'][$i] ?? '');
                                    $jumlahBarangVal = sanitize($old['jumlah_barang'][$i] ?? '');
                                    $satuanBarangVal = sanitize($old['satuan_barang'][$i] ?? '');
                                    $uraianBarangVal = sanitize($old['jenis_uraian_barang'][$i] ?? '');
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
                                <?php endfor; ?>
                            </div>
                            <button type="button" class="btn-add" onclick="addBarang()">+ Tambah Barang</button>
                        </div>
                        
                        <div class="form-section">
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
                <label>Nama <span style="color:#dc2626">*</span></label>
                <input type="text" name="nama_pelaku[]" placeholder="Nama lengkap">
            </div>
            <div class="repeat-field">
                <label>Identitas <span style="color:#dc2626">*</span></label>
                <input type="text" name="identitas_pelaku[]" placeholder="Misal: KTP, SIM">
            </div>
        </div>
        <div class="repeat-field-2col">
            <div class="repeat-field">
                <label>No. Identitas <span style="color:#dc2626">*</span></label>
                <input type="text" name="no_identitas_pelaku[]" placeholder="No. identitas">
            </div>
            <div class="repeat-field">
                <label>Jenis Kelamin <span style="color:#dc2626">*</span></label>
                <select name="jenis_kelamin_pelaku[]">
                    <option value="">Pilih</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>
        </div>
        <div class="repeat-field">
            <label>Alamat <span style="color:#dc2626">*</span></label>
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
                <label>Nama Barang <span style="color:#dc2626">*</span></label>
                <input type="text" name="nama_barang[]" placeholder="Nama barang">
            </div>
            <div class="repeat-field">
                <label>Jenis Barang <span style="color:#dc2626">*</span></label>
                <input type="text" name="jenis_barang[]" placeholder="Jenis">
            </div>
        </div>
        <div class="repeat-field-2col">
            <div class="repeat-field">
                <label>Jumlah <span style="color:#dc2626">*</span></label>
                <input type="number" name="jumlah_barang[]" step="0.01" min="0" placeholder="Jumlah">
            </div>
            <div class="repeat-field">
                <label>Satuan <span style="color:#dc2626">*</span></label>
                <input type="text" name="satuan_barang[]" placeholder="Satuan">
            </div>
        </div>
        <div class="repeat-field">
            <label>Jenis Uraian Barang</label>
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
