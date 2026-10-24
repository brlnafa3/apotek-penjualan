CREATE DATABASE db_apotek
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE db_apotek;

CREATE TABLE obat(
    id_obat INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_obat VARCHAR(100) NOT NULL,
    satuan VARCHAR(20) NOT NULL,
    harga DECIMAL(10,2) UNSIGNED NOT NULL,
    stok INT UNSIGNED NOT NULL DEFAULT 0
);

CREATE TABLE transaksi(
    id_transaksi INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tanggal_transaksi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_harga DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0
);

CREATE TABLE detail_transaksi(
    id_detail INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT UNSIGNED NOT NULL,
    id_obat INT UNSIGNED NOT NULL,
    jumlah INT UNSIGNED NOT NULL,
    harga_satuan DECIMAL(10,2) UNSIGNED NOT NULL,

    CONSTRAINT fk_detail_transaksi_transaksi
    FOREIGN KEY (id_transaksi) REFERENCES transaksi(id_transaksi)
    ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_detail_transaksi_obat
    FOREIGN KEY (id_obat) REFERENCES obat(id_obat)
    ON UPDATE CASCADE ON DELETE RESTRICT
);