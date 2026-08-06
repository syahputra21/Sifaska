<?php
session_start();
include '../config/koneksi.php';

if (isset($_POST['register'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $fakultas = mysqli_real_escape_string($conn, $_POST['fakultas']);
    $prodi = mysqli_real_escape_string($conn, $_POST['prodi']);

    $cek = mysqli_query($conn, "SELECT username FROM users WHERE username = '$nim'");
    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['error_msg'] = "NIM $nim sudah terdaftar!";
        header("Location: login.php?view=register");
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'mahasiswa';

    $query = "INSERT INTO users (nama, username, password, role, fakultas, prodi) VALUES ('$nama', '$nim', '$hashed_password', '$role', '$fakultas', '$prodi')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success_msg'] = "Akun berhasil dibuat! Silakan login.";
        header("Location: login.php");
        exit;
    } else {
        $_SESSION['error_msg'] = "Gagal mendaftar: " . mysqli_error($conn);
        header("Location: login.php?view=register");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
?>
