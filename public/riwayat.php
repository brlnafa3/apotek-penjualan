<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apotek Sehat</title>

     <link rel="stylesheet" href="../style.css?v=12345">
</head>

<?php
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "db_apotek"
);

if ($conn->connect_error) {
    die("Koneksi database gagal");
}

$conn->set_charset("utf8mb4");

$sql = "
    SELECT
        t.id_transaksi,
        t.tanggal_transaksi AS tanggal,
        t.total_harga,
        o.nama_obat,
        d.jumlah,
        d.harga_satuan,
        (d.jumlah * d.harga_satuan) AS subtotal
    FROM transaksi AS t
    JOIN detail_transaksi AS d
        ON t.id_transaksi = d.id_transaksi
    JOIN obat AS o
        ON d.id_obat = o.id_obat
    ORDER BY t.id_transaksi DESC
";

$result = $conn->query($sql);

?>
<body>

<header>
    <h1>Apotek Sehat</h1>
    <p>Riwayat pembelian obat</p>

    <nav>
        <a href="index.php">Daftar Obat</a>
        <a href="obat.php">Kelola Obat</a>
        <a href="riwayat.php">Riwayat Transaksi</a>
    </nav>
</header>

<main>
    <section class="intro">
        <h2>Riwayat Transaksi</h2>
        <p>Daftar pembelian obat yang telah tercatat di database.</p>
    </section>

    <?php if ($result && $result->num_rows > 0): ?>

       <div class="table-container">
    <table class="riwayat-table">
        <thead>
            <tr>
                <th>ID Transaksi</th>
                <th>Tanggal</th>
                <th>Nama Obat</th>
                <th>Jumlah</th>
                <th>Harga Satuan</th>
                <th>Subtotal</th>
                <th>Total Transaksi</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?= (int) $row['id_transaksi'] ?></td>

                    <td>
                        <?= htmlspecialchars(
                            date('d-m-Y H:i', strtotime($row['tanggal']))
                        ) ?>
                    </td>

                    <td class="nama-obat">
                        <?= htmlspecialchars($row['nama_obat']) ?>
                    </td>

                    <td><?= (int) $row['jumlah'] ?></td>

                    <td>
                        Rp <?= number_format(
                            (float) $row['harga_satuan'],
                            0, ',', '.'
                        ) ?>
                    </td>

                    <td>
                        Rp <?= number_format(
                            (float) $row['subtotal'],
                            0, ',', '.'
                        ) ?>
                    </td>

                    <td class="total-harga">
                        Rp <?= number_format(
                            (float) $row['total_harga'],
                            0, ',', '.'
                        ) ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

    <?php else: ?>

        <div class="kosong">
            Belum ada riwayat transaksi pembelian.
        </div>

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