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
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .action-link:hover { text-decoration: underline; }
        .action-links { display: flex; flex-direction: column; gap: 8px; }
        .action-link { display: flex; align-items: center; gap: 6px; padding: 0; background: transparent; border: none; cursor: pointer; font-size: inherit; color: inherit; text-decoration: none; }
        .nama-petugas { color: #1e40af; text-decoration: none; cursor: pointer; transition: text-decoration 0.15s ease; }
        .nama-petugas:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <button id="toggleSidebar" class="hamburger-btn">☰</button>
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
                                <td><a href="petugas_edit.php?id=<?= $p['id'] ?>" class="nama-petugas"><?= sanitize($p['nama']) ?></a></td>
                                <td style="font-size:13px;font-family:Poppins,sans-serif"><?= sanitize($p['nip']) ?></td>
                                <td><?= sanitize($p['pangkat']) ?></td>
                                <td><?= sanitize($p['jabatan']) ?></td>
                                <td>
                                    <div class="action-links">
                                        <a href="petugas_edit.php?id=<?= $p['id'] ?>" class="action-link" style="color:#166534;" title="Edit">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            Edit
                                        </a>
                                        <a href="petugas.php?hapus=<?= $p['id'] ?>" class="action-link" style="color:#dc2626;" onclick="return confirm('Hapus petugas <?= addslashes(sanitize($p['nama'])) ?>?')" title="Hapus">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                            Hapus
                                        </a>
                                    </div>
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
    <div class="backdrop"></div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
