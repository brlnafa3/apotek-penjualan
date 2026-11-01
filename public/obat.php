
!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Obat - Apotek Sehat</title>

    <link rel="stylesheet" href="../style.css?v=12345">
</head>

<body>

<?php
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Koneksi database
try {
    $conn = new mysqli("localhost", "root", "", "db_apotek");
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Koneksi database gagal.");
}

$pesan = "";

// PROSES TAMBAH OBAT
if (isset($_POST["tambah"])) {

    $nama = trim($_POST["nama_obat"] ?? "");
    $harga = (float) ($_POST["harga"] ?? 0);
    $stok = (int) ($_POST["stok"] ?? 0);

    if ($nama !== "" && $harga >= 0 && $stok >= 0) {

        $stmt = $conn->prepare(
            "INSERT INTO obat (nama_obat, harga, stok)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param("sdi", $nama, $harga, $stok);
        $stmt->execute();
        $stmt->close();

        header("Location: obat.php?pesan=tambah");
        exit;

    } else {
        $pesan = "Data obat tidak valid.";
    }
}

// PROSES EDIT OBAT
if (isset($_POST["edit"])) {

    $id = (int) ($_POST["id_obat"] ?? 0);
    $nama = trim($_POST["nama_obat"] ?? "");
    $harga = (float) ($_POST["harga"] ?? 0);
    $stok = (int) ($_POST["stok"] ?? 0);

    if ($id > 0 && $nama !== "" && $harga >= 0 && $stok >= 0) {

        $stmt = $conn->prepare(
            "UPDATE obat
             SET nama_obat = ?, harga = ?, stok = ?
             WHERE id_obat = ?"
        );

        $stmt->bind_param("sdii", $nama, $harga, $stok, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: obat.php?pesan=edit");
        exit;

    } else {
        $pesan = "Data obat tidak valid.";
    }
}

// PROSES HAPUS OBAT
if (isset($_POST["hapus"])) {

    $id = (int) ($_POST["id_obat"] ?? 0);

    if ($id > 0) {
        try {
            $stmt = $conn->prepare(
                "DELETE FROM obat WHERE id_obat = ?"
            );

            $stmt->bind_param("i", $id);
            $stmt->execute();

            $pesan = $stmt->affected_rows > 0
                ? "Obat berhasil dihapus."
                : "Data obat tidak ditemukan.";

            $stmt->close();

        } catch (mysqli_sql_exception $e) {
            $pesan = "Obat tidak dapat dihapus karena sudah digunakan dalam transaksi.";
        }
    }
}

// PESAN HASIL
if (isset($_GET["pesan"])) {
    if ($_GET["pesan"] === "tambah") {
        $pesan = "Obat berhasil ditambahkan.";
    } elseif ($_GET["pesan"] === "edit") {
        $pesan = "Data obat berhasil diperbarui.";
    }
}

// AMBIL DATA OBAT
$result = $conn->query(
    "SELECT id_obat, nama_obat, harga, stok
     FROM obat
     ORDER BY id_obat DESC"
);

// AMBIL DATA UNTUK EDIT
$editObat = null;

if (isset($_GET["edit"])) {

    $idEdit = (int) $_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT id_obat, nama_obat, harga, stok
         FROM obat
         WHERE id_obat = ?"
    );

    $stmt->bind_param("i", $idEdit);
    $stmt->execute();

    $editObat = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Format rupiah
function rupiah($nilai) {
    return "Rp " . number_format((float)$nilai, 0, ",", ".");
}

function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, "UTF-8");
}
?>

<header>
    <h1>Apotek Sehat</h1>
    <p>Kelola persediaan obat</p>

    <nav>
        <a href="index.php">Daftar Obat</a>
        <a href="obat.php">Kelola Obat</a>
        <a href="riwayat.php">Riwayat Transaksi</a>
    </nav>
</header>

<main class="container">

    <h2>Manajemen Data Obat</h2>

    <?php if ($pesan !== ""): ?>
        <p class="pesan-transaksi">
            <?= e($pesan) ?>
        </p>
    <?php endif; ?>

    <!-- FORM TAMBAH / EDIT OBAT -->

    <section class="form-obat">

        <h3>
            <?= $editObat ? "Edit Data Obat" : "Tambah Obat Baru" ?>
        </h3>

        <form method="POST" action="obat.php">

            <?php if ($editObat): ?>

                <input
                    type="hidden"
                    name="id_obat"
                    value="<?= e($editObat["id_obat"]) ?>"
                >

            <?php endif; ?>

            <label>Nama Obat</label>
            <input
                type="text"
                name="nama_obat"
                placeholder="Masukkan nama obat"
                value="<?= e($editObat["nama_obat"] ?? "") ?>"
                required
            >

            <label>Harga Obat</label>
            <input
                type="number"
                name="harga"
                min="0"
                step="0.01"
                placeholder="Masukkan harga"
                value="<?= e($editObat["harga"] ?? "") ?>"
                required
            >

            <label>Stok Obat</label>
            <input
                type="number"
                name="stok"
                min="0"
                placeholder="Masukkan stok"
                value="<?= e($editObat["stok"] ?? "") ?>"
                required
            >

            <?php if ($editObat): ?>

                <button type="submit" name="edit">
                    Simpan Perubahan
                </button>

                <a href="obat.php">Batal Edit</a>

            <?php else: ?>

                <button type="submit" name="tambah">
                    Tambah Obat
                </button>

            <?php endif; ?>

        </form>

    </section>

    <!-- TABEL DAFTAR OBAT -->

    <section class="daftar-obat">

        <h3>Daftar Persediaan Obat</h3>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Obat</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>
                            <td><?= e($row["id_obat"]) ?></td>

                            <td><?= e($row["nama_obat"]) ?></td>

                            <td><?= rupiah($row["harga"]) ?></td>

                            <td><?= e($row["stok"]) ?></td>

                            

<td>
    <div class="aksi-tabel">

        <a
            href="obat.php?edit=<?= e($row['id_obat']) ?>"
            class="btn-edit-tabel"
        >
            Edit
        </a>

        <form
            method="POST"
            action="obat.php"
            class="form-hapus-tabel"
            onsubmit="return confirm('Yakin ingin menghapus obat ini?')"
        >
            <input
                type="hidden"
                name="id_obat"
                value="<?= e($row['id_obat']) ?>"
            >

            <button type="submit" name="hapus" class="btn-hapus-tabel">
                Hapus
            </button>
        </form>

    </div>
</td>


                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">
                            Belum ada data obat.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>

    </section>

</main>

<footer>
    &copy; 2026 Apotek Sehat
</footer>

</body>
</html>

<?php
$conn->close();
?>
