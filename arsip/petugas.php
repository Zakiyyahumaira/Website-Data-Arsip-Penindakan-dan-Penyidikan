<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekAdmin();

$errors = [];
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama'] ?? '');
    $nip     = trim($_POST['nip'] ?? '');
    $pangkat = trim($_POST['pangkat'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');

    if (!$nama || !$nip || !$pangkat || !$jabatan) {
        $errors[] = 'Semua field petugas wajib diisi.';
    } else {
        $cek = $pdo->prepare("SELECT id FROM petugas WHERE nama = ? AND nip = ?");
        $cek->execute([$nama, $nip]);
        if ($cek->fetch()) {
            $errors[] = 'Petugas dengan nama dan NIP tersebut sudah ada.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO petugas (nama, nip, pangkat, jabatan, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $nip, $pangkat, $jabatan, $_SESSION['user_id']]);
            logAktivitas($pdo, $_SESSION['user_id'], 'Tambah petugas: ' . $nama, null);
            header('Location: petugas.php?msg=tambah');
            exit;
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $petugas = $pdo->prepare("SELECT nama FROM petugas WHERE id = ?");
    $petugas->execute([$id]);
    $row = $petugas->fetch();
    if ($row) {
        $pdo->prepare("DELETE FROM petugas WHERE id = ?")->execute([$id]);
        logAktivitas($pdo, $_SESSION['user_id'], 'Hapus petugas: ' . $row['nama'], null);
    }
    header('Location: petugas.php?msg=hapus');
    exit;
}

$petugasList = $pdo->query("SELECT * FROM petugas ORDER BY nama")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Petugas — Arsip Kantor</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content collapsed">
        <div class="topbar">
            <button id="toggleSidebar" class="btn btn-ghost btn-sm">☰</button>
            <h1>Manajemen Petugas</h1>
        </div>

        <div class="page-body">
            <?php if ($msg === 'tambah'): ?><div class="alert alert-success" data-dismiss="3000">Petugas berhasil ditambahkan.</div><?php endif; ?>
            <?php if ($msg === 'hapus'): ?><div class="alert alert-success" data-dismiss="3000">Petugas berhasil dihapus.</div><?php endif; ?>
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin:0;padding-left:18px">
                    <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;align-items:start">
                <div class="card">
                    <div class="card-header"><h3>Tambah Petugas</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Nama Petugas *</label>
                                <input class="form-control" type="text" name="nama" required placeholder="Nama lengkap petugas">
                            </div>
                            <div class="form-group">
                                <label class="form-label">NIP *</label>
                                <input class="form-control" type="text" name="nip" required placeholder="Nomor Induk Pegawai">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Pangkat *</label>
                                <input class="form-control" type="text" name="pangkat" required placeholder="Pangkat petugas">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Jabatan *</label>
                                <input class="form-control" type="text" name="jabatan" required placeholder="Jabatan petugas">
                            </div>
                            <button type="submit" class="btn btn-primary">Tambah Petugas</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Daftar Petugas (<?= count($petugasList) ?>)</h3></div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Nama</th>
                                    <th>NIP</th>
                                    <th>Pangkat</th>
                                    <th>Jabatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($petugasList as $i => $p): ?>
                            <tr>
                                <td style="color:#9ca3af"><?= $i + 1 ?></td>
                                <td><strong><?= sanitize($p['nama']) ?></strong></td>
                                <td style="font-size:13px"><code><?= sanitize($p['nip']) ?></code></td>
                                <td><?= sanitize($p['pangkat']) ?></td>
                                <td><?= sanitize($p['jabatan']) ?></td>
                                <td>
                                    <a href="petugas_edit.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <a href="petugas.php?hapus=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus petugas <?= addslashes(sanitize($p['nama'])) ?>?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
