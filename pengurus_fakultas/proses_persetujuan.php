<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengurus_fakultas') {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $status = mysqli_real_escape_string($conn, $_GET['status']);

    mysqli_begin_transaction($conn);

    require_once '../config/fcm_helper.php';

    try {
        $query_loan = mysqli_query($conn, "SELECT loans.*, items.nama_barang FROM loans JOIN items ON loans.item_id = items.id WHERE loans.id = '$id'");
        $loan = mysqli_fetch_assoc($query_loan);

        if (!$loan) {
            throw new Exception("Data peminjaman tidak ditemukan.");
        }

        $item_id = $loan['item_id'];
        $current_status = $loan['status'];
        $target_user_id = $loan['user_id'];
        $nama_barang = $loan['nama_barang'];

        if ($status == 'approved') {
            $query_item = mysqli_query($conn, "SELECT stok FROM items WHERE id = '$item_id' FOR UPDATE");
            $item = mysqli_fetch_assoc($query_item);

            if ($item['stok'] > 0) {
                mysqli_query($conn, "UPDATE items SET stok = stok - 1 WHERE id = '$item_id'");
                mysqli_query($conn, "UPDATE loans SET status = 'approved' WHERE id = '$id'");
                $_SESSION['success_msg'] = "Peminjaman disetujui, stok berkurang.";
                sendFCMNotification($target_user_id, "🎉 Peminjaman Disetujui", "Peminjaman aset '$nama_barang' telah disetujui oleh Staff Fakultas.", "success");
            } else {
                $_SESSION['error_msg'] = "Gagal setuju: Stok habis!";
            }

        } elseif ($status == 'returned') {
            if ($current_status == 'approved' || $current_status == 'return_request') {
                mysqli_query($conn, "UPDATE items SET stok = stok + 1 WHERE id = '$item_id'");
                mysqli_query($conn, "UPDATE loans SET status = 'returned' WHERE id = '$id'");
                $_SESSION['success_msg'] = "Barang dikembalikan, stok bertambah.";
                sendFCMNotification($target_user_id, "✅ Barang Dikembalikan", "Pengembalian aset '$nama_barang' telah dikonfirmasi.", "success");
            }

        } elseif ($status == 'rejected') {
            if ($current_status == 'approved') {
                mysqli_query($conn, "UPDATE items SET stok = stok + 1 WHERE id = '$item_id'");
            }
            mysqli_query($conn, "UPDATE loans SET status = 'rejected' WHERE id = '$id'");
            $_SESSION['success_msg'] = "Peminjaman ditolak.";
            sendFCMNotification($target_user_id, "⚠️ Peminjaman Ditolak", "Mohon maaf, peminjaman aset '$nama_barang' ditolak oleh Staff Fakultas.", "error");
        }

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_msg'] = "Gagal memproses: " . $e->getMessage();
    }
}

header("Location: dashboard.php");
exit;
?>
