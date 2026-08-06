<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $kondisi = $_POST['kondisi_kembali'] ?? 'baik';
    $keluhan = $_POST['keluhan'] ?? '';
    
    // Sanitize
    $kondisi = mysqli_real_escape_string($conn, $kondisi);
    $keluhan = mysqli_real_escape_string($conn, $keluhan);
    
    $user_id = $_SESSION['user_id'];
    $check = mysqli_query($conn, "SELECT * FROM loans WHERE id = '$id' AND user_id = '$user_id' AND status = 'approved'");

    if (mysqli_num_rows($check) > 0) {
        $query = "UPDATE loans SET status = 'return_request', kondisi_kembali = '$kondisi', keluhan = '$keluhan' WHERE id = '$id'";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success_msg'] = "Permintaan pengembalian dikirim. Menunggu verifikasi petugas.";
            
            require_once '../config/fcm_helper.php';
            
            $q_detail = mysqli_query($conn, "SELECT items.nama_barang, users.nama FROM loans JOIN items ON loans.item_id = items.id JOIN users ON loans.user_id = users.id WHERE loans.id = '$id'");
            $detail = mysqli_fetch_assoc($q_detail);
            $nama_barang = $detail['nama_barang'] ?? 'Aset';
            $nama_mhs = $detail['nama'] ?? 'Mahasiswa';
            
            $q_admins = mysqli_query($conn, "SELECT id FROM users WHERE role = 'pengurus_fakultas'");
            while ($admin = mysqli_fetch_assoc($q_admins)) {
                sendFCMNotification($admin['id'], "📦 Pengembalian Aset", "Mahasiswa '$nama_mhs' mengajukan pengembalian aset '$nama_barang'.", "info");
            }
        } else {
            $_SESSION['error_msg'] = "Gagal memproses: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error_msg'] = "Peminjaman tidak valid atau status bukan dipinjam.";
    }
}

header("Location: dashboard.php");
exit;
?>
