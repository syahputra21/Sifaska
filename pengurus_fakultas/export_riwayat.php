<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengurus_fakultas') {
    header("Location: ../auth/login.php");
    exit;
}

$fakultas_admin = $_SESSION['fakultas'] ?? '';

$query = "SELECT loans.id, users.nama as peminjam, users.fakultas, users.prodi, items.nama_barang, items.kategori, loans.tanggal_pinjam, loans.jam_mulai, loans.jam_selesai, loans.status, loans.kondisi_kembali, loans.keluhan, loans.created_at
          FROM loans 
          JOIN users ON loans.user_id = users.id 
          JOIN items ON loans.item_id = items.id 
          WHERE users.fakultas = '$fakultas_admin'
          ORDER BY loans.created_at DESC";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Laporan Peminjaman Fakultas</title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 20px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0 0 5px 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 0; color: #666; font-size: 14px; }
        table { border-collapse: collapse; margin-top: 20px; width: 100%; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .status { font-weight: bold; }
        .status-dikembalikan { color: #4f46e5; }
        .status-ditolak { color: #dc2626; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            @page { size: landscape; margin: 1cm; }
        }
        .btn-print { padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 20px; font-weight: bold; }
        .keluhan { font-size: 10px; color: #dc2626; font-style: italic; margin-top: 4px; }
    </style>
</head>
<body onload="window.print()">

    <button class="btn-print no-print" onclick="window.print()">Cetak PDF</button>

    <div class="header">
        <h1>Laporan Riwayat Peminjaman Fakultas</h1>
        <p>Fakultas: <?= htmlspecialchars($fakultas_admin) ?> | Dicetak pada: <?= date('d M Y H:i') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Peminjam</th>
                <th>Program Studi</th>
                <th>Barang</th>
                <th>Waktu Penjadwalan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)): 
                $status_class = '';
                if($row['status'] == 'returned') $status_class = 'status-dikembalikan';
                if($row['status'] == 'rejected') $status_class = 'status-ditolak';
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($row['peminjam']) ?></strong></td>
                    <td><?= htmlspecialchars($row['prodi']) ?></td>
                    <td><?= htmlspecialchars($row['nama_barang']) ?> <br><small>(<?= htmlspecialchars($row['kategori']) ?>)</small></td>
                    <td>
                        <?= date('d M Y', strtotime($row['tanggal_pinjam'])) ?><br>
                        <?= date('H:i', strtotime($row['jam_mulai'])) ?> - <?= date('H:i', strtotime($row['jam_selesai'])) ?>
                    </td>
                    <td>
                        <span class="status <?= $status_class ?>"><?= strtoupper($row['status']) ?></span>
                        <?php if($row['kondisi_kembali'] == 'rusak'): ?>
                            <div class="keluhan">Kondisi: Rusak<br>Ket: <?= htmlspecialchars($row['keluhan']) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>
