<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apotek Sehat</title>
</head>
<body>
    <?php
    // koneksi ke database MySQL
    $conn = new mysqli("localhost", "root", "","apotek");

    //periksa koneksi
    if ($conn->connect_error) {
        die("koneksi gagal: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    //ambil data obat dari database
    $sql = "SELECT id_obat, nama_obat, harga, stok FROM obat ORDER BY nama_obat ASC";
    $result = $conn ->query($sql);
    ?>

    <header>
        <h1>Aotek Sehat</h1>
        <p>Kesehatan Anda, Prioritas kami.</p>

        <nav>
            <a href="index.php">Daftar obat</a>
            <a href="obat.php">Kelola Obat</a>
            <a href="riwayat.php">Riwayat Transaksi</a>
        </nav>
</header>

<main>
    <section class="intro">
        <h2>Daftar Obat</h2>
        <p>pilih obat yang dibutuhkan dan periksa stok yang tersedia sebelum melakukan pembelian</p>

    </section>

    <input
     type="text"
     id="cari"
     class="search"
     placeholder="cari nama obat..."
     oninput="cariobat()"
    >


    <section class="products" id="daftar-obat">

    <?php if ($result && $result->num_rows > 0); ?>

    <?php while ($obat = $result->fetch_assoc()): ?>

    <article class="card">
    <h3 class="nama-obat">
    <?= htmlspecialchars($obat['nama_obat']) ?>
    </h3>

    <p class="price">
        RP <?= number_format(
            $obat['harga'],0, ',', '.'
        ) ?>
    </p>

    <p class="stock">
     Stok tersedia: <?= (int) $obat['stok'] ?>
    </p>

    <?php if ((int) $obat['stok'] > 0): ?>

        <form action="transaksi.php" method="GET">
            <input
            type="hidden"
            name="id_obat"
            value="<?= (int) $obat['id_obat'] ?>"
        >

        <label>
            jumlah:
            <input
            type="number"
            name="jumlah"
            min="1"
            max="<?= (int) $obat['stok'] ?>"
            value="1"
            required
            >

        </label>

        <button type="submit">
            pilih obat
        </button>

    </form>

    <?php else: ?>

        <button type="button" disabled>
            stok habis
        </button>
        
         <?php endif; ?>
                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <p>Belum ada data obat yang tersedia.</p>

        <?php endif; ?>

    </section>
</main>

<footer>
    &copy; 2026 Apotek Sehat
</footer>

<script>
    function cariobat() {
        const kata = document.getElementBYID("cari")
        .value.tolowerCase();

        document.querySelectorAll(".card").forEach(function(card) {
        const nama = card.querySelector(".nama-obat")
            .textContent.toLowerCase();

        card.style.display = nama.includes(kata) ? "" : "none";
    });
}
</script>

</body>
</html>

<?php
$conn->close();
?>