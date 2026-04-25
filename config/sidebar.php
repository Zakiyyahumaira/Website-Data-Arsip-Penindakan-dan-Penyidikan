<?php
// Hitung base URL berdasarkan PHP_SELF
// /arsip-kantor/dashboard.php         -> depth=0 -> base=''
// /arsip-kantor/arsip/daftar.php      -> depth=1 -> base='../'
$parts = explode('/', trim($_SERVER['PHP_SELF'], '/'));
// $parts[0] = 'arsip-kantor', $parts[1] = file atau subfolder
$depth = count($parts) - 2; // kurangi nama_folder + nama_file
$base  = str_repeat('../', max(0, $depth));

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

function isActive($pages) {
    global $currentPage, $currentDir;
    foreach ((array)$pages as $p) {
        if (strpos($p, '/') !== false) {
            list($d, $f) = explode('/', $p, 2);
            if ($currentDir === $d && $currentPage === $f) return 'active';
        } else {
            if ($currentPage === $p) return 'active';
        }
    }
    return '';
}
?>
<div class="sidebar collapsed">
    <div class="sidebar-brand">
        <h2>&#128193; Arsip Kantor</h2>
        <span>Sistem Manajemen Arsip</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Menu Utama</div>

        <a href="<?= $base ?>dashboard.php" class="nav-item <?= isActive('dashboard.php') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            <span>Dashboard</span>
        </a>

        <a href="<?= $base ?>arsip/map.php" class="nav-item <?= isActive(['arsip/map.php','arsip/map_detail.php','arsip/map_tambah.php','arsip/map_edit.php']) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <span>Daftar Map</span>
        </a>
                
        <a href="<?= $base ?>arsip/daftar.php" class="nav-item <?= isActive(['arsip/daftar.php','arsip/detail.php','arsip/edit.php']) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <span>Daftar Arsip</span>
        </a>

        <a href="<?= $base ?>arsip/tambah.php" class="nav-item <?= isActive('arsip/tambah.php') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            <span>Upload Arsip</span>
        </a>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="nav-section">Admin</div>

        <a href="<?= $base ?>arsip/kategori.php" class="nav-item <?= isActive('arsip/kategori.php') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            <span>Referensi Data</span>
        </a>

        <a href="<?= $base ?>arsip/pengguna.php" class="nav-item <?= isActive('arsip/pengguna.php') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Pengguna</span>
        </a>

        <a href="<?= $base ?>arsip/petugas.php" class="nav-item <?= isActive('arsip/petugas.php') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><circle cx="16" cy="8" r="3"/><path d="M7 11c0-2 3-3 5-3s5 1 5 3v2"/></svg>
            <span>Petugas</span>
        </a>

        <a href="<?= $base ?>arsip/log.php" class="nav-item <?= isActive('arsip/log.php') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Log Aktivitas</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <strong><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></strong>
        <?= ucfirst($_SESSION['role'] ?? '') ?>
        &nbsp;·&nbsp;
        <a href="<?= $base ?>auth/logout.php" style="color:var(--gray-400)">Keluar</a>
    </div>
</div>