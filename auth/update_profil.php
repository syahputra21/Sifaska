<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    
    // Determine redirect loopbacks
    $redirect_url = '../mahasiswa/dashboard.php';
    if ($_SESSION['role'] == 'dekan') {
        $redirect_url = '../dekan/dashboard.php';
    } else if ($_SESSION['role'] == 'pengurus_fakultas') {
        $redirect_url = '../pengurus_fakultas/dashboard.php';
    }

    $updates = [];
    $errors = [];

    // Handle Profile Picture Upload
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['foto_profil']['type'], $allowed_types)) {
            $errors[] = "Format foto harus JPG atau PNG.";
        } elseif ($_FILES['foto_profil']['size'] > $max_size) {
            $errors[] = "Ukuran foto maksimal 2MB.";
        } else {
            $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
            $filename = "user_" . $user_id . "_" . time() . "." . $ext;
            
            $upload_dir = '../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_file)) {
                // Delete old photo if it's not default
                $query_old = "SELECT foto_profil FROM users WHERE id = '$user_id'";
                $res_old = mysqli_query($conn, $query_old);
                $row_old = mysqli_fetch_assoc($res_old);
                if ($row_old['foto_profil'] && $row_old['foto_profil'] != 'default.png' && file_exists($upload_dir . $row_old['foto_profil'])) {
                    unlink($upload_dir . $row_old['foto_profil']);
                }
                
                $updates[] = "foto_profil = '$filename'";
                $_SESSION['foto_profil'] = $filename; // Update session
            } else {
                $errors[] = "Gagal mengunggah foto profil.";
            }
        }
    } elseif (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] == '1') {
        // Handle Delete Photo
        $query_old = "SELECT foto_profil FROM users WHERE id = '$user_id'";
        $res_old = mysqli_query($conn, $query_old);
        $row_old = mysqli_fetch_assoc($res_old);
        $upload_dir = '../uploads/profiles/';
        if ($row_old['foto_profil'] && $row_old['foto_profil'] != 'default.png' && file_exists($upload_dir . $row_old['foto_profil'])) {
            unlink($upload_dir . $row_old['foto_profil']);
        }
        $updates[] = "foto_profil = 'default.png'";
        $_SESSION['foto_profil'] = 'default.png';
    }

    // Handle Password Change
    if (!empty($password_baru)) {
        if ($password_baru !== $konfirmasi_password) {
            $errors[] = "Konfirmasi password baru tidak cocok!";
        } else {
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $updates[] = "password = '$password_hash'";
        }
    }

    if (count($errors) > 0) {
        $_SESSION['error_msg'] = implode("<br>", $errors);
    } elseif (count($updates) > 0) {
        $set_query = implode(", ", $updates);
        $update_query = "UPDATE users SET $set_query WHERE id = '$user_id'";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success_msg'] = "Profil berhasil diperbarui!";
        } else {
            $_SESSION['error_msg'] = "Gagal memperbarui profil: " . mysqli_error($conn);
        }
    } else {
        // No changes submitted
        $_SESSION['success_msg'] = "Tidak ada perubahan yang disimpan.";
    }
    
    header("Location: " . $redirect_url);
    exit;
}
?>
