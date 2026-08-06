<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengurus_fakultas') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int) $_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $fakultas = mysqli_real_escape_string($conn, $_POST['fakultas']);
    $prodi = mysqli_real_escape_string($conn, $_POST['prodi']);
    $password = $_POST['password'];

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' AND id != $id");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error_msg'] = "Username sudah digunakan user lain!";
        header("Location: dashboard.php");
        exit;
    }

    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET nama='$nama', username='$username', password='$password_hash', role='$role', fakultas='$fakultas', prodi='$prodi' WHERE id=$id";
    } else {
        $query = "UPDATE users SET nama='$nama', username='$username', role='$role', fakultas='$fakultas', prodi='$prodi' WHERE id=$id";
    }

    if (mysqli_query($conn, $query)) {
        $_SESSION['success_msg'] = "Data user berhasil diperbarui!";
    } else {
        $_SESSION['error_msg'] = "Gagal update user: " . mysqli_error($conn);
    }
}

header("Location: dashboard.php");
exit;
?>
