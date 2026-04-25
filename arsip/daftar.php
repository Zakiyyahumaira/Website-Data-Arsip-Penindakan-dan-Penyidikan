<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$search    = trim($_GET['q'] ?? '');
$jenisF    = (int)($_GET['jenis'] ?? 0);
$wilayahF  = (int)($_GET['wilayah'] ?? 0);
$mapF      = (int)($_GET['map'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 15;
$offset    = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];
if ($search) {
    $where   .= " AND (a.nama_pegawai LIKE ? OR p1.nama LIKE ? OR p2.nama LIKE ? OR a.no_surat LIKE ? OR a.nama_tempat LIKE ? OR m.nama_map LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($jenisF)   { $where .= " AND a.jenis_pelanggaran_id = ?"; $params[] = $jenisF; }
if ($wilayahF) { $where .= " AND a.wilayah_id = ?";           $params[] = $wilayahF; }
if ($mapF)     { $where .= " AND a.map_id = ?";               $params[] = $mapF; }

$total     = $pdo->prepare("SELECT COUNT(*) FROM arsip a
    LEFT JOIN petugas p1 ON a.petugas_1_id = p1.id
    LEFT JOIN petugas p2 ON a.petugas_2_id = p2.id
    LEFT JOIN map m ON a.map_id = m.id
    $where");
$total->execute($params);
$total     = $total->fetchColumn();
$totalPage = ceil($total / $perPage);

$stmt = $pdo->prepare(
    "SELECT a.*, jp.nama_pelanggaran, w.nama_wilayah, k.nama_kecamatan, m.nama_map, u.nama AS uploader,
            p1.nama AS petugas_1, p2.nama AS petugas_2
     FROM arsip a
     LEFT JOIN petugas p1 ON a.petugas_1_id = p1.id
     LEFT JOIN petugas p2 ON a.petugas_2_id = p2.id
     LEFT JOIN jenis_pelanggaran jp ON a.jenis_pelanggaran_id = jp.id
     LEFT JOIN wilayah w            ON a.wilayah_id = w.id
     LEFT JOIN kecamatan k          ON a.kecamatan_id = k.id
     LEFT JOIN map m                ON a.map_id = m.id
     LEFT JOIN users u              ON a.diunggah_oleh = u.id
     $where
     ORDER BY a.created_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$arsips = $stmt->fetchAll();

$jenisList = $pdo->query("SELECT * FROM jenis_pelanggaran ORDER BY nama_pelanggaran")->fetchAll();
$wilayahs  = $pdo->query("SELECT * FROM wilayah ORDER BY id")->fetchAll();
$mapsList  = $pdo->query("SELECT * FROM map ORDER BY nama_map")->fetchAll();
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Arsip — Arsip Kantor</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="wrapper">
    <?php require '../config/sidebar.php'; ?>
    <div class="main-content collapsed">
        <div class="topbar">
            <button id="toggleSidebar" class="btn btn-ghost btn-sm">☰</button>
            <h1>Daftar Arsip</h1>
            <div class="topbar-actions">
                <a href="tambah.php" class="btn btn-primary btn-sm">+ Upload Arsip</a>
            </div>
        </div>

        <div class="page-body">
            <?php if ($msg === 'tambah'): ?><div class="alert alert-success" data-dismiss="4000">Arsip berhasil ditambahkan.</div>
            <?php elseif ($msg === 'hapus'): ?><div class="alert alert-success" data-dismiss="4000">Arsip berhasil dihapus.</div>
            <?php elseif ($msg === 'edit'): ?><div class="alert alert-success" data-dismiss="4000">Arsip berhasil diperbarui.</div>
            <?php endif; ?>

            <div class="card">
                <!-- Filter & Pencarian -->
                <div class="card-header">
                    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;flex:1">
                        <div class="search-wrap" style="flex:1;min-width:200px">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input class="form-control" type="text" name="q"
                                   placeholder="Cari nama pegawai, no. surat, nama tempat..."
                                   value="<?= sanitize($search) ?>">
                        </div>
                        <select class="form-control" name="jenis" onchange="this.form.submit()" style="width:auto">
                            <option value="">Semua Jenis</option>
                            <?php foreach ($jenisList as $j): ?>
                            <option value="<?= $j['id'] ?>" <?= $jenisF == $j['id'] ? 'selected' : '' ?>>
                                <?= sanitize($j['nama_pelanggaran']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-control" name="wilayah" onchange="this.form.submit()" style="width:auto">
                            <option value="">Semua Wilayah</option>
                            <?php foreach ($wilayahs as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= $wilayahF == $w['id'] ? 'selected' : '' ?>>
                                <?= sanitize($w['nama_wilayah']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-control" name="map" onchange="this.form.submit()" style="width:auto">
                            <option value="">Semua Map</option>
                            <?php foreach ($mapsList as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $mapF == $m['id'] ? 'selected' : '' ?>>
                                <?= sanitize($m['nama_map']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                        <?php if ($search || $jenisF || $wilayahF || $mapF): ?>
                        <a href="daftar.php" class="btn btn-ghost btn-sm">Reset</a>
                        <?php endif; ?>
                    </form>
                    <span style="font-size:13px;color:#6b7280;white-space:nowrap"><?= $total ?> dokumen</span>
                </div>

                <!-- Tabel -->
                <div class="table-wrap">
                    <table id="arsipTable">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>No. Surat</th>
                                <th>Petugas</th>
                                <th>Map</th>
                                <th>Jenis Pelanggaran</th>
                                <th>Wilayah</th>
                                <th>Kecamatan</th>
                                <th>Jumlah</th>
                                <th>Tgl. Dokumen</th>
                                <th>File</th>
                                <th style="width:110px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($arsips)): ?>
                            <tr><td colspan="11">
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                    <p>Tidak ada arsip ditemukan</p>
                                </div>
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($arsips as $i => $a): ?>
                            <tr>
                                <td style="color:#9ca3af"><?= $offset + $i + 1 ?></td>
                                <td><code style="font-size:12px;background:#f3f4f6;padding:2px 6px;border-radius:4px"><?= sanitize($a['no_surat']) ?></code></td>
                                <td><a href="detail.php?id=<?= $a['id'] ?>"><?= sanitize($a['petugas_1'] . ' / ' . $a['petugas_2']) ?></a></td>
                                <td style="font-size:13px"><?= sanitize($a['nama_map'] ?? '-') ?></td>
                                <td><span class="badge badge-blue"><?= sanitize($a['nama_pelanggaran'] ?? '-') ?></span></td>
                                <td style="font-size:13px"><?= sanitize($a['nama_wilayah'] ?? '-') ?></td>
                                <td style="font-size:13px;color:#6b7280"><?= sanitize($a['nama_kecamatan'] ?? '-') ?></td>
                                <td style="font-size:13px;white-space:nowrap">
                                    <?= $a['jumlah'] !== null ? formatJumlah($a['jumlah'], $a['satuan']) : '-' ?>
                                </td>
                                <td style="font-size:13px;color:#6b7280;white-space:nowrap"><?= formatTanggal($a['tanggal_dokumen']) ?></td>
                                <td>
                                    <?php if ($a['file_path']): ?>
                                    <a href="../<?= $a['file_path'] ?>" target="_blank" class="file-link">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        Unduh
                                    </a>
                                    <?php else: ?><span style="color:#9ca3af;font-size:12px">-</span><?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:4px">
                                        <a href="detail.php?id=<?= $a['id'] ?>" class="btn btn-ghost btn-sm" title="Detail">&#128269;</a>
                                        <a href="edit.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm" title="Edit">&#9998;</a>
                                        <button onclick="konfirmasiHapus(<?= $a['id'] ?>, '<?= addslashes(sanitize($a['petugas_1'] . ' / ' . $a['petugas_2'])) ?>')"
                                            class="btn btn-danger btn-sm" title="Hapus">&#128465;</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPage > 1): ?>
                <div style="padding:16px 20px;border-top:1px solid #e5e7eb">
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?q=<?= urlencode($search) ?>&jenis=<?= $jenisF ?>&wilayah=<?= $wilayahF ?>&map=<?= $mapF ?>&page=<?= $page-1 ?>" class="page-btn">&lsaquo; Sebelumnya</a>
                        <?php endif; ?>
                        <?php for ($p = max(1,$page-2); $p <= min($totalPage,$page+2); $p++): ?>
                        <a href="?q=<?= urlencode($search) ?>&jenis=<?= $jenisF ?>&wilayah=<?= $wilayahF ?>&map=<?= $mapF ?>&page=<?= $p ?>"
                           class="page-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPage): ?>
                        <a href="?q=<?= urlencode($search) ?>&jenis=<?= $jenisF ?>&wilayah=<?= $wilayahF ?>&map=<?= $mapF ?>&page=<?= $page+1 ?>" class="page-btn">Berikutnya &rsaquo;</a>
                        <?php endif; ?>
                        <span style="font-size:13px;color:#6b7280;margin-left:8px">Halaman <?= $page ?> dari <?= $totalPage ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
