<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengurus_fakultas') {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "DELETE FROM items WHERE id = '$id'";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success_msg'] = "Barang berhasil dihapus!";
    } else {
        $_SESSION['error_msg'] = "Gagal menghapus barang: " . mysqli_error($conn);
    }
}

header("Location: dashboard.php");
exit;
?>
