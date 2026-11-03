
<?php
$conn = new mysqli("localhost", "root", "", "db_apotek");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
$pesan = "";

// Proses tambah atau hapus obat
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $aksi = isset($_POST["id_obat"])
        ? "hapus"
        : ($_POST["aksi"] ?? "");

    // PROSES TAMBAH OBAT
    if ($aksi === "tambah") {

        $nama = trim($_POST["nama_obat"] ?? "");
        $satuan = trim($_POST["satuan"] ?? "");
        $harga = filter_var($_POST["harga"] ?? null, FILTER_VALIDATE_FLOAT);
        $stok = filter_var($_POST["stok"] ?? null, FILTER_VALIDATE_INT);

        if ($nama === "" || $satuan === "" || $harga === false || $harga <= 0 || $stok === false || $stok < 0) {

            header("Location: index.php?pesan=input_salah");
            exit;
        }

        $stmt = $conn->prepare(
            "INSERT INTO obat (nama_obat, satuan, harga, stok)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param("sdi", $nama, $satuan, $harga, $stok);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?pesan=tambah");
            exit;
        }

        $stmt->close();
        header("Location: index.php?pesan=gagal");
        exit;
    }

    // PROSES HAPUS OBAT
    if ($aksi === "hapus") {

        $id = filter_var($_POST["id_obat"] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            header("Location: index.php?pesan=gagal");
            exit;
        }

        // Memeriksa apakah obat sudah digunakan dalam transaksi
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM detail_transaksi WHERE id_obat = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($jumlahTransaksi);
        $stmt->fetch();
        $stmt->close();

        if ($jumlahTransaksi > 0) {
            header("Location: index.php?pesan=terpakai");
            exit;
        }

        $stmt = $conn->prepare(
            "DELETE FROM obat WHERE id_obat = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $berhasil = $stmt->affected_rows > 0;
        $stmt->close();

        header("Location: index.php?pesan=" .
            ($berhasil ? "hapus" : "gagal"));
        exit;
    }
}

// Pesan hasil tindakan
$pesan = $_GET["pesan"] ?? "";

// Mengambil daftar obat dari database
$result = $conn->query(
    "SELECT id_obat, nama_obat, satuan, harga, stok
     FROM obat
     ORDER BY nama_obat ASC"
);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apotek Sehat</title>
    <link rel="stylesheet" href="../style.css?v=12345">
</head>

<body>

<header>
    <h1>Apotek Sehat</h1>
    <p>Kesehatan Anda, prioritas kami.</p>

    <nav>
        <a href="index.php">Daftar Obat</a>
        <a href="obat.php">Kelola Obat</a>
        <a href="riwayat.php">Riwayat Transaksi</a>
    </nav>
</header>

<main>

    <section class="intro">
        <h2>Daftar Obat</h2>
        <p>Pilih satu atau beberapa obat yang dibutuhkan.</p>
    </section>

    <!-- Pesan hasil tindakan -->
    <?php if ($pesan === "tambah"): ?>

        <p class="pesan sukses">Obat berhasil ditambahkan!</p>

    <?php elseif ($pesan === "hapus"): ?>

        <p class="pesan sukses">Obat berhasil dihapus!</p>

    <?php elseif ($pesan === "terpakai"): ?>

        <p class="pesan gagal">
            Obat sudah tercatat dalam transaksi sehingga tidak dapat dihapus.
        </p>

    <?php elseif ($pesan === "input_salah"): ?>

        <p class="pesan gagal">Periksa nama, harga, dan stok obat.</p>

    <?php elseif ($pesan === "gagal"): ?>

        <p class="pesan gagal">
            Operasi gagal. Periksa struktur database dan relasi tabel.
        </p>

    <?php endif; ?>


    <!-- FORM TAMBAH OBAT -->
    <section class="form-tambah">

        <h2>+ Tambah Obat Baru</h2>

        <form method="POST" action="index.php" class="form-obat">

            <input type="hidden" name="aksi" value="tambah">

            <label for="nama_obat">Nama obat</label>
            <input type="text" id="nama_obat" name="nama_obat"
                   placeholder="Contoh: Paracetamol 500 mg"
                   maxlength="255" required>

            <label for="satuan">Satuan</label>
            <input type="text" id="satuan" name="satuan"
                   placeholder="Contoh: strip, botol"
                   maxlength="20" required>

            <label for="harga">Harga (Rp)</label>
            <input type="number" id="harga" name="harga"
                   min="0.01" step="0.01" required>

            <label for="stok">Stok</label>
            <input type="number" id="stok" name="stok"
                   min="0" step="1" required>

            <button type="submit" class="btn-tambah">
                + Simpan Obat
            </button>

        </form>

    </section>

    
<!-- DAFTAR OBAT -->
<section class="products" id="daftar-obat">

    <?php if ($result && $result->num_rows > 0): ?>

        <?php while ($obat = $result->fetch_assoc()): ?>

            <article class="card">

                <h3 class="nama-obat">
                    <?= htmlspecialchars($obat["nama_obat"]) ?>
                </h3>

                <p style="color: #718096; font-size: 13px; margin-top: -10px; margin-bottom: 15px;">
                    Satuan: <?= htmlspecialchars($obat["satuan"]) ?>
                </p>

                <p class="price">
                    Rp <?= number_format(
                        (float)$obat["harga"], 0, ",", "."
                    ) ?>
                </p>

                <p class="stock">
                    Stok tersedia: <?= (int)$obat["stok"] ?>
                </p>

                <div class="aksi-obat">

                    <?php if ((int)$obat["stok"] > 0): ?>

                        <label>
                            <input
                                type="checkbox"
                                name="pilih[]"
                                value="<?= (int)$obat["id_obat"] ?>"
                                form="form-pembelian"
                            >
                            Pilih obat
                        </label>

                        <label>
                            Jumlah:
                            <input
                                type="number"
                                name="jumlah[<?= (int)$obat["id_obat"] ?>]"
                                min="1"
                                max="<?= (int)$obat["stok"] ?>"
                                value="1"
                                form="form-pembelian"
                            >
                        </label>

                    <?php else: ?>

                        <button type="button" disabled>
                            Stok Habis
                        </button>

                    <?php endif; ?>

                </div>

                <!-- Tombol hapus -->
                <form method="POST" action="index.php"
                      onsubmit="return confirm('Yakin ingin menghapus obat ini?')">

                    <button
                        type="submit"
                        name="id_obat"
                        value="<?= (int)$obat["id_obat"] ?>"
                        class="btn-hapus"
                    >
                        🗑 Hapus Obat
                    </button>

                </form>

            </article>

        <?php endwhile; ?>

    <?php else: ?>

        <p>Belum ada data obat yang tersedia.</p>

    <?php endif; ?>

</section>


<!-- FORM CHECKOUT -->
</section>

<form action="transaksi.php" method="POST" id="form-pembelian">

    <input type="hidden" name="aksi" value="checkout">

   <button type="submit" name="aksi" value="checkout"
        class="btn-checkout">
    Lanjut ke Pembayaran
</button>

</form>

</main>

<footer>&copy; 2026 Apotek Sehat</footer>

</body>
</html>

<?php $conn->close(); ?>
