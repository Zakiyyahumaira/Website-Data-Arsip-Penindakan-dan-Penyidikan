<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    
    $namaMap = trim($_POST['nama_map'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (!$namaMap) {
        $errors[] = 'Nama map wajib diisi.';
    }
    
    // Cek duplikat nama
    if ($namaMap) {
        $cek = $pdo->prepare("SELECT id FROM map WHERE nama_map = ?");
        $cek->execute([$namaMap]);
        if ($cek->fetch()) {
            $errors[] = 'Nama map sudah digunakan.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO map (nama_map, deskripsi, dibuat_oleh)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $namaMap,
            $deskripsi ?: null,
            $_SESSION['user_id']
        ]);
        
        logAktivitas($pdo, $_SESSION['user_id'], 'Tambah map: ' . $namaMap, null);
        header('Location: map.php?msg=tambah');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Map Baru — Arsip Kantor</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content collapsed">
        <div class="topbar">
            <button id="toggleSidebar" class="btn btn-ghost btn-sm">☰</button>
            <h1>📁 Tambah Map Baru</h1>
            <div class="topbar-actions">
                <a href="map.php" class="btn btn-ghost btn-sm">&larr; Kembali</a>
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

            <div class="card" style="max-width: 500px;">
                <div class="card-header"><h3>Formulir Tambah Map</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label" for="nama_map">Nama Map <span style="color:#dc2626">*</span></label>
                            <input class="form-control" type="text" id="nama_map" name="nama_map"
                                   value="<?= sanitize($old['nama_map'] ?? '') ?>"
                                   placeholder="Contoh: Penindakan 2024" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="deskripsi">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi"
                                      placeholder="Deskripsi singkat untuk map ini"><?= sanitize($old['deskripsi'] ?? '') ?></textarea>
                        </div>

                        <div style="display:flex;gap:12px;margin-top:20px">
                            <button type="submit" class="btn btn-primary">Buat Map</button>
                            <a href="map.php" class="btn btn-ghost">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
