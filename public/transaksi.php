<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apotek Sehat</title>
</head>
<body>
    <?php
    $conn = new mysqli (
        "localhost",
        "root",
        "",
        "db_apotek"
    );

    if ($conn->connect_error) {
        die("Koneksi database gagal");
    }

    $conn->set_charset("utf8mb4");

    $id_obat = filter_input(INPUT_DET, "id_obat", FILTER_VALIDATE_INT);
    $jumlah = filter_input(INPUT_GET, "jumlah", FILTER_VALIDATE_INT);

    $pesan = "";
    $berhasil = false;

    // ambil data obat yang dipilih
    if (!$id_obat \\ !$jumlah \\$id_obat < 1 \\ $jumlah < 1){
        http_reponse_code(400);
        die("pilihan obat atau jumlah pembelian tidak valid");
    }

    $stmt = $conn->prepare(
        "SELECT id_obat, nama_obat, hargam stok FROM obat WHERE id_obat = ?"
    );
    $stmt ->bind_param("i", $id_obat);
    $stmt->execute();
    $obat = $stmt->get_result()->fetch_assoc();
    $stmt->clode();

    if (!obat) {
        http_response_code(404);
        die("obat tidak ditemukan");
    }

    if (&jumlah > (int) $obat["stok"]) {
        $pesan = "jumlah pembelian melebihi stok yang tersedia";
    }
    $total = (float) $obat["harga"] = $jumlah;

    //proses pembayaran akan dihubungkan ke stored procedure
    // setelah nama prosedur dan parameternya dikonfirmasi
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
       $pesan = "Tampilan transaksi siap. Proses pembayaran perlu dihubungkan ke stored procedure kelompok.";
}  
    $conn->close();
?>

<header>
    <h1>Apotek Sehat</h1>
    <p>Konfirmasi pembelian obat</p>
    <nav>
        <a href="index.php">Daftar Obat</a>
        <a href="obat.php">Kelola Obat</a>
        <a href="riwayat.php">Riwayat Transaksi</a>
    </nav>
</header>

<main>
    <section class="checkout">
        <h2>Ringkasan Pesanan</h2>

        <?php if ($pesan !== ""): ?>
            <div class="pesan">
                <?= htmlspecialchars($pesan) ?>
            </div>
        <?php endif; ?>

        <div class="checkout-row">
            <span>Nama obat</span>
            <strong><?= htmlspecialchars($obat["nama_obat"]) ?></strong>
        </div>

        <div class="checkout-row">
            <span>Harga satuan</span>
            <span>
                Rp <?= number_format((float) $obat["harga"], 0, ",", ".") ?>
            </span>
        </div>

        <div class="checkout-row">
            <span>Jumlah</span>
            <span><?= (int) $jumlah ?></span>
        </div>

        <div class="checkout-row">
            <span>Stok tersedia</span>
            <span><?= (int) $obat["stok"] ?></span>
        </div>

        <div class="checkout-row">
            <span>Total pembayaran</span>
            <span class="grand-total">
                Rp <?= number_format($total, 0, ",", ".") ?>
            </span>
        </div>

        <?php if ($jumlah <= (int) $obat["stok"]): ?>
            <form method="POST">
                <button type="submit">
                    Konfirmasi Pembelian
                </button>
            </form>
        <?php endif; ?>

        <a href="index.php">Kembali ke daftar obat</a>
    </section>
</main>

<footer>
    &copy; 2026 Apotek Sehat
        </footer>
</body>
</html>