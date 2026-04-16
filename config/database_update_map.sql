-- ============================================================
-- UPDATE DATABASE UNTUK FITUR MAP/FOLDER
-- Jalankan file ini di phpMyAdmin setelah file database.sql
-- ============================================================

-- ============================================================
-- TABEL BARU: MAP/FOLDER (SIMPLE VERSION - NO HIERARCHY)
-- ============================================================
CREATE TABLE IF NOT EXISTS map (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama_map    VARCHAR(150) NOT NULL UNIQUE,
    deskripsi   TEXT,
    dibuat_oleh INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- MODIFIKASI TABEL ARSIP: TAMBAH KOLOM map_id
-- ============================================================
ALTER TABLE arsip ADD COLUMN map_id INT AFTER id COMMENT 'Referensi ke map/folder';
ALTER TABLE arsip ADD FOREIGN KEY (map_id) REFERENCES map(id) ON DELETE SET NULL;

-- ============================================================
-- DATA AWAL: CONTOH MAP (SIMPLE VERSION)
-- ============================================================
-- Tidak ada data awal. Map akan dibuat melalui aplikasi web.
-- Atau jika mau ada sample, uncomment di bawah:

/*
INSERT INTO map (nama_map, deskripsi, dibuat_oleh) VALUES
('Penindakan & Penyidikan', 'Map untuk dokumen penindakan dan penyidikan', 1),
('Dokumen Administrasi', 'Map untuk dokumen administrasi umum', 1),
('Laporan Periodik', 'Map untuk laporan mingguan dan bulanan', 1);
*/

-- ============================================================
-- DONE!
-- ============================================================
-- Setelah menjalankan SQL ini, lanjutkan dengan:
-- 1. Tambahkan function di config/functions.php
-- 2. Buat file-file baru (map.php, map_detail.php, map_tambah.php, etc)
-- 3. Update sidebar.php untuk tambah menu Map
