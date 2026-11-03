
<?php
$conn = new mysqli("localhost", "root", "", "db_apotek");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
$pesan = "";

    // PROSES HAPUS OBAT
    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $aksi = isset($_POST["id_obat"]) ? "hapus" : ($_POST["aksi"] ?? "");

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

// Menangkap kata kunci pencarian jika ada
$keyword = $_GET['cari'] ?? '';

if ($keyword !== '') {
    // Jika ada pencarian, gunakan klausa LIKE
    $stmt = $conn->prepare(
        "SELECT id_obat, nama_obat, satuan, harga, stok
         FROM obat
         WHERE nama_obat LIKE ?
         ORDER BY nama_obat ASC"
    );
    $like_keyword = "%" . $keyword . "%";
    $stmt->bind_param("s", $like_keyword);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Jika tidak ada pencarian, tampilkan semua
    $result = $conn->query(
        "SELECT id_obat, nama_obat, satuan, harga, stok
         FROM obat
         ORDER BY nama_obat ASC"
    );
}
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


 <!-- FORM PENCARIAN OBAT -->
    <form method="GET" action="index.php" style="display: flex; gap: 10px; margin-bottom: 24px;">
        <input 
            type="text" 
            name="cari" 
            class="search" 
            placeholder="Cari nama obat..." 
            value="<?= htmlspecialchars($keyword) ?>" 
            style="margin-bottom: 0;"
        >
        <button type="submit" style="white-space: nowrap;">🔍 Cari</button>
        
        <?php if ($keyword !== ''): ?>
            <a href="index.php" style="padding: 10px 15px; color: #c45d66; background: #fff0f0; border-radius: 10px; text-decoration: none; font-weight: bold; display: flex; align-items: center;">Reset</a>
        <?php endif; ?>
    </form>


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
<form action="transaksi.php" method="POST" id="form-pembelian" style="margin-top: 45px; text-align: right;">

    <input type="hidden" name="aksi" value="checkout">

    <button type="submit" name="aksi" value="checkout" class="btn-checkout" style="padding: 12px 28px; font-size: 16px; box-shadow: 0 4px 12px rgba(120, 171, 224, 0.3);">
        Lanjut ke Pembayaran ➔
    </button>

</form>

</main>

<footer>&copy; 2026 Apotek Sehat</footer>

</body>
</html>

<?php $conn->close(); ?>
