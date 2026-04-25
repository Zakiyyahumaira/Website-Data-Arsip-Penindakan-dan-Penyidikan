<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: petugas.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM petugas WHERE id = ?");
$stmt->execute([$id]);
$petugas = $stmt->fetch();
if (!$petugas) {
    header('Location: petugas.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama'] ?? '');
    $nip     = trim($_POST['nip'] ?? '');
    $pangkat = trim($_POST['pangkat'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');

    if (!$nama || !$nip || !$pangkat || !$jabatan) {
        $errors[] = 'Semua field petugas wajib diisi.';
    } else {
        $cek = $pdo->prepare("SELECT id FROM petugas WHERE nama = ? AND nip = ? AND id != ?");
        $cek->execute([$nama, $nip, $id]);
        if ($cek->fetch()) {
            $errors[] = 'Petugas dengan nama dan NIP tersebut sudah ada.';
        } else {
            $stmt = $pdo->prepare("UPDATE petugas SET nama = ?, nip = ?, pangkat = ?, jabatan = ? WHERE id = ?");
            $stmt->execute([$nama, $nip, $pangkat, $jabatan, $id]);
            logAktivitas($pdo, $_SESSION['user_id'], 'Edit petugas: ' . $nama, null);
            header('Location: petugas.php?msg=edit');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Petugas — Arsip Kantor</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content collapsed">
        <div class="topbar">
            <button id="toggleSidebar" class="btn btn-ghost btn-sm">☰</button>
            <h1>Edit Petugas</h1>
            <div class="topbar-actions">
                <a href="petugas.php" class="btn btn-ghost btn-sm">&larr; Kembali</a>
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

            <div class="card" style="max-width:500px;">
                <div class="card-header"><h3>Edit Data Petugas</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Nama Petugas *</label>
                            <input class="form-control" type="text" name="nama"
                                   value="<?= sanitize($_POST['nama'] ?? $petugas['nama']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">NIP *</label>
                            <input class="form-control" type="text" name="nip"
                                   value="<?= sanitize($_POST['nip'] ?? $petugas['nip']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pangkat *</label>
                            <input class="form-control" type="text" name="pangkat"
                                   value="<?= sanitize($_POST['pangkat'] ?? $petugas['pangkat']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jabatan *</label>
                            <input class="form-control" type="text" name="jabatan"
                                   value="<?= sanitize($_POST['jabatan'] ?? $petugas['jabatan']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="petugas.php" class="btn btn-ghost">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
