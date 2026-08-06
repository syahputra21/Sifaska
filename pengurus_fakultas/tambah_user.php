<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengurus_fakultas') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $fakultas = mysqli_real_escape_string($conn, $_POST['fakultas']);
    $prodi = mysqli_real_escape_string($conn, $_POST['prodi']);

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error_msg'] = "Username sudah digunakan!";
        header("Location: dashboard.php");
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO users (nama, username, password, role, fakultas, prodi) 
              VALUES ('$nama', '$username', '$password_hash', '$role', '$fakultas', '$prodi')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success_msg'] = "User baru berhasil ditambahkan!";
    } else {
        $_SESSION['error_msg'] = "Gagal menambah user: " . mysqli_error($conn);
    }
}

header("Location: dashboard.php");
exit;
?>
