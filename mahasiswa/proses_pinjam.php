<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $item_id = $_POST['item_id'];
    $tgl_mulai = $_POST['tgl_mulai'];
    $jam_mulai = $_POST['jam_mulai'];
    $tgl_selesai = $_POST['tgl_selesai'];
    $jam_selesai = $_POST['jam_selesai'];
    $no_hp = mysqli_real_escape_string($conn, trim($_POST['no_hp'] ?? ''));
    $tujuan_raw = mysqli_real_escape_string($conn, trim($_POST['tujuan'] ?? ''));

    if (empty($item_id) || empty($tgl_mulai) || empty($jam_mulai) || empty($jam_selesai) || empty($no_hp) || empty($tujuan_raw)) {
        $_SESSION['error_msg'] = "Mohon lengkapi semua data termasuk Nomor HP/WhatsApp!";
        header("Location: dashboard.php");
        exit;
    }

    // Pastikan kolom no_hp ada di tabel loans (auto alter jika belum ada)
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM loans LIKE 'no_hp'");
    if (mysqli_num_rows($check_col) == 0) {
        @mysqli_query($conn, "ALTER TABLE loans ADD COLUMN no_hp VARCHAR(50) NULL AFTER jam_selesai");
    }

    // Gabungkan nomor kontak ke dalam tujuan agar terbaca otomatis di seluruh dashboard
    $tujuan = "[WA/HP: " . $no_hp . "] — " . $tujuan_raw;

    $query = "INSERT INTO loans (user_id, item_id, tanggal_pinjam, jam_mulai, jam_selesai, no_hp, tujuan, status) 
              VALUES ('$user_id', '$item_id', '$tgl_mulai', '$jam_mulai', '$jam_selesai', '$no_hp', '$tujuan', 'pending')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success_msg'] = "Pengajuan berhasil dikirim! Menunggu persetujuan dekan / kaprodi.";
        
        // Notify admin and pengurus_fakultas
        require_once '../config/fcm_helper.php';
        
        $q_mhs = mysqli_query($conn, "SELECT nama FROM users WHERE id = '$user_id'");
        $mhs = mysqli_fetch_assoc($q_mhs);
        $nama_mhs = $mhs['nama'];
        
        $q_item = mysqli_query($conn, "SELECT nama_barang FROM items WHERE id = '$item_id'");
        $item = mysqli_fetch_assoc($q_item);
        $nama_barang = $item['nama_barang'];

        $q_admins = mysqli_query($conn, "SELECT id FROM users WHERE role IN ('pengurus_fakultas')");
        while ($admin = mysqli_fetch_assoc($q_admins)) {
            sendFCMNotification($admin['id'], "🔔 Peminjaman Baru", "Mahasiswa '$nama_mhs' mengajukan peminjaman aset '$nama_barang'.", "info");
        }

    } else {
        $_SESSION['error_msg'] = "Gagal mengajukan: " . mysqli_error($conn);
    }

    header("Location: dashboard.php");
    exit;
}
?>
