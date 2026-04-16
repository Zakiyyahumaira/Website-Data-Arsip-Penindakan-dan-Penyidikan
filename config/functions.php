<?php
function cekLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

function cekAdmin() {
    cekLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'dashboard.php?error=akses');
        exit;
    }
}

function logAktivitas($pdo, $user_id, $aksi, $arsip_id = null) {
    $stmt = $pdo->prepare("INSERT INTO log_aktivitas (user_id, aksi, arsip_id) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $aksi, $arsip_id]);
}

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim((string)$str)), ENT_QUOTES, 'UTF-8');
}

function formatTanggal($date) {
    if (!$date) return '-';
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $d = explode('-', substr($date, 0, 10));
    if (count($d) < 3) return $date;
    return $d[2] . ' ' . $bulan[(int)$d[1]] . ' ' . $d[0];
}

function formatJumlah($angka, $satuan = '') {
    if ($angka === null || $angka === '') return '-';
    $formatted = number_format((float)$angka, 2, ',', '.');
    // Hapus desimal jika .00
    $formatted = preg_replace('/,00$/', '', $formatted);
    return $formatted . ($satuan ? ' ' . $satuan : '');
}

function uploadFile($file, $uploadDir = 'uploads/') {
    $allowedExt = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png'];
    $maxSize    = 10 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) return ['ok' => false, 'msg' => 'Upload gagal.'];
    if ($file['size'] > $maxSize)         return ['ok' => false, 'msg' => 'File terlalu besar (maks 10 MB).'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt))    return ['ok' => false, 'msg' => 'Format file tidak diizinkan.'];

    $newName = uniqid('arsip_', true) . '.' . $ext;
    $dest    = $uploadDir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return ['ok' => false, 'msg' => 'Gagal menyimpan file.'];

    return ['ok' => true, 'path' => $dest, 'name' => $file['name']];
}

// Ambil semua kecamatan berdasarkan wilayah_id (untuk AJAX atau form)
function getKecamatanByWilayah($pdo, $wilayah_id) {
    $stmt = $pdo->prepare("SELECT id, nama_kecamatan FROM kecamatan WHERE wilayah_id = ? ORDER BY nama_kecamatan");
    $stmt->execute([$wilayah_id]);
    return $stmt->fetchAll();
}

// ============================================================
// FUNCTIONS UNTUK FITUR MAP/FOLDER (SIMPLE VERSION)
// ============================================================

// Fungsi untuk mendapatkan semua map
function getSemuaMap($pdo) {
    $stmt = $pdo->prepare("
        SELECT * FROM map
        ORDER BY nama_map ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Fungsi untuk mendapatkan detail satu map
function getMapDetail($pdo, $map_id) {
    $stmt = $pdo->prepare("SELECT * FROM map WHERE id = ?");
    $stmt->execute([$map_id]);
    return $stmt->fetch();
}

// Fungsi untuk mendapatkan arsip dalam satu map
function getArsipByMap($pdo, $map_id) {
    $stmt = $pdo->prepare("
        SELECT a.* FROM arsip a
        WHERE a.map_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$map_id]);
    return $stmt->fetchAll();
}

// Fungsi untuk menghitung jumlah arsip dalam map
function hitungArsipInMap($pdo, $map_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as jml FROM arsip WHERE map_id = ?");
    $stmt->execute([$map_id]);
    $result = $stmt->fetch();
    return $result['jml'] ?? 0;
}

// BASE_URL dihitung dinamis berdasarkan PHP_SELF
if (!defined('BASE_URL')) {
    $parts = explode('/', trim($_SERVER['PHP_SELF'], '/'));
    $depth = count($parts) - 2;
    define('BASE_URL', str_repeat('../', max(0, $depth)));
}
?>
