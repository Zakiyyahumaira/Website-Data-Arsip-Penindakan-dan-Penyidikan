-- ============================================================
-- DATABASE: arsip_kantor (versi terbaru)
-- Jalankan file ini di phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS arsip_kantor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE arsip_kantor;

-- ============================================================
-- TABEL PENGGUNA
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nama       VARCHAR(100)  NOT NULL,
    username   VARCHAR(50)   UNIQUE NOT NULL,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('admin','staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABEL JENIS PELANGGARAN (pengganti kategori)
-- ============================================================
CREATE TABLE IF NOT EXISTS jenis_pelanggaran (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    nama_pelanggaran    VARCHAR(150) NOT NULL,
    deskripsi           TEXT
);

-- ============================================================
-- TABEL WILAYAH
-- ============================================================
CREATE TABLE IF NOT EXISTS wilayah (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nama_wilayah VARCHAR(100) NOT NULL
);

-- ============================================================
-- TABEL KECAMATAN
-- ============================================================
CREATE TABLE IF NOT EXISTS kecamatan (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    wilayah_id     INT NOT NULL,
    nama_kecamatan VARCHAR(100) NOT NULL,
    FOREIGN KEY (wilayah_id) REFERENCES wilayah(id) ON DELETE CASCADE
);

-- ============================================================
-- TABEL ARSIP UTAMA
-- ============================================================
CREATE TABLE IF NOT EXISTS arsip (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    no_surat             VARCHAR(50)  UNIQUE NOT NULL,
    nama_pegawai         VARCHAR(200) NOT NULL,
    deskripsi            TEXT,
    jenis_pelanggaran_id INT,
    wilayah_id           INT,
    kecamatan_id         INT,
    nama_tempat          VARCHAR(200),
    jumlah               DECIMAL(15,2),
    satuan               VARCHAR(50),
    tanggal_dokumen      DATE,
    file_path            VARCHAR(255),
    file_name            VARCHAR(255),
    diunggah_oleh        INT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jenis_pelanggaran_id) REFERENCES jenis_pelanggaran(id) ON DELETE SET NULL,
    FOREIGN KEY (wilayah_id)           REFERENCES wilayah(id)           ON DELETE SET NULL,
    FOREIGN KEY (kecamatan_id)         REFERENCES kecamatan(id)         ON DELETE SET NULL,
    FOREIGN KEY (diunggah_oleh)        REFERENCES users(id)             ON DELETE SET NULL
);

-- ============================================================
-- TABEL LOG AKTIVITAS
-- ============================================================
CREATE TABLE IF NOT EXISTS log_aktivitas (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT,
    aksi      VARCHAR(150),
    arsip_id  INT,
    waktu     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);


-- ============================================================
-- DATA AWAL: PENGGUNA
-- ============================================================
INSERT INTO users (nama, username, password, role) VALUES
('Administrator', 'admin', MD5('admin123'), 'admin'),
('Staff Arsip',   'staff', MD5('staff123'), 'staff');

-- ============================================================
-- DATA AWAL: JENIS PELANGGARAN
-- ============================================================
INSERT INTO jenis_pelanggaran (nama_pelanggaran, deskripsi) VALUES
('Pelanggaran Disiplin',       'Pelanggaran terhadap aturan disiplin pegawai'),
('Pelanggaran Administrasi',   'Pelanggaran dalam proses administrasi'),
('Pelanggaran Keuangan',       'Pelanggaran terkait pengelolaan keuangan'),
('Pelanggaran Etika',          'Pelanggaran etika dan norma kedinasan'),
('Pelanggaran Jabatan',        'Pelanggaran dalam pelaksanaan tugas jabatan'),
('Lain-lain',                  'Pelanggaran di luar kategori di atas');

-- ============================================================
-- DATA AWAL: WILAYAH
-- ============================================================
INSERT INTO wilayah (id, nama_wilayah) VALUES
(1, 'Banda Aceh'),
(2, 'Aceh Besar'),
(3, 'Pidie'),
(4, 'Pidie Jaya');

-- ============================================================
-- KECAMATAN: Banda Aceh (wilayah_id = 1)
-- ============================================================
INSERT INTO kecamatan (wilayah_id, nama_kecamatan) VALUES
(1, 'Baiturrahman'),
(1, 'Kuta Alam'),
(1, 'Meuraxa'),
(1, 'Syiah Kuala'),
(1, 'Lueng Bata'),
(1, 'Kuta Raja'),
(1, 'Banda Raya'),
(1, 'Jaya Baru'),
(1, 'Ulee Kareng');

-- ============================================================
-- KECAMATAN: Aceh Besar (wilayah_id = 2)
-- ============================================================
INSERT INTO kecamatan (wilayah_id, nama_kecamatan) VALUES
(2, 'Lhoknga'),
(2, 'Leupung'),
(2, 'Indrapuri'),
(2, 'Kuta Cot Glie'),
(2, 'Seulimeum'),
(2, 'Pulo Aceh'),
(2, 'Peukan Bada'),
(2, 'Baitussalam'),
(2, 'Darul Imarah'),
(2, 'Darul Kamal'),
(2, 'Darussalam'),
(2, 'Ingin Jaya'),
(2, 'Kota Jantho'),
(2, 'Kuta Malaka'),
(2, 'Lembah Seulawah'),
(2, 'Mesjid Raya'),
(2, 'Montasik'),
(2, 'Suka Makmur'),
(2, 'Simpang Tiga'),
(2, 'Sukamakmur');

-- ============================================================
-- KECAMATAN: Pidie (wilayah_id = 3)
-- ============================================================
INSERT INTO kecamatan (wilayah_id, nama_kecamatan) VALUES
(3, 'Batee'),
(3, 'Delima'),
(3, 'Geulumpang Tiga'),
(3, 'Geumpang'),
(3, 'Glumpang Baro'),
(3, 'Grong-Grong'),
(3, 'Indra Jaya'),
(3, 'Kembang Tanjong'),
(3, 'Keumala'),
(3, 'Kota Sigli'),
(3, 'Mane'),
(3, 'Mila'),
(3, 'Muara Tiga'),
(3, 'Mutiara'),
(3, 'Mutiara Timur'),
(3, 'Padang Tiji'),
(3, 'Peukan Baro'),
(3, 'Pidie'),
(3, 'Sakti'),
(3, 'Simpang Tiga'),
(3, 'Tangse'),
(3, 'Tiro'),
(3, 'Titeue');

-- ============================================================
-- KECAMATAN: Pidie Jaya (wilayah_id = 4)
-- ============================================================
INSERT INTO kecamatan (wilayah_id, nama_kecamatan) VALUES
(4, 'Bandar Baru'),
(4, 'Bandar Dua'),
(4, 'Jangka Buya'),
(4, 'Meurah Dua'),
(4, 'Meureudu'),
(4, 'Panteraja'),
(4, 'Trienggadeng'),
(4, 'Ulim');
