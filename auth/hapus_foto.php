<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Determine redirect loopbacks
$redirect_url = '../mahasiswa/dashboard.php';
if ($_SESSION['role'] == 'dekan') {
    $redirect_url = '../dekan/dashboard.php';
} else if ($_SESSION['role'] == 'pengurus_fakultas') {
    $redirect_url = '../pengurus_fakultas/dashboard.php';
} else if ($_SESSION['role'] == 'kaprodi') {
    $redirect_url = '../kaprodi/dashboard.php';
}

$query_old = "SELECT foto_profil FROM users WHERE id = '$user_id'";
$res_old = mysqli_query($conn, $query_old);
$row_old = mysqli_fetch_assoc($res_old);
$upload_dir = '../uploads/profiles/';

if ($row_old['foto_profil'] && $row_old['foto_profil'] != 'default.png' && file_exists($upload_dir . $row_old['foto_profil'])) {
    unlink($upload_dir . $row_old['foto_profil']);
}

$update_query = "UPDATE users SET foto_profil = 'default.png' WHERE id = '$user_id'";
if (mysqli_query($conn, $update_query)) {
    $_SESSION['foto_profil'] = 'default.png';
    $_SESSION['success_msg'] = "Foto profil berhasil dihapus!";
} else {
    $_SESSION['error_msg'] = "Gagal menghapus foto profil.";
}

header("Location: " . $redirect_url);
exit;
?>
