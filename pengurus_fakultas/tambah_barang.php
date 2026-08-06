<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengurus_fakultas') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $stok = (int) $_POST['stok'];
    $stok_rusak = (int) $_POST['stok_rusak'];
    $fakultas = $_SESSION['fakultas'] ?? '';

    $query = "INSERT INTO items (nama_barang, kategori, stok, stok_rusak, fakultas) VALUES ('$nama_barang', '$kategori', '$stok', '$stok_rusak', '$fakultas')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success_msg'] = "Barang baru berhasil ditambahkan!";
    } else {
        $_SESSION['error_msg'] = "Gagal menambah barang: " . mysqli_error($conn);
    }
}

header("Location: dashboard.php");
exit;
?>
