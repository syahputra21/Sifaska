<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Determine redirect loopbacks
    $redirect_url = '../mahasiswa/dashboard.php';
    if ($_SESSION['role'] == 'dekan') {
        $redirect_url = '../dekan/dashboard.php';
    } else if ($_SESSION['role'] == 'pengurus_fakultas') {
        $redirect_url = '../pengurus_fakultas/dashboard.php';
    }

    if ($password_baru !== $konfirmasi_password) {
        $_SESSION['error_msg'] = "Konfirmasi password baru tidak cocok!";
        header("Location: " . $redirect_url);
        exit;
    }

    $query = "SELECT password FROM users WHERE id = '$user_id'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    if (password_verify($password_lama, $row['password'])) {
        // Hash and Update
        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = '$password_hash' WHERE id = '$user_id'";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success_msg'] = "Password Anda berhasil diperbarui!";
        } else {
            $_SESSION['error_msg'] = "Gagal memperbarui password: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error_msg'] = "Password lama yang Anda masukkan salah!";
    }
    
    header("Location: " . $redirect_url);
    exit;
}
?>
