<?php
/**
 * Modul Firebase Cloud Messaging (FCM) Helper - SIFASKA UNAMIN Sorong
 * Digunakan untuk mengirimkan notifikasi real-time ke perangkat pengguna (Web / Mobile).
 * Mengacu pada Bab 2.3.12 Seminar Proposal Skripsi Brayen Syahputra (NIM 202255202089).
 */

require_once __DIR__ . '/koneksi.php';

// Konfigurasi Kunci Server FCM (Ganti dengan Server Key Firebase dari Google Cloud Console)
if (!defined('FCM_SERVER_KEY')) {
    define('FCM_SERVER_KEY', 'DEMO_FCM_SERVER_KEY_UNAMIN_SORONG');
}

/**
 * Mengirimkan notifikasi push via FCM dan menyimpannya di database MySQL.
 * 
 * @param int    $userId  ID pengguna penerima notifikasi
 * @param string $title   Judul notifikasi
 * @param string $message Isi pesan notifikasi
 * @param string $type    Kategori notifikasi (info, success, warning, error)
 * @param array  $data    Data ekstra untuk deep link / action
 * @return array Hasil pengiriman FCM (status, response, notification_id)
 */
function sendFCMNotification($userId, $title, $message, $type = 'info', $data = []) {
    global $conn;

    // 1. Simpan riwayat notifikasi ke tabel `notifications` MySQL
    $notificationId = 0;
    if ($conn) {
        $stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isss", $userId, $title, $message, $type);
            mysqli_stmt_execute($stmt);
            $notificationId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
        }
    }

    // 2. Ambil token FCM pengguna dari tabel `fcm_tokens`
    $tokens = [];
    if ($conn) {
        $query = "SELECT token FROM fcm_tokens WHERE user_id = " . intval($userId);
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                if (!empty($row['token'])) {
                    $tokens[] = $row['token'];
                }
            }
        }
    }

    // 3. Jika berjalan dalam mode DEMO / LOKAL (tanpa Firebase Server Key produksi),
    // kembalikan status simulasi sukses untuk diproses secara lokal oleh Web Service Worker.
    if (FCM_SERVER_KEY === 'DEMO_FCM_SERVER_KEY_UNAMIN_SORONG' || empty($tokens)) {
        return [
            'status' => true,
            'mode' => 'SIMULATED_LOCAL_FCM',
            'notification_id' => $notificationId,
            'message' => 'Notifikasi FCM sukses disimpan dan dipicu melalui simulasi push lokal.',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    // 4. Kirim request cURL ke Firebase Cloud Messaging Server HTTP API
    $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    $payload = [
        'registration_ids' => $tokens,
        'notification' => [
            'title' => $title,
            'body' => $message,
            'icon' => '/sifaska-pwm/assets/icon-fcm.png',
            'click_action' => 'http://localhost/sifaska-pwm/'
        ],
        'data' => array_merge([
            'notification_id' => $notificationId,
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s')
        ], $data)
    ];

    $headers = [
        'Authorization: key=' . FCM_SERVER_KEY,
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fcmUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [
            'status' => false,
            'mode' => 'LIVE_FCM_ERROR',
            'notification_id' => $notificationId,
            'error' => $error
        ];
    }

    return [
        'status' => true,
        'mode' => 'LIVE_FCM',
        'notification_id' => $notificationId,
        'response' => json_decode($response, true)
    ];
}
