<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengurus_fakultas') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $stok = (int) $_POST['stok'];
    $stok_rusak = (int) $_POST['stok_rusak'];

    $query = "UPDATE items SET nama_barang = '$nama_barang', kategori = '$kategori', stok = '$stok', stok_rusak = '$stok_rusak' WHERE id = '$id'";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success_msg'] = "Data barang berhasil diperbarui!";
    } else {
        $_SESSION['error_msg'] = "Gagal update barang: " . mysqli_error($conn);
    }
}

header("Location: dashboard.php");
exit;
?>
