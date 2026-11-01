
<?php
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli("localhost", "root", "", "db_apotek");
$conn->set_charset("utf8mb4");

$error = "";
$sukses = false;
$hasilTransaksi = null;

// Membuat token keamanan untuk konfirmasi
if (empty($_SESSION["token_checkout"])) {
    $_SESSION["token_checkout"] = bin2hex(random_bytes(32));
}

// ==========================================
// 1. MENERIMA PILIHAN OBAT DARI INDEX.PHP
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["aksi"] ?? "") === "checkout") {

    $pilihan = $_POST["pilih"] ?? [];
    $jumlah = $_POST["jumlah"] ?? [];

    $keranjang = [];

    if (!is_array($pilihan) || count($pilihan) === 0) {
        $error = "Silakan pilih minimal satu obat.";
    } else {

        $stmt = $conn->prepare(
            "SELECT nama_obat, harga, stok
             FROM obat WHERE id_obat = ?"
        );

        foreach (array_unique($pilihan) as $idInput) {

            $id = filter_var($idInput, FILTER_VALIDATE_INT);

            if ($id === false || $id < 1) {
                $error = "ID obat tidak valid.";
                break;
            }

            $qty = filter_var(
                $jumlah[$id] ?? null,
                FILTER_VALIDATE_INT
            );

            if ($qty === false || $qty < 1) {
                $error = "Jumlah pembelian tidak valid.";
                break;
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();

            $data = $stmt->get_result()->fetch_assoc();

            if (!$data) {
                $error = "Obat tidak ditemukan.";
                break;
            }

            if ($qty > (int)$data["stok"]) {
                $error = "Stok " . $data["nama_obat"]
                       . " tidak mencukupi.";
                break;
            }

            $keranjang[] = [
                "id_obat" => $id,
                "nama_obat" => $data["nama_obat"],
                "harga" => (float)$data["harga"],
                "jumlah" => $qty,
                "subtotal" => (float)$data["harga"] * $qty
            ];
        }

        $stmt->close();

        if ($error === "") {
            $_SESSION["keranjang_checkout"] = $keranjang;
        }
    }
}

// ==========================================
// 2. PROSES KONFIRMASI PEMBELIAN
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["aksi"] ?? "") === "konfirmasi") {

    $token = $_POST["token"] ?? "";

    if (!hash_equals($_SESSION["token_checkout"], $token)) {
        $error = "Permintaan tidak valid. Silakan ulangi checkout.";
    } elseif (empty($_SESSION["keranjang_checkout"])) {
        $error = "Keranjang pembelian kosong.";
    } else {

        try {

            $daftarObat = [];

            foreach ($_SESSION["keranjang_checkout"] as $item) {
                $daftarObat[] = [
                    "id_obat" => (int)$item["id_obat"],
                    "jumlah" => (int)$item["jumlah"]
                ];
            }

            // Mengubah daftar obat menjadi JSON
            $json = json_encode(
                $daftarObat,
                JSON_UNESCAPED_UNICODE
            );

            if ($json === false) {
                throw new Exception("Gagal membuat data JSON.");
            }

            // Memanggil Stored Procedure
            $stmt = $conn->prepare(
                "CALL sp_proses_pembelian_multi(?)"
            );

            $stmt->bind_param("s", $json);
            $stmt->execute();

            // Mengambil hasil dari Stored Procedure
            $result = $stmt->get_result();

            if ($result) {
                $hasilTransaksi = $result->fetch_assoc();
                $result->free();
            }

            // Membersihkan hasil tambahan dari CALL
            while ($stmt->more_results() && $stmt->next_result()) {
                $tambahan = $stmt->get_result();

                if ($tambahan) {
                    $tambahan->free();
                }
            }

            $stmt->close();

            if (!$hasilTransaksi ||
                !isset($hasilTransaksi["id_transaksi"])) {
                throw new Exception(
                    "Hasil transaksi tidak ditemukan."
                );
            }

            // Simpan data keranjang untuk bukti pembelian
$keranjangSelesai = $_SESSION["keranjang_checkout"];

$sukses = true;

unset($_SESSION["keranjang_checkout"]);

        } catch (Throwable $e) {

            error_log("Checkout error: " . $e->getMessage());

            $error = "Pembelian gagal: " . $e->getMessage();
        }
    }
}

// ==========================================
// 3. MENGAMBIL DATA KERANJANG
// ==========================================
$keranjang = $sukses
    ? $keranjangSelesai
    : ($_SESSION["keranjang_checkout"] ?? []);

$total = 0;

foreach ($keranjang as $item) {
    $total += $item["subtotal"];
}

// Fungsi format rupiah
function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ",", ".");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - Apotek Sehat</title>

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
        <h2>
            <?= $sukses ? "Pembelian Berhasil" : "Checkout Pembelian" ?>
        </h2>

        <p>
            <?= $sukses
                ? "Terima kasih telah berbelanja di Apotek Sehat."
                : "Periksa kembali obat yang akan dibeli." ?>
        </p>
    </section>

    <?php if ($error !== ""): ?>

        <p class="pesan gagal">
            <?= htmlspecialchars($error) ?>
        </p>

    <?php endif; ?>


    <!-- ==================================
         4. TAMPILAN JIKA PEMBELIAN BERHASIL
         ================================== -->

    <?php if ($sukses): ?>

        <section class="form-tambah">

            <h2>✓ Pembelian Berhasil!</h2>

            <p>
                Nomor Transaksi:
                <strong>
                    #<?= (int)$hasilTransaksi["id_transaksi"] ?>
                </strong>
            </p>

            <p>
                Tanggal:
                <?= date("d-m-Y H:i:s") ?>
            </p>

            <hr>

            <h3>Ringkasan Pembelian</h3>

            <?php foreach ($keranjang as $item): ?>

                <p>
                    <?= htmlspecialchars($item["nama_obat"]) ?>
                    (<?= (int)$item["jumlah"] ?>)
                    - <?= rupiah($item["subtotal"]) ?>
                </p>

            <?php endforeach; ?>

            <h3>
                Total Pembelian:
                <?= rupiah($hasilTransaksi["total_harga"]) ?>
            </h3>

            <p class="pesan sukses">
                Transaksi berhasil disimpan dan stok obat telah diperbarui.
            </p>

            <a href="index.php" class="btn-checkout">
                Kembali ke Daftar Obat
            </a>

            <a href="riwayat.php" class="btn-tambah">
                Lihat Riwayat Transaksi
            </a>

        </section>


    <!-- ==================================
         5. TAMPILAN KERANJANG CHECKOUT
         ================================== -->

    <?php elseif (!empty($keranjang)): ?>

        <section class="form-tambah">

            <h2>Daftar Obat yang Dipilih</h2>

            <div style="table-wrapper">
                
                <table class="checkout-table">

                <colgroup>
                <col style="width: 7%">
                <col style="width: 38%">
                <col style="width: 20%">
                <col style="width: 12%">
                <col style="width: 23%">
    </colgroup>

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Obat</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($keranjang as $i => $item): ?>

                            <tr>
                                <td><?= $i + 1 ?></td>

                                <td>
                                    <?= htmlspecialchars($item["nama_obat"]) ?>
                                </td>

                                <td>
                                    <?= rupiah($item["harga"]) ?>
                                </td>

                                <td>
                                    <?= (int)$item["jumlah"] ?>
                                </td>

                                <td>
                                    <?= rupiah($item["subtotal"]) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

            <hr>

            <div class="checkout-area">

                <h3>Total Pembelian</h3>

                <h2>
                    <?= rupiah($total) ?>
                </h2>

                <p>
                    Pastikan jumlah dan obat yang dipilih sudah benar.
                </p>

            </div>

            <!-- Form konfirmasi -->
            <form action="transaksi.php" method="POST">

                <input type="hidden"
                       name="aksi"
                       value="konfirmasi">

                <input type="hidden"
                       name="token"
                       value="<?= htmlspecialchars(
                           $_SESSION["token_checkout"]
                       ) ?>">

                <button type="submit"
                        class="btn-checkout"
                        onclick="return confirm('Yakin ingin mengonfirmasi pembelian ini?')">

                    Konfirmasi Pembelian

                </button>

            </form>

            <br>

            <a href="index.php" class="btn-tambah">
                ← Kembali Memilih Obat
            </a>

        </section>


    <?php else: ?>

        <section class="form-tambah">

            <h2>Belum Ada Pembelian</h2>

            <p>Silakan pilih obat terlebih dahulu.</p>

            <a href="index.php" class="btn-checkout">
                Kembali ke Daftar Obat
            </a>

        </section>

    <?php endif; ?>

</main>

<footer>
    &copy; 2026 Apotek Sehat
</footer>

</body>
</html>

<?php
$conn->close();
?>
