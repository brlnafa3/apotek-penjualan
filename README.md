# Apotek Sehat

Aplikasi Penjualan Obat berbasis web yang dikembangkan menggunakan PHP dan MariaDB untuk memenuhi tugas UTS Basis Data 2. Aplikasi ini dirancang untuk membantu pengelolaan persediaan obat dan proses transaksi penjualan di apotek.

Aplikasi dilengkapi dengan fitur pengelolaan data obat, pencarian obat, keranjang pembelian, serta pencatatan riwayat transaksi. Pemrosesan transaksi menggunakan **Stored Procedure** untuk melakukan validasi stok, menghitung total harga, menyimpan detail pembelian, dan memperbarui stok obat secara otomatis.

## Fitur Utama

- **Daftar Obat:** Menampilkan daftar obat beserta nama, satuan penjualan, harga, dan stok yang tersedia.
- **Pencarian Obat:** Memudahkan pengguna mencari obat berdasarkan nama.
- **Kelola Obat (CRUD):** Menambahkan, melihat, mengubah, dan menghapus data obat.
- **Keranjang Pembelian:** Memilih beberapa jenis obat sekaligus beserta jumlah pembeliannya.
- **Transaksi Penjualan:** Memproses pembelian menggunakan Stored Procedure dengan validasi stok dan perhitungan total harga.
- **Riwayat Transaksi:** Menampilkan catatan pembelian yang telah berhasil dilakukan, termasuk rincian obat, jumlah, harga satuan, subtotal, dan total transaksi.

## Teknologi yang Digunakan

- **Backend:** PHP Native
- **Database:** MariaDB 10.4.32
- **Database Connection:** MySQLi
- **Frontend:** HTML5 dan CSS3
- **Web Server:** Apache (XAMPP)
- **Database Management:** phpMyAdmin
- **Version Control:** Git dan GitHub

## Struktur Proyek

```text
apotek-penjualan/
│
├── database/
│   ├── schema.sql
│   ├── sample_data.sql
│   └── stored_procedures.sql
│
├── public/
│   ├── index.php
│   ├── obat.php
│   ├── riwayat.php
│   └── transaksi.php
│
├── .gitignore
├── README.md
└── style.css
```

## Panduan Instalasi dan Penggunaan

### 1. Persiapan Lingkungan

Pastikan perangkat telah memiliki perangkat lunak berikut:

- XAMPP (Apache, PHP, dan MariaDB).
- Web browser.
- Git (apabila menggunakan metode clone repository).

### 2. Mengunduh Proyek

Clone repository melalui Command Prompt atau Terminal:

```bash
cd C:\xampp\htdocs
git clone https://github.com/brlnafa3/apotek-penjualan.git
```

Pastikan folder proyek berada di dalam direktori `htdocs` XAMPP.

### 3. Menjalankan XAMPP

1. Buka XAMPP Control Panel.
2. Klik **Start** pada Apache.
3. Klik **Start** pada MySQL (MariaDB).
4. Pastikan kedua layanan berjalan dengan baik.

### 4. Membuat dan Mengisi Database

1. Buka browser dan akses `http://localhost/phpmyadmin`.
2. Pilih tab SQL.
3. Buka file `database/schema.sql` dari folder proyek.
4. Salin seluruh isi file, tempelkan ke kolom SQL phpMyAdmin, lalu klik **Go** untuk membuat database dan tabel.
5. Pilih database `db_apotek`.
6. Jalankan file `database/sample_data.sql` untuk memasukkan data awal obat.
7. Jalankan file `database/stored_procedures.sql` untuk membuat Stored Procedure `sp_proses_pembelian`.

Pastikan ketiga file dijalankan secara berurutan.

### 5. Konfigurasi Koneksi Database

Aplikasi menggunakan koneksi MariaDB melalui MySQLi dengan konfigurasi lokal berikut:

- Host: `localhost`
- Username: `root`
- Password: kosong (sesuai konfigurasi default XAMPP yang digunakan).

Apabila konfigurasi MariaDB pada perangkat berbeda, sesuaikan pengaturan koneksi database pada file PHP di folder `public`.

### 6. Menjalankan Aplikasi

Setelah database berhasil dibuat dan koneksi berjalan, buka browser dan akses:

```text
http://localhost/apotek-penjualan/public/
```

Aplikasi Apotek Sehat siap digunakan.

## Panduan Penggunaan Aplikasi

### A. Daftar Obat

1. Buka halaman utama aplikasi.
2. Lihat daftar obat yang tersedia beserta harga, satuan, dan stok.
3. Gunakan kolom pencarian apabila ingin menemukan obat tertentu.
4. Pilih obat yang akan dibeli.
5. Masukkan jumlah pembelian sesuai kebutuhan.
6. Klik tombol **Lanjut ke Pembayaran**.

### B. Kelola Obat

1. Buka menu **Kelola Obat**.
2. Untuk menambahkan obat, isi nama obat, satuan, harga, dan stok, kemudian klik **Tambah Obat**.
3. Untuk mengubah data, klik tombol **Edit**, lakukan perubahan, lalu klik **Simpan Perubahan**.
4. Untuk menghapus data, klik tombol **Hapus** dan konfirmasi tindakan.

Obat yang sudah tercatat dalam transaksi tidak dapat dihapus agar riwayat transaksi tetap terjaga.

### C. Transaksi Pembelian

1. Pilih satu atau beberapa obat dari halaman daftar obat.
2. Tentukan jumlah masing-masing obat.
3. Klik **Lanjut ke Pembayaran** untuk melihat ringkasan pembelian.
4. Periksa nama obat, harga, jumlah, subtotal, dan total pembelian.
5. Klik **Konfirmasi Pembelian**.
6. Sistem memproses transaksi menggunakan Stored Procedure.
7. Apabila berhasil, sistem menampilkan informasi pembelian dan nomor transaksi.

Jika stok tidak mencukupi atau data pembelian tidak valid, sistem akan menolak transaksi.

### D. Riwayat Transaksi

1. Buka menu **Riwayat Transaksi**.
2. Lihat daftar transaksi yang telah berhasil disimpan.
3. Periksa tanggal transaksi, nama obat, jumlah pembelian, harga satuan, subtotal, dan total transaksi.

## Informasi Pengembang

**Anggota 1:**  
Berliana Fara Nur Widayanto

**Anggota 2:**  
Novia Sulistyani

**Program Studi:** Teknik Informatika  
**Universitas:** Universitas Darussalam Gontor  
**Mata Kuliah:** Basis Data 2  
**Keperluan:** Tugas UTS

## Repository GitHub

Source code Aplikasi Apotek Sehat dapat diakses melalui repository berikut:

https://github.com/brlnafa3/apotek-penjualan
