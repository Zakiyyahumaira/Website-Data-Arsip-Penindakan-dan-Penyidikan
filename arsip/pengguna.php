<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekAdmin();

$errors = [];
$msg    = $_GET['msg'] ?? '';
$show   = $_GET['show'] ?? '';

// Tambah pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    $show = 'tambah';
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .action-link:hover {
            text-decoration: underline;
        }
        .modal-backdrop { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(15,23,42,0.55); opacity: 0; pointer-events: none; transition: opacity .2s ease; z-index: 1000; }
        .modal-backdrop.open { opacity: 1; pointer-events: auto; }
        .modal-panel { width: min(95vw, 520px); background: #ffffff; border-radius: 18px; box-shadow: 0 20px 60px rgba(15,23,42,.18); transform: translateY(-20px); transition: transform .2s ease; overflow: hidden; }
        .modal-backdrop.open .modal-panel { transform: translateY(0); }
        .modal-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 20px; border-bottom: 1px solid #e5e7eb; }
        .modal-title { margin: 0; font-size: 18px; font-weight: 700; }
        .modal-body { padding: 20px; }
        .modal-close { width: 36px; height: 36px; border-radius: 12px; border: none; background: #f3f4f6; cursor: pointer; font-size: 18px; color: #111827; }
        .modal-close:hover { background: #e5e7eb; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require '../config/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <button id="toggleSidebar" class="hamburger-btn">☰</button>
            <h1>Pengguna</h1>
        </div>

        <div class="page-body">
            <?php if ($msg === 'tambah'): ?>
            <div class="alert alert-success" data-dismiss="3000">Pengguna berhasil ditambahkan.</div>
            <?php elseif ($msg === 'hapus'): ?>
            <div class="alert alert-success" data-dismiss="3000">Pengguna berhasil dihapus.</div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?= sanitize($errors[0]) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header" style="flex-direction: row; justify-content: space-between; align-items: center;">
                    <h3 style="margin:0;">Daftar Pengguna (<?= count($users) ?>)</h3>
                    <button type="button" class="btn btn-primary btn-sm" onclick="openPenggunaModal()">+ Tambah Pengguna</button>
                </div>
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
                                <td style="text-align:center;vertical-align:middle;">
                                    <div class="action-links" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;width:100%;">
                                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                        <a href="pengguna.php?hapus=<?= $u['id'] ?>"
                                           onclick="return confirm('Hapus pengguna <?= addslashes(sanitize($u['nama'])) ?>?')"
                                           class="action-link" style="display:inline-flex;align-items:center;gap:6px;padding:0;background:transparent;color:#dc2626;border:none;cursor:pointer;font-size:inherit" title="Hapus">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                            Hapus
                                        </a>
                                        <?php else: ?>
                                        <span style="font-size:12px;color:#9ca3af">Anda</span>
                                        <?php endif; ?>
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
    <div id="penggunaModal" class="modal-backdrop" onclick="closePenggunaModal(event)">
        <div class="modal-panel" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2 class="modal-title">Tambah Pengguna</h2>
                <button type="button" class="modal-close" onclick="closePenggunaModal()">×</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="penggunaForm">
                    <input type="hidden" name="action" value="tambah">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap *</label>
                        <input class="form-control" type="text" name="nama" required placeholder="Nama lengkap" value="<?= sanitize($_POST['nama'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input class="form-control" type="text" name="username" required placeholder="username_unik" value="<?= sanitize($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input class="form-control" type="password" name="password" required placeholder="Min. 6 karakter">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select class="form-control" name="role">
                            <option value="staff" <?= (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'selected' : '' ?>>Staff</option>
                            <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;margin-top:16px">
                        <button type="button" class="btn btn-ghost" onclick="closePenggunaModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
<script>
function openPenggunaModal() {
    document.getElementById('penggunaModal').classList.add('open');
}
function closePenggunaModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('penggunaModal').classList.remove('open');
}
<?php if ($show === 'tambah'): ?>
openPenggunaModal();
<?php endif; ?>
</script>
</body>
</html>
