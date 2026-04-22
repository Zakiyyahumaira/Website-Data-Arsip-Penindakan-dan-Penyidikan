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
        .table-container { overflow-x: auto; margin-top: 8px; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th { background: #f3f4f6; padding: 8px; text-align: left; font-weight: 600; border: 1px solid #d1d5db; }
        .data-table td { padding: 8px; border: 1px solid #d1d5db; }
        .data-table input { width: 100%; padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; }
        .data-table select { width: 100%; padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; }
        .btn-remove { padding: 4px 8px; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-remove:hover { background: #991b1b; }
        .btn-add { padding: 8px 12px; background: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 8px; }
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

                        <div class="form-group">
                            <label class="form-label">Waktu Penindakan (WIB) *</label>
                            <input class="form-control" type="time" name="waktu_penindakan"
                                   value="<?= sanitize($arsip['waktu_penindakan'] ?? '') ?>" required>
                            <div class="form-hint">Format: HH:MM (Contoh: 14:30)</div>
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

                        <!-- Section: Data Pelaku -->
                        <div class="form-section">
                            <label style="font-size: 15px; color: black; font-weight: 600">👤 Data Pelaku</label>
                            
                            <div class="table-container">
                                <table class="data-table" id="tablePelaku">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%">No</th>
                                            <th style="width: 15%">Nama <span style="color:#dc2626">*</span></th>
                                            <th style="width: 12%">Identitas <span style="color:#dc2626">*</span></th>
                                            <th style="width: 14%">No. Identitas <span style="color:#dc2626">*</span></th>
                                            <th style="width: 12%">Jenis Kelamin <span style="color:#dc2626">*</span></th>
                                            <th style="width: 35%">Alamat <span style="color:#dc2626">*</span></th>
                                            <th style="width: 7%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bodyPelaku">
                                        <?php 
                                        if (!empty($pelakuList)) {
                                            foreach ($pelakuList as $idx => $p):
                                        ?>
                                        <tr class="row-pelaku" data-idx="<?= $idx ?>">
                                            <td><?= $idx + 1 ?></td>
                                            <td><input type="text" name="nama_pelaku[]" value="<?= sanitize($p['nama'] ?? '') ?>" placeholder="Nama lengkap"></td>
                                            <td><input type="text" name="identitas_pelaku[]" value="<?= sanitize($p['identitas'] ?? '') ?>" placeholder="Misal: KTP, SIM"></td>
                                            <td><input type="text" name="no_identitas_pelaku[]" value="<?= sanitize($p['no_identitas'] ?? '') ?>" placeholder="No. identitas"></td>
                                            <td>
                                                <select name="jenis_kelamin_pelaku[]">
                                                    <option value="">Pilih</option>
                                                    <option value="Laki-laki" <?= ($p['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                                    <option value="Perempuan" <?= ($p['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                                                </select>
                                            </td>
                                            <td><input type="text" name="alamat_pelaku[]" value="<?= sanitize($p['alamat'] ?? '') ?>" placeholder="Alamat lengkap"></td>
                                            <td><button type="button" class="btn-remove" onclick="removePelaku(this)">Hapus</button></td>
                                        </tr>
                                        <?php 
                                            endforeach;
                                        } else {
                                        ?>
                                        <tr class="row-pelaku" data-idx="0">
                                            <td>1</td>
                                            <td><input type="text" name="nama_pelaku[]" placeholder="Nama lengkap"></td>
                                            <td><input type="text" name="identitas_pelaku[]" placeholder="Misal: KTP, SIM"></td>
                                            <td><input type="text" name="no_identitas_pelaku[]" placeholder="No. identitas"></td>
                                            <td>
                                                <select name="jenis_kelamin_pelaku[]">
                                                    <option value="">Pilih</option>
                                                    <option value="Laki-laki">Laki-laki</option>
                                                    <option value="Perempuan">Perempuan</option>
                                                </select>
                                            </td>
                                            <td><input type="text" name="alamat_pelaku[]" placeholder="Alamat lengkap"></td>
                                            <td><button type="button" class="btn-remove" onclick="removePelaku(this)">Hapus</button></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn-add" onclick="addPelaku()">+ Tambah Pelaku</button>
                        </div>

                        <!-- Section: Barang Hasil Penindakan -->
                        <div class="form-section">
                            <label style="font-size: 15px; color: black; font-weight: 600">📦 Barang Hasil Penindakan</label>
                            
                            <div class="table-container">
                                <table class="data-table" id="tableBarang">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%">No</th>
                                            <th style="width: 18%">Nama Barang <span style="color:#dc2626">*</span></th>
                                            <th style="width: 15%">Jenis Barang <span style="color:#dc2626">*</span></th>
                                            <th style="width: 12%">Jumlah <span style="color:#dc2626">*</span></th>
                                            <th style="width: 10%">Satuan <span style="color:#dc2626">*</span></th>
                                            <th style="width: 35%">Jenis Uraian Barang</th>
                                            <th style="width: 5%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bodyBarang">
                                        <?php 
                                        if (!empty($barangList)) {
                                            foreach ($barangList as $idx => $b):
                                        ?>
                                        <tr class="row-barang" data-idx="<?= $idx ?>">
                                            <td><?= $idx + 1 ?></td>
                                            <td><input type="text" name="nama_barang[]" value="<?= sanitize($b['nama_barang'] ?? '') ?>" placeholder="Nama barang"></td>
                                            <td><input type="text" name="jenis_barang[]" value="<?= sanitize($b['jenis_barang'] ?? '') ?>" placeholder="Jenis"></td>
                                            <td><input type="number" name="jumlah_barang[]" value="<?= sanitize($b['jumlah_barang'] ?? '') ?>" step="0.01" min="0" placeholder="Jumlah"></td>
                                            <td><input type="text" name="satuan_barang[]" value="<?= sanitize($b['satuan'] ?? '') ?>" placeholder="Satuan"></td>
                                            <td><textarea name="jenis_uraian_barang[]" placeholder="Deskripsi barang" style="width: 100%; padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; resize: vertical;"><?= sanitize($b['jenis_uraian_barang'] ?? '') ?></textarea></td>
                                            <td><button type="button" class="btn-remove" onclick="removeBarang(this)">Hapus</button></td>
                                        </tr>
                                        <?php 
                                            endforeach;
                                        } else {
                                        ?>
                                        <tr class="row-barang" data-idx="0">
                                            <td>1</td>
                                            <td><input type="text" name="nama_barang[]" placeholder="Nama barang"></td>
                                            <td><input type="text" name="jenis_barang[]" placeholder="Jenis"></td>
                                            <td><input type="number" name="jumlah_barang[]" step="0.01" min="0" placeholder="Jumlah"></td>
                                            <td><input type="text" name="satuan_barang[]" placeholder="Satuan"></td>
                                            <td><textarea name="jenis_uraian_barang[]" placeholder="Deskripsi barang" style="width: 100%; padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; resize: vertical;"></textarea></td>
                                            <td><button type="button" class="btn-remove" onclick="removeBarang(this)">Hapus</button></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
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
    const tbody = document.getElementById('bodyPelaku');
    const rowCount = tbody.querySelectorAll('tr').length + 1;
    const row = document.createElement('tr');
    row.className = 'row-pelaku';
    row.dataset.idx = idxPelakuGlobal;
    row.innerHTML = `
        <td>${rowCount}</td>
        <td><input type="text" name="nama_pelaku[]" placeholder="Nama lengkap"></td>
        <td><input type="text" name="identitas_pelaku[]" placeholder="Misal: KTP, SIM"></td>
        <td><input type="text" name="no_identitas_pelaku[]" placeholder="No. identitas"></td>
        <td>
            <select name="jenis_kelamin_pelaku[]">
                <option value="">Pilih</option>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
            </select>
        </td>
        <td><input type="text" name="alamat_pelaku[]" placeholder="Alamat lengkap"></td>
        <td><button type="button" class="btn-remove" onclick="removePelaku(this)">Hapus</button></td>
    `;
    tbody.appendChild(row);
    idxPelakuGlobal++;
}

function removePelaku(btn) {
    const tbody = document.getElementById('bodyPelaku');
    if (tbody.querySelectorAll('tr').length <= 1) {
        alert('Minimal harus ada satu data pelaku');
        return;
    }
    btn.closest('tr').remove();
    updateRowNumbers('bodyPelaku');
}

function addBarang() {
    const tbody = document.getElementById('bodyBarang');
    const rowCount = tbody.querySelectorAll('tr').length + 1;
    const row = document.createElement('tr');
    row.className = 'row-barang';
    row.dataset.idx = idxBarangGlobal;
    row.innerHTML = `
        <td>${rowCount}</td>
        <td><input type="text" name="nama_barang[]" placeholder="Nama barang"></td>
        <td><input type="text" name="jenis_barang[]" placeholder="Jenis"></td>
        <td><input type="number" name="jumlah_barang[]" step="0.01" min="0" placeholder="Jumlah"></td>
        <td><input type="text" name="satuan_barang[]" placeholder="Satuan"></td>
        <td><textarea name="jenis_uraian_barang[]" placeholder="Deskripsi barang" style="width: 100%; padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; resize: vertical;"></textarea></td>
        <td><button type="button" class="btn-remove" onclick="removeBarang(this)">Hapus</button></td>
    `;
    tbody.appendChild(row);
    idxBarangGlobal++;
}

function removeBarang(btn) {
    const tbody = document.getElementById('bodyBarang');
    if (tbody.querySelectorAll('tr').length <= 1) {
        alert('Minimal harus ada satu data barang');
        return;
    }
    btn.closest('tr').remove();
    updateRowNumbers('bodyBarang');
}

function updateRowNumbers(tbodyId) {
    const tbody = document.getElementById(tbodyId);
    tbody.querySelectorAll('tr').forEach((row, idx) => {
        row.querySelector('td').textContent = idx + 1;
    });
}
</script>
</body>
</html>
