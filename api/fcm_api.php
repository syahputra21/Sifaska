<?php
/**
 * API Endpoint untuk Manajemen Firebase Cloud Messaging (FCM) & Notifikasi
 * SIFASKA Universitas Muhammadiyah Sorong
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/fcm_helper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized / Belum Login']);
    exit;
}

$userId = intval($_SESSION['user_id']);
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'save_token':
        $token = isset($_POST['token']) ? trim($_POST['token']) : '';
        $deviceType = isset($_POST['device_type']) ? trim($_POST['device_type']) : 'web';

        if (empty($token)) {
            echo json_encode(['status' => false, 'message' => 'Token FCM kosong']);
            exit;
        }

        // Cek apakah token sudah ada
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM fcm_tokens WHERE user_id = ? AND token = ?");
        mysqli_stmt_bind_param($checkStmt, "is", $userId, $token);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        $exists = mysqli_stmt_num_rows($checkStmt) > 0;
        mysqli_stmt_close($checkStmt);

        if (!$exists) {
            $insertStmt = mysqli_prepare($conn, "INSERT INTO fcm_tokens (user_id, token, device_type) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($insertStmt, "iss", $userId, $token, $deviceType);
            mysqli_stmt_execute($insertStmt);
            mysqli_stmt_close($insertStmt);
        }

        echo json_encode(['status' => true, 'message' => 'Token FCM berhasil didaftarkan']);
        break;

    case 'get_notifications':
        $query = "SELECT * FROM notifications WHERE user_id = $userId ORDER BY created_at DESC LIMIT 15";
        $result = mysqli_query($conn, $query);
        $notifications = [];
        $unreadCount = 0;

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $notifications[] = $row;
                if ($row['is_read'] == 0) {
                    $unreadCount++;
                }
            }
        }

        echo json_encode([
            'status' => true,
            'unread_count' => $unreadCount,
            'data' => $notifications
        ]);
        break;

    case 'mark_read':
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = $userId";
        mysqli_query($conn, $query);
        echo json_encode(['status' => true, 'message' => 'Semua notifikasi ditandai sudah dibaca']);
        break;

    case 'test_notification':
        $scenario = isset($_POST['scenario']) ? $_POST['scenario'] : 'approved';
        $title = "🔔 Notifikasi FCM SIFASKA";
        $message = "Uji coba pengiriman pesan real-time berhasil!";
        $type = "info";

        if ($scenario === 'approved') {
            $title = "🎉 Pengajuan Disetujui (FCM)";
            $message = "Permohonan peminjaman Proyektor Epson telah disetujui oleh Kaprodi Teknik Informatika.";
            $type = "success";
        } elseif ($scenario === 'rejected') {
            $title = "⚠️ Pengajuan Ditolak (FCM)";
            $message = "Mohon maaf, pengajuan Ruang Sidang ditolak karena jadwal bentrok dengan perkuliahan.";
            $type = "error";
        } elseif ($scenario === 'reminder') {
            $title = "⏰ Pengingat Pengembalian Aset";
            $message = "Waktu peminjaman Speaker JBL akan berakhir 30 menit lagi. Harap segera mengembalikan.";
            $type = "warning";
        }

        $res = sendFCMNotification($userId, $title, $message, $type, ['scenario' => $scenario]);

        echo json_encode([
            'status' => true,
            'message' => 'Notifikasi uji coba berhasil dipicu!',
            'fcm_result' => $res,
            'notification' => [
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'created_at' => date('H:i')
            ]
        ]);
        break;

    default:
        echo json_encode(['status' => false, 'message' => 'Action tidak dikenali']);
        break;
}
