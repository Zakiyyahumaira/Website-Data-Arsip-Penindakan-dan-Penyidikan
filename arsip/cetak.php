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
    size: A4;
    margin: 30mm 25mm 30mm 25mm;
}

body{
    font-family: "Times New Roman", serif;
    font-size: 12pt;
    color: #000;
    margin: 0;
    padding: 0;
    line-height: 1.5;
}

/* =========================
   GLOBAL
========================= */

table{
    width: 100%;
    border-collapse: collapse;
}

td{
    vertical-align: top;
    padding: 3px 0;
}


/* =========================
   KOP SURAT
========================= */

.kop-wrapper{
    width: 100%;
    border-bottom: 3px solid #000;
    padding-bottom: 10px;
    margin-bottom: 30px;
}

.kop-table{
    width: 100%;
}

.kop-table td{
    vertical-align: middle;
    padding: 0;
}

.logo-cell{
    width: 95px;
    text-align: center;
}

.logo{
    width: 74px;
}

.kop-text{
    text-align: center;
    line-height: 1.3;
    padding-right: 20px;
}

.kop-1{
    font-size: 13pt;
    font-weight: bold;
    letter-spacing: 0.3px;
}

.kop-2{
    font-size: 13pt;
    font-weight: bold;
    letter-spacing: 0.3px;
}

.kop-3{
    font-size: 12pt;
    font-weight: bold;
    letter-spacing: 0.2px;
}

/* =========================
   JUDUL
========================= */

.judul{
    text-align: center;
    font-size: 18pt;
    font-weight: bold;
    text-decoration: underline;
    margin-bottom: 35px;
    letter-spacing: 0.5px;
}

/* =========================
   TABEL UTAMA
========================= */

.info-table{
    width: 100%;
    table-layout: fixed;
}

.no{
    width: 40px;
}

.label{
    width: 240px;
}

.titik{
    width: 25px;
    text-align: center;
}

.isi{
    width: auto;
    text-align: justify;
}

/* =========================
   SUB TABLE
========================= */

.sub-table{
    width: 100%;
    margin-top: 8px;
    margin-bottom: 18px;
    table-layout: fixed;
}

.sub-no{
    width: 40px;
}

.sub-label{
    width: 240px;
}

.sub-titik{
    width: 25px;
    text-align: center;
}

/* =========================
   PRINT
========================= */

@media print{

    body{
        zoom: 100%;
    }

    .info-table,
    .sub-table{
        page-break-inside: avoid;
    }
}

</style>
</head>

<body>

<!-- =========================
     KOP SURAT
========================= -->

<div class="kop-wrapper">

    <table class="kop-table">

        <tr>

            <td class="logo-cell">
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
                    KANTOR WILAYAH DIREKTORAT JENDERAL
                </div>

                <div class="kop-3">
                    BEA DAN CUKAI ACEH
                </div>

                <div class="kop-3">
                    KANTOR PENGAWASAN DAN PELAYANAN BEA DAN CUKAI
                </div>

                <div class="kop-3">
                    TIPE MADYA PABEAN C BANDA ACEH
                </div>

            </td>

        </tr>

    </table>

</div>

<!-- =========================
     JUDUL
========================= -->

<div class="judul">
    ARSIP PENINDAKAN
</div>

<!-- =========================
     INFORMASI UTAMA
========================= -->

<table class="info-table">

<tr>
    <td class="no">1.</td>
    <td class="label">MAP</td>
    <td class="titik">:</td>
    <td class="isi"><?= sanitize($arsip['map_id'] ?? '-') ?></td>
</tr>

<tr>
    <td class="no">2.</td>
    <td class="label">No. Surat</td>
    <td class="titik">:</td>
    <td class="isi"><?= sanitize($arsip['no_surat']) ?></td>
</tr>

<tr>
    <td class="no">3.</td>
    <td class="label">Petugas</td>
    <td class="titik">:</td>
    <td class="isi">
        1) <?= sanitize($arsip['petugas_1']) ?><br>
        2) <?= sanitize($arsip['petugas_2']) ?>
    </td>
</tr>

<tr>
    <td class="no">4.</td>
    <td class="label">Jenis Pelanggaran</td>
    <td class="titik">:</td>
    <td class="isi"><?= sanitize($arsip['nama_pelanggaran']) ?></td>
</tr>

<tr>
    <td class="no">5.</td>
    <td class="label">Wilayah</td>
    <td class="titik">:</td>
    <td class="isi"><?= sanitize($arsip['nama_wilayah']) ?></td>
</tr>

<tr>
    <td class="no">6.</td>
    <td class="label">Kecamatan</td>
    <td class="titik">:</td>
    <td class="isi"><?= sanitize($arsip['nama_kecamatan']) ?></td>
</tr>

<tr>
    <td class="no">7.</td>
    <td class="label">Nama Tempat</td>
    <td class="titik">:</td>
    <td class="isi"><?= sanitize($arsip['nama_tempat']) ?></td>
</tr>

<tr>
    <td class="no">8.</td>
    <td class="label">Waktu Penindakan</td>
    <td class="titik">:</td>
    <td class="isi"><?= sanitize($arsip['waktu_penindakan']) ?></td>
</tr>

<tr>
    <td class="no">9.</td>
    <td class="label">Tanggal Dokumen</td>
    <td class="titik">:</td>
    <td class="isi"><?= sanitize($arsip['tanggal_dokumen']) ?></td>
</tr>

<tr>
    <td class="no">10.</td>
    <td class="label">Pelaku</td>
    <td class="titik">:</td>
    <td></td>
</tr>

</table>

<!-- =========================
     DATA PELAKU
========================= -->

<?php foreach($pelakuList as $i => $p): ?>

<table class="sub-table">

<tr>
    <td class="sub-no"></td>
    <td class="sub-label">Nama</td>
    <td class="sub-titik">:</td>
    <td><?= sanitize($p['nama']) ?></td>
</tr>

<tr>
    <td class="sub-no"></td>
    <td class="sub-label">Identitas</td>
    <td class="sub-titik">:</td>
    <td><?= sanitize($p['identitas']) ?></td>
</tr>

<tr>
    <td class="sub-no"></td>
    <td class="sub-label">No. Identitas</td>
    <td class="sub-titik">:</td>
    <td><?= sanitize($p['no_identitas']) ?></td>
</tr>

<tr>
    <td class="sub-no"></td>
    <td class="sub-label">Jenis Kelamin</td>
    <td class="sub-titik">:</td>
    <td><?= sanitize($p['jenis_kelamin']) ?></td>
</tr>

<tr>
    <td class="sub-no"></td>
    <td class="sub-label">Alamat</td>
    <td class="sub-titik">:</td>
    <td><?= sanitize($p['alamat']) ?></td>
</tr>

</table>

<?php endforeach; ?>

<!-- =========================
     BARANG HASIL PENINDAKAN
========================= -->

<table class="info-table">

<tr>
    <td class="no">11.</td>
    <td class="label">Barang Hasil Penindakan</td>
    <td class="titik">:</td>
    <td></td>
</tr>

</table>

<?php foreach($barangList as $i => $b): ?>

<table class="sub-table">

<tr>
    <td class="sub-no"></td>
    <td class="sub-label">Nama Barang</td>
    <td class="sub-titik">:</td>
    <td><?= sanitize($b['nama_barang']) ?></td>
</tr>

<tr>
    <td class="sub-no"></td>
    <td class="sub-label">Jenis Barang</td>
    <td class="sub-titik">:</td>
    <td><?= sanitize($b['jenis_barang']) ?></td>
</tr>

<tr>
    <td class="sub-no"></td>
    <td class="sub-label">Jumlah</td>
    <td class="sub-titik">:</td>
    <td><?= sanitize($b['jumlah_barang']) ?></td>
</tr>

<tr>
    <td class="sub-no"></td>
    <td class="sub-label">Satuan</td>
    <td class="sub-titik">:</td>
    <td><?= sanitize($b['satuan']) ?></td>
</tr>

<tr>
    <td class="sub-no"></td>
    <td class="sub-label">Jenis Uraian Barang</td>
    <td class="sub-titik">:</td>
    <td><?= sanitize($b['jenis_uraian_barang']) ?></td>
</tr>

</table>

<?php endforeach; ?>

<script>
window.print();
</script>

</body>
</html>