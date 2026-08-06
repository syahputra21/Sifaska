<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kaprodi') {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status']; // 'approved_kaprodi' or 'rejected_kaprodi'

    if (in_array($status, ['approved_kaprodi', 'rejected_kaprodi'])) {
        require_once '../config/fcm_helper.php';
        
        $query_req = mysqli_query($conn, "SELECT item_requests.*, items.nama_barang FROM item_requests JOIN items ON item_requests.item_id = items.id WHERE item_requests.id = '$id'");
        $req = mysqli_fetch_assoc($query_req);
        $target_user_id = $req['user_id'];
        $nama_barang = $req['nama_barang'];

        $query = "UPDATE item_requests SET status = '$status' WHERE id = '$id'";
        
        if (mysqli_query($conn, $query)) {
            if ($status == 'approved_kaprodi') {
                $_SESSION['success_msg'] = "Pengajuan disetujui dan telah diteruskan ke Dekan.";
                sendFCMNotification($target_user_id, "✅ Pengajuan Diteruskan (Dekan)", "Pengajuan baru '$nama_barang' telah disetujui Kaprodi dan sedang menunggu persetujuan Dekan.", "success");
                
                // Kirim notif ke Dekan
                $q_dekan = mysqli_query($conn, "SELECT id FROM users WHERE role = 'dekan'");
                while ($dekan = mysqli_fetch_assoc($q_dekan)) {
                    sendFCMNotification($dekan['id'], "📝 Verifikasi Pengajuan Baru", "Kaprodi telah menyetujui pengajuan aset '$nama_barang'. Menunggu verifikasi Anda.", "info");
                }
            } else {
                $_SESSION['success_msg'] = "Pengajuan berhasil ditolak.";
                sendFCMNotification($target_user_id, "❌ Pengajuan Ditolak Kaprodi", "Pengajuan aset baru '$nama_barang' ditolak oleh Kaprodi.", "error");
            }
        } else {
            $_SESSION['error_msg'] = "Gagal memproses pengajuan: " . mysqli_error($conn);
        }
    }
}

header("Location: dashboard.php");
exit;
?>
