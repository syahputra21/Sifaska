<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dekan') {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status']; // 'approved_dekan' or 'rejected_dekan'

    if (in_array($status, ['approved_dekan', 'rejected_dekan'])) {
        require_once '../config/fcm_helper.php';
        
        $query_req = mysqli_query($conn, "SELECT item_requests.*, items.nama_barang FROM item_requests JOIN items ON item_requests.item_id = items.id WHERE item_requests.id = '$id'");
        $req = mysqli_fetch_assoc($query_req);
        $target_user_id = $req['user_id'];
        $nama_barang = $req['nama_barang'];

        $query = "UPDATE item_requests SET status = '$status' WHERE id = '$id'";
        
        if (mysqli_query($conn, $query)) {
            if ($status == 'approved_dekan') {
                $_SESSION['success_msg'] = "Penggantian barang disetujui. Staff dapat memprosesnya.";
                sendFCMNotification($target_user_id, "🎉 Pengajuan Disetujui Dekan", "Selamat! Pengajuan penggantian aset '$nama_barang' telah disetujui oleh Dekan dan akan segera diproses.", "success");
            } else {
                $_SESSION['success_msg'] = "Penggantian barang ditolak.";
                sendFCMNotification($target_user_id, "❌ Pengajuan Ditolak Dekan", "Mohon maaf, pengajuan penggantian aset '$nama_barang' ditolak oleh Dekan.", "error");
            }
        } else {
            $_SESSION['error_msg'] = "Gagal memproses pengajuan: " . mysqli_error($conn);
        }
    }
}

header("Location: dashboard.php");
exit;
?>
