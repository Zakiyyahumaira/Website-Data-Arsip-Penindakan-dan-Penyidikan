-- Tambahkan tabel petugas dan relasi petugas pada tabel arsip
ALTER TABLE arsip
    ADD COLUMN petugas_1_id INT NULL AFTER nama_pegawai,
    ADD COLUMN petugas_2_id INT NULL AFTER petugas_1_id;

CREATE TABLE IF NOT EXISTS petugas (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nama       VARCHAR(150) NOT NULL,
    nip        VARCHAR(50)  NOT NULL,
    pangkat    VARCHAR(100),
    jabatan    VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

ALTER TABLE arsip
    ADD CONSTRAINT fk_arsip_petugas1 FOREIGN KEY (petugas_1_id) REFERENCES petugas(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_arsip_petugas2 FOREIGN KEY (petugas_2_id) REFERENCES petugas(id) ON DELETE SET NULL;

INSERT INTO petugas (nama, nip, pangkat, jabatan, created_by) VALUES
('Petugas 1', '1987654321', 'Penata Muda', 'Penyidik', NULL),
('Petugas 2', '1987654322', 'Penata Muda Tingkat I', 'Penyidik', NULL);
