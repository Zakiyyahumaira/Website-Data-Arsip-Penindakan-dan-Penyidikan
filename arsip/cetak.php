<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    exit('ID tidak valid');
}

$stmt = $pdo->prepare(
    "SELECT a.*, 
            jp.nama_pelanggaran, 
            w.nama_wilayah, 
            k.nama_kecamatan,

            p1.nama AS petugas_1,
            p2.nama AS petugas_2

     FROM arsip a

     LEFT JOIN petugas p1 
        ON a.petugas_1_id = p1.id

     LEFT JOIN petugas p2 
        ON a.petugas_2_id = p2.id

     LEFT JOIN jenis_pelanggaran jp 
        ON a.jenis_pelanggaran_id = jp.id

     LEFT JOIN wilayah w 
        ON a.wilayah_id = w.id

     LEFT JOIN kecamatan k 
        ON a.kecamatan_id = k.id

     WHERE a.id = ?"
);

$stmt->execute([$id]);

$arsip = $stmt->fetch();

if (!$arsip) {
    exit('Data tidak ditemukan');
}

/* =========================
   DATA PELAKU
========================= */

$pelakuList = $pdo->prepare("
    SELECT * 
    FROM pelaku 
    WHERE arsip_id = ?
");

$pelakuList->execute([$id]);

$pelakuList = $pelakuList->fetchAll();

/* =========================
   DATA BARANG
========================= */

$barangList = $pdo->prepare("
    SELECT * 
    FROM barang_hasil_penindakan 
    WHERE arsip_id = ?
");

$barangList->execute([$id]);

$barangList = $barangList->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">

<title>Cetak Arsip</title>

<style>

@page{
    size:A4;
    margin:25px;
}

body{
    font-family:"Times New Roman", serif;
    font-size:12pt;
    color:#000;
    margin:0;
    padding:0;
}

table{
    width:100%;
    border-collapse:collapse;
}

td{
    vertical-align:top;
    padding:2px 4px;
}

.kop-wrapper{
    width:100%;
    border-bottom:3px solid #000;
    padding-bottom:10px;
    margin-bottom:25px;
}

.logo{
    width:90px;
}

.kop-text{
    text-align:center;
    line-height:1.2;
}

.kop-1{
    font-size:16pt;
    font-weight:bold;
}

.kop-2{
    font-size:15pt;
    font-weight:bold;
}

.kop-3{
    font-size:13pt;
    font-weight:bold;
}

.kop-4{
    font-size:10pt;
    margin-top:2px;
}

.judul{
    text-align:center;
    font-weight:bold;
    margin-top:20px;
    margin-bottom:25px;
    font-size:18pt;
    text-decoration:underline;
}

.info-table td{
    padding:3px 4px;
}

.sub-table{
    margin-left:40px;
    margin-bottom:12px;
}

@media print{
    body{
        zoom:100%;
    }
}

</style>
</head>

<body>

<!-- KOP SURAT -->

<div class="kop-wrapper">

    <table>

        <tr>

            <td width="110" align="center">
                <img src="../assets/logo_kemenkeu.png" class="logo">
            </td>

            <td class="kop-text">

                <div class="kop-1">
                    KEMENTERIAN KEUANGAN REPUBLIK INDONESIA
                </div>

                <div class="kop-2">
                    DIREKTORAT JENDERAL BEA DAN CUKAI
                </div>

                <div class="kop-3">
                    KANTOR WILAYAH DIREKTORAT JENDERAL BEA DAN CUKAI ACEH
                </div>

                <div class="kop-3">
                    KANTOR PENGAWASAN DAN PELAYANAN BEA DAN CUKAI TIPE MADYA
                </div>

                <div class="kop-3">
                    PABEAN C BANDA ACEH
                </div>

                <div class="kop-4">
                    Jalan Soekarno Hatta Nomor 3A, Geuceu Menara, Banda Aceh 23241
                </div>

                <div class="kop-4">
                    TELEPON (0651) 43137, FAKSIMILE (0651) 43136
                </div>

                <div class="kop-4">
                    LAMAN www.beacukai.go.id; PUSAT KONTAK LAYANAN 1500225
                </div>

                <div class="kop-4">
                    SUREL beaaceh@customs.go.id
                </div>

            </td>

        </tr>

    </table>

</div>

<!-- JUDUL -->

<div class="judul">
    ARSIP PENINDAKAN
</div>

<!-- INFORMASI -->

<table class="info-table">

<tr>
    <td width="40">1.</td>
    <td width="220">MAP</td>
    <td width="20">:</td>
    <td><?= sanitize($arsip['map_id'] ?? '-') ?></td>
</tr>

<tr>
    <td>2.</td>
    <td>No. Surat</td>
    <td>:</td>
    <td><?= sanitize($arsip['no_surat']) ?></td>
</tr>

<tr>
    <td>3.</td>
    <td>Petugas</td>
    <td>:</td>
    <td>
        1) <?= sanitize($arsip['petugas_1']) ?><br>
        2) <?= sanitize($arsip['petugas_2']) ?>
    </td>
</tr>

<tr>
    <td>4.</td>
    <td>Jenis Pelanggaran</td>
    <td>:</td>
    <td><?= sanitize($arsip['nama_pelanggaran']) ?></td>
</tr>

<tr>
    <td>5.</td>
    <td>Wilayah</td>
    <td>:</td>
    <td><?= sanitize($arsip['nama_wilayah']) ?></td>
</tr>

<tr>
    <td>6.</td>
    <td>Kecamatan</td>
    <td>:</td>
    <td><?= sanitize($arsip['nama_kecamatan']) ?></td>
</tr>

<tr>
    <td>7.</td>
    <td>Nama Tempat</td>
    <td>:</td>
    <td><?= sanitize($arsip['nama_tempat']) ?></td>
</tr>

<tr>
    <td>8.</td>
    <td>Waktu Penindakan</td>
    <td>:</td>
    <td><?= sanitize($arsip['waktu_penindakan']) ?></td>
</tr>

<tr>
    <td>9.</td>
    <td>Tanggal Dokumen</td>
    <td>:</td>
    <td><?= sanitize($arsip['tanggal_dokumen']) ?></td>
</tr>

<tr>
    <td>10.</td>
    <td>Pelaku</td>
    <td>:</td>
    <td></td>
</tr>

</table>

<?php foreach($pelakuList as $i => $p): ?>

<table class="sub-table">

<tr>
    <td width="220">Nama</td>
    <td width="20">:</td>
    <td><?= sanitize($p['nama']) ?></td>
</tr>

<tr>
    <td>Identitas</td>
    <td>:</td>
    <td><?= sanitize($p['identitas']) ?></td>
</tr>

<tr>
    <td>No. Identitas</td>
    <td>:</td>
    <td><?= sanitize($p['no_identitas']) ?></td>
</tr>

<tr>
    <td>Jenis Kelamin</td>
    <td>:</td>
    <td><?= sanitize($p['jenis_kelamin']) ?></td>
</tr>

<tr>
    <td>Alamat</td>
    <td>:</td>
    <td><?= sanitize($p['alamat']) ?></td>
</tr>

</table>

<?php endforeach; ?>

<table class="info-table">

<tr>
    <td width="40">11.</td>
    <td width="220">Barang Hasil Penindakan</td>
    <td width="20">:</td>
    <td></td>
</tr>

</table>

<?php foreach($barangList as $i => $b): ?>

<table class="sub-table">

<tr>
    <td width="220">Nama Barang</td>
    <td width="20">:</td>
    <td><?= sanitize($b['nama_barang']) ?></td>
</tr>

<tr>
    <td>Jenis Barang</td>
    <td>:</td>
    <td><?= sanitize($b['jenis_barang']) ?></td>
</tr>

<tr>
    <td>Jumlah</td>
    <td>:</td>
    <td><?= sanitize($b['jumlah_barang']) ?></td>
</tr>

<tr>
    <td>Satuan</td>
    <td>:</td>
    <td><?= sanitize($b['satuan']) ?></td>
</tr>

<tr>
    <td>Jenis Uraian Barang</td>
    <td>:</td>
    <td><?= sanitize($b['jenis_uraian_barang']) ?></td>
</tr>

</table>

<?php endforeach; ?>

<script>
window.print();
</script>

</body>
</html>