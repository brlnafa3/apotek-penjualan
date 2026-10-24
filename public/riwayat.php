<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Transaksi - Apotek Sehat</title>

    <link rel="stylesheet" href="css/style.css">

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
        t.tanggal,
        t.total_harga,
        d.nama_obat,
        dt.jumlah,
        dt.harga,
        dt.subtotal
    FROM transaksi AS t
    LEFT JOIN detail_transaksi AS dt
        ON t.id_transaksi = dt.id_transaksi
    LEFT JOIN obat AS d
        ON dt.id_obat = d.id_obat
    ORDER BY t.tanggal DESC, t.id_transaksi DESC
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

        <section class="riwayat">
            <table>
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Tanggal</th>
                        <th>Nama Obat</th>
                        <th>Jumlah</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                        <th>Total Transaksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                #<?= (int) $row['id_transaksi'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['tanggal']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row['nama_obat'] ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= (int) ($row['jumlah'] ?? 0) ?>
                            </td>

                            <td>
                                Rp <?= number_format(
                                    (float) ($row['harga'] ?? 0),
                                    0, ',', '.'
                                ) ?>
                            </td>

                            <td>
                                Rp <?= number_format(
                                    (float) ($row['subtotal'] ?? 0),
                                    0, ',', '.'
                                ) ?>
                            </td>

                            <td class="total">
                                Rp <?= number_format(
                                    (float) $row['total_harga'],
                                    0, ',', '.'
                                ) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

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