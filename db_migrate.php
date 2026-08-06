<?php
include 'config/koneksi.php';
$query = "ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) DEFAULT 'default.png'";
if(mysqli_query($conn, $query)) {
    echo "SUCCESS";
} else {
    echo "ERROR: " . mysqli_error($conn);
}
?>
