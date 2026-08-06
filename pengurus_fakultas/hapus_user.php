<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengurus_fakultas') {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    if ($id == $_SESSION['user_id']) {
        $_SESSION['error_msg'] = "Tidak dapat menghapus akun sendiri!";
        header("Location: dashboard.php");
        exit;
    }

    $query = "DELETE FROM users WHERE id = $id";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success_msg'] = "User berhasil dihapus!";
    } else {
        $_SESSION['error_msg'] = "Gagal menghapus user: " . mysqli_error($conn);
    }
}

header("Location: dashboard.php");
exit;
?>
