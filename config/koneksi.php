<?php
/**
 * Konfigurasi Koneksi Database SIFASKA UNAMIN Sorong
 * DETEKSI OTOMATIS: Lokal (XAMPP / localhost) vs Online (TiDB Cloud / Hosting)
 */

$server_name = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

if ($server_name === 'localhost' || strpos($server_name, '127.0.0.1') !== false || strpos($server_name, '192.168.') === 0 || php_sapi_name() === 'cli') {
    // =====================================================================
    // 1. KREDENSIAL LOKAL (XAMPP / Komputer Pribadi) - AKTIF DI LOCALHOST
    // =====================================================================
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "sifaska_pwm";
    $port = 3306;
} else {
    // =====================================================================
    // 2. KREDENSIAL ONLINE (TiDB Cloud / InfinityFree / Vercel)
    // OTOMATIS AKTIF JIKA DIAKSES DARI DOMAIN ONLINE / CLOUD
    // =====================================================================
    $host = getenv('DB_HOST') ?: "gateway01.ap-southeast-1.prod.aws.tidbcloud.com";
    $user = getenv('DB_USER') ?: "8FTqSgRhjRIuKBv.root";
    $pass = getenv('DB_PASS') ?: "9sA2m7pXMSAZJHKL";
    $db   = getenv('DB_NAME') ?: "test";
    $port = intval(getenv('DB_PORT') ?: 4000);
}

$conn = mysqli_init();

// Jika terhubung ke TiDB Cloud (port 4000), aktifkan SSL secara otomatis
if ($port == 4000 || strpos($host, 'tidbcloud.com') !== false) {
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);
} else {
    mysqli_real_connect($conn, $host, $user, $pass, $db, $port);
}

if (!$conn) {
    die("Koneksi Database SIFASKA Gagal: " . mysqli_connect_error());
}
?>
