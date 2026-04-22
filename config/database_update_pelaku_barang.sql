-- ============================================================
-- DATABASE UPDATE: PELAKU & BARANG HASIL PENINDAKAN
-- Jalankan file ini di phpMyAdmin setelah backup database
-- ============================================================

-- ============================================================
-- TABEL PELAKU (SUSPECT)
-- ============================================================
CREATE TABLE IF NOT EXISTS pelaku (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    arsip_id        INT NOT NULL,
    nama            VARCHAR(150) NOT NULL,
    identitas       VARCHAR(50)  NOT NULL,
    no_identitas    VARCHAR(50)  NOT NULL,
    jenis_kelamin   ENUM('Laki-laki', 'Perempuan') NOT NULL,
    alamat          TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (arsip_id) REFERENCES arsip(id) ON DELETE CASCADE
);

-- ============================================================
-- TABEL BARANG HASIL PENINDAKAN
-- ============================================================
CREATE TABLE IF NOT EXISTS barang_hasil_penindakan (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    arsip_id            INT NOT NULL,
    nama_barang         VARCHAR(200) NOT NULL,
    jenis_barang        VARCHAR(100) NOT NULL,
    jumlah_barang       DECIMAL(15,2) NOT NULL,
    satuan              VARCHAR(50) NOT NULL,
    jenis_uraian_barang TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (arsip_id) REFERENCES arsip(id) ON DELETE CASCADE
);

-- ============================================================
-- UPDATE TABEL ARSIP: TAMBAHKAN WAKTU PENINDAKAN
-- ============================================================
ALTER TABLE arsip ADD COLUMN IF NOT EXISTS waktu_penindakan TIME DEFAULT NULL
COMMENT 'Waktu penindakan dalam format HH:MM (WIB)';

-- ============================================================
-- CREATE INDEX UNTUK PERFORMA
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_pelaku_arsip ON pelaku(arsip_id);
CREATE INDEX IF NOT EXISTS idx_barang_arsip ON barang_hasil_penindakan(arsip_id);
