<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apotek Sehat</title>
</head>
<body>
    <?php
    $conn = new mysqli("localhost", "root", "", "apotek_db");
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        die("Koneksi database gagal.");
    }

    $pesan = "";

    // tambah obat

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
    }

    $pesan = "Data obat tidak valid.";


    //edit obat
if (isset($_POST["edit"])) {
    $id = (int) $_POST["id_obat"];
    $nama = trim($_POST["nama_obat"]);
    $harga = (float) $_POST["harga"];
    $stok = (int) $_POST["stok"];

    if ($id > 0 && $nama !== "" && $harga >= 0 && $stok >= 0) {
        $stmt = $conn->prepare(
            "UPDATE obat
            SET nama_obat = ?, harga = ?, stok = ?
            WHERE id_obat = ?"
        );

        $stmt->bind_param("sdii", $nama, $harga, $stok, $id);
        $stmt->exceute();
        $stmt->close();

        header("Location: obat.php?pesan=edit");
        exit;
    }
    $pesan = "Data obat tidak Valid";
    }

    // hapus obat 
    if (isset($_POST["hapus"])) {
        $id = (int) $_POST["id_obat"];

        $stmt = $conn->prepare(
            "DELET FROM obat WHERE id_obat"
        );

        try{
            $stmt->execute();
            $pesan = $stmt->affected_rows > 0
            ? "Obat berhasil dihapus"
            : "Data obat tidak ditemukan"
        } catch (mysqli_sql_exception $e) {
            $pesan = "Obat tidak dapat dihapus karena mungkin sudah digunakan dalam transaksi.";
        }

        $stmt->close();
    }

    // pesan hasil
    if (isset($_GET["pesan"])) {
        if ($_GET["pesan"] === "tambah") {
            $pesan = "obat berhasil ditambahkan.";
        } elseif ($_GET["pesan"] === "edit") {
            $pesan = "data oabt berhasil diperbarui";
        }
    }

    // ambil data obat
    $result = $conn->query(
        "SELECT id_obat, nama_obat, harga, stok
        FROM obat ORDER BY id_obat DESC"
    );

    $editObat = null;

if (isset($_GET["edit"])) {
    $idEdit = (int) $_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT id_obat, nama_obat, harga, stok
         FROM obat WHERE id_obat = ?"
    );
    $stmt->bind_param("i", $idEdit);
    $stmt->execute();

    $editObat = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
</body>
<header>
    <h1>Apotek Sehat</h1>
    <p>kelola persediaan obat</p>

    <nav>
        <a href="index.php">daftar obat</a>
        <a href="obat.php">kelola obat</a>
        <a href="riwayat.php">riwayat transaksi</a>
</nav>
</header>

</html>