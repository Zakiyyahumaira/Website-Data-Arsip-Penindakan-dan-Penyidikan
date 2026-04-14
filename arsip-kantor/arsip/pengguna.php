<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekAdmin();

$errors = [];
$msg    = $_GET['msg'] ?? '';

// Tambah pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['admin','staff']) ? $_POST['role'] : 'staff';

    if (!$nama || !$username || !$password) {
        $errors[] = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    } else {
        $cek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $cek->execute([$username]);
        if ($cek->fetch()) {
            $errors[] = 'Username sudah digunakan.';
        } else {
            $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, MD5(?), ?)")
                ->execute([$nama, $username, $password, $role]);
            header('Location: pengguna.php?msg=tambah');
            exit;
        }
    }
}

// Hapus pengguna
if (isset($_GET['hapus'])) {
    $uid = (int)$_GET['hapus'];
    if ($uid !== $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
    }
    header('Location: pengguna.php?msg=hapus');
    exit;
}

$users = $pdo->query(
    "SELECT u.*, COUNT(a.id) as total_upload
     FROM users u
     LEFT JOIN arsip a ON a.diunggah_oleh = u.id
     GROUP BY u.id ORDER BY u.created_at DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengguna — Arsip Kantor</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar"><h1>Manajemen Pengguna</h1></div>

        <div class="page-body">
            <?php if ($msg === 'tambah'): ?>
            <div class="alert alert-success" data-dismiss="3000">Pengguna berhasil ditambahkan.</div>
            <?php elseif ($msg === 'hapus'): ?>
            <div class="alert alert-success" data-dismiss="3000">Pengguna berhasil dihapus.</div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?= sanitize($errors[0]) ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1.8fr;gap:20px;align-items:start">
                <!-- Form tambah -->
                <div class="card">
                    <div class="card-header"><h3>Tambah Pengguna</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="tambah">
                            <div class="form-group">
                                <label class="form-label">Nama Lengkap *</label>
                                <input class="form-control" type="text" name="nama" required placeholder="Nama lengkap">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Username *</label>
                                <input class="form-control" type="text" name="username" required placeholder="username_unik">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password *</label>
                                <input class="form-control" type="password" name="password" required placeholder="Min. 6 karakter">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Role *</label>
                                <select class="form-control" name="role">
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Tambah Pengguna</button>
                        </form>
                    </div>
                </div>

                <!-- Daftar pengguna -->
                <div class="card">
                    <div class="card-header"><h3>Daftar Pengguna (<?= count($users) ?>)</h3></div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Upload</th>
                                    <th>Bergabung</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $i => $u): ?>
                            <tr>
                                <td style="color:#9ca3af"><?= $i+1 ?></td>
                                <td><strong><?= sanitize($u['nama']) ?></strong></td>
                                <td style="font-size:13px"><code><?= sanitize($u['username']) ?></code></td>
                                <td>
                                    <span class="badge <?= $u['role'] === 'admin' ? 'badge-purple' : 'badge-gray' ?>">
                                        <?= ucfirst($u['role']) ?>
                                    </span>
                                </td>
                                <td><?= $u['total_upload'] ?> arsip</td>
                                <td style="font-size:12px;color:#9ca3af"><?= formatTanggal(substr($u['created_at'],0,10)) ?></td>
                                <td>
                                    <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                    <a href="pengguna.php?hapus=<?= $u['id'] ?>"
                                       onclick="return confirm('Hapus pengguna <?= addslashes(sanitize($u['nama'])) ?>?')"
                                       class="btn btn-danger btn-sm">Hapus</a>
                                    <?php else: ?>
                                    <span style="font-size:12px;color:#9ca3af">Anda</span>
                                    <?php endif; ?>
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
