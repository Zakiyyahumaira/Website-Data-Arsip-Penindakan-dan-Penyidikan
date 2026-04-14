<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Arsip Kantor</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-bg">
    <div class="login-card">
        <div class="login-logo">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px;display:block">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
            <h1>Sistem Arsip Kantor</h1>
            <p>Masuk untuk mengelola dokumen arsip</p>
        </div>

        <?php if ($error === 'wrong'): ?>
        <div class="alert alert-danger" data-dismiss="4000">
            Username atau password salah. Coba lagi.
        </div>
        <?php elseif ($error === 'logout'): ?>
        <div class="alert alert-success" data-dismiss="3000">
            Anda berhasil keluar.
        </div>
        <?php endif; ?>

        <form action="auth/login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input class="form-control" type="text" id="username" name="username"
                       placeholder="Masukkan username" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div style="position:relative">
                    <input class="form-control" type="password" id="password" name="password"
                           placeholder="Masukkan password" required style="padding-right:100px">
                    <button type="button" onclick="togglePassword('password')"
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                               border:none;background:none;cursor:pointer;font-size:13px;color:#6b7280">
                        Tampilkan
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px">
                Masuk
            </button>
        </form>

        <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:24px">
            Default: admin / admin123 &nbsp;|&nbsp; staff / staff123
        </p>
    </div>
</div>
<script src="js/main.js"></script>
</body>
</html>
