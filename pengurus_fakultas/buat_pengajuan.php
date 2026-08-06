<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengurus_fakultas') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = $_POST['item_id'];
    $qty = $_POST['qty'];
    $alasan = $_POST['alasan'];
    $user_id = $_SESSION['user_id'];

    $query = "INSERT INTO item_requests (item_id, user_id, qty, alasan, status) VALUES ('$item_id', '$user_id', '$qty', '$alasan', 'pending_kaprodi')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success_msg'] = "Pengajuan berhasil dikirim dan menunggu verifikasi Kaprodi.";
        
        require_once '../config/fcm_helper.php';
        
        $q_item = mysqli_query($conn, "SELECT nama_barang FROM items WHERE id = '$item_id'");
        $item = mysqli_fetch_assoc($q_item);
        $nama_barang = $item['nama_barang'];

        // Kirim notif ke semua kaprodi
        $q_kaprodi = mysqli_query($conn, "SELECT id FROM users WHERE role = 'kaprodi'");
        while ($kaprodi = mysqli_fetch_assoc($q_kaprodi)) {
            sendFCMNotification($kaprodi['id'], "📝 Pengajuan Barang Baru", "Staff Fakultas mengajukan penggantian/penambahan aset '$nama_barang'.", "info");
        }
        
    } else {
        $_SESSION['error_msg'] = "Gagal mengirim pengajuan: " . mysqli_error($conn);
    }
}

header("Location: dashboard.php");
exit;
?>
