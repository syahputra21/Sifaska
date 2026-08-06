<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_user = $_SESSION['nama'];
$fakultas_user = $_SESSION['fakultas'];
$prodi_user = $_SESSION['prodi'];

date_default_timezone_set('Asia/Jakarta');
$jam = date('H');
if ($jam < 11) $sapaan = 'Selamat Pagi';
elseif ($jam < 15) $sapaan = 'Selamat Siang';
elseif ($jam < 18) $sapaan = 'Selamat Sore';
else $sapaan = 'Selamat Malam';
$nama_depan = explode(' ', $nama_user)[0];

$query_items = "SELECT * FROM items ORDER BY nama_barang ASC";
$result_items = mysqli_query($conn, $query_items);

$query_history = "SELECT loans.*, items.nama_barang, items.kategori 
                  FROM loans 
                  JOIN items ON loans.item_id = items.id 
                  WHERE loans.user_id = '$user_id' 
                  ORDER BY loans.created_at DESC";
$result_history = mysqli_query($conn, $query_history);

$count_active = 0;
$count_finished = 0;
$history_data = [];
while ($row = mysqli_fetch_assoc($result_history)) {
    $history_data[] = $row;
    if ($row['status'] == 'approved' || $row['status'] == 'pending' || $row['status'] == 'return_request') {
        $count_active++;
    } else if ($row['status'] == 'returned' || $row['status'] == 'rejected') {
        $count_finished++;
    }
}

function getFakultasBadgeStyle($fakultas) {
    $fak = strtolower(trim($fakultas ?? ''));
    if (strpos($fak, 'fisip') !== false || strpos($fak, 'sosial') !== false || strpos($fak, 'politik') !== false) {
        return 'background-color: #f3901b1a; color: #c86e06; border: 1px solid #f3901b50;'; // Fisip #f3901b
    } elseif (strpos($fak, 'hukum') !== false) {
        return 'background-color: #d000001a; color: #d00000; border: 1px solid #d0000050;'; // Hukum #d00000
    } elseif (strpos($fak, 'teknik') !== false) {
        return 'background-color: #fdc40126; color: #9c7800; border: 1px solid #fdc40170;'; // Teknik #fdc401
    } elseif (strpos($fak, 'pertanian') !== false) {
        return 'background-color: #1f710e1a; color: #1f710e; border: 1px solid #1f710e50;'; // Pertanian #1f710e
    } elseif (strpos($fak, 'perikanan') !== false) {
        return 'background-color: #29a6c020; color: #13778c; border: 1px solid #29a6c060;'; // Perikanan #29a6c0
    } elseif (strpos($fak, 'fkip') !== false || strpos($fak, 'keguruan') !== false || strpos($fak, 'pendidikan') !== false) {
        return 'background-color: #0248821a; color: #024882; border: 1px solid #02488250;'; // Fkip #024882
    } elseif (strpos($fak, 'ekonomi') !== false) {
        return 'background-color: #e1b74026; color: #9a7818; border: 1px solid #e1b74070;'; // Ekonomi #e1b740
    } else {
        return 'background-color: #3b82f61a; color: #2563eb; border: 1px solid #3b82f650;'; // Default blue
    }
}
?>

<?php include '../includes/header.php'; ?>

    <div id="toast-container" class="fixed top-6 right-6 z-[110] flex flex-col gap-3 pointer-events-none">
        <?php if(isset($_SESSION['success_msg'])): ?>
            <div class="bg-white border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-xl shadow-soft flex items-center gap-3 min-w-[300px] animate-slide-in pointer-events-auto">
                <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                <span class="text-sm font-bold"><?= $_SESSION['success_msg'] ?></span>
            </div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
        <?php if(isset($_SESSION['error_msg'])): ?>
            <div class="bg-white border-l-4 border-red-500 text-red-700 p-4 rounded-xl shadow-soft flex items-center gap-3 min-w-[300px] animate-slide-in pointer-events-auto">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <span class="text-sm font-bold"><?= $_SESSION['error_msg'] ?></span>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>
    </div>

    <div id="loan-modal" class="fixed inset-0 z-[90] flex items-center justify-center bg-luxury-secondary/70 backdrop-blur-md hidden px-4 overflow-y-auto py-6">
        <div class="bg-white rounded-[2.5rem] shadow-2xl animate-popup max-w-xl w-full relative overflow-hidden ring-1 ring-slate-900/10">
            <!-- Header Modal -->
            <div class="bg-gradient-to-tr from-luxury-secondary via-[#1E293B] to-luxury-primary px-8 py-7 text-white relative overflow-hidden">
                <div class="absolute -top-12 -right-12 w-48 h-48 bg-luxury-primary/30 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-indigo-500/20 rounded-full blur-xl pointer-events-none"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center justify-between gap-4 mb-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold tracking-wider uppercase bg-white/10 backdrop-blur-md border border-white/15 text-luxury-accent">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                            Fasilitas Akademik UNAMIN
                        </span>
                        <div class="bg-white/10 backdrop-blur-md px-3.5 py-1.5 rounded-full border border-white/20 flex items-center gap-2 text-luxury-accent shadow-inner">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            <span id="real-time-clock" class="text-xs font-mono font-bold tracking-wider">00:00:00</span>
                        </div>
                    </div>

                    <h2 class="text-2xl font-display font-bold tracking-tight">Formulir Peminjaman</h2>
                    <p class="text-xs md:text-sm text-white/75 mt-1 font-light">Lengkapi jadwal serta tujuan peminjaman sarana akademik fakultas</p>

                    <div class="mt-4 pt-4 border-t border-white/15 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 flex items-center justify-center text-white shadow-sm">
                                <i data-lucide="box" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-[11px] text-white/60 uppercase font-bold tracking-wider">Item Yang Dipinjam:</p>
                                <p id="loan-item-name" class="text-base font-bold text-white tracking-wide">Nama Barang</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeLoanModal()" class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all cursor-pointer" title="Tutup Modal">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Form Body -->
            <form id="form-peminjaman" action="proses_pinjam.php" method="POST" class="p-8 space-y-6 bg-white">
                <input type="hidden" name="item_id" id="loan-item-id">
                
                <!-- Jadwal Peminjaman (Sangat Simpel & Bersih) -->
                <div class="space-y-3">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 ml-1">
                        <i data-lucide="calendar" class="w-4 h-4 text-luxury-primary"></i>
                        Jadwal Peminjaman <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Mulai -->
                        <div class="space-y-1.5">
                            <span class="text-xs font-semibold text-slate-600 ml-1">Waktu Mulai:</span>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" name="tgl_mulai" id="loan-date-start" required class="input-luxury w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium rounded-xl border border-slate-200 focus:border-luxury-primary transition-all cursor-pointer">
                                <input type="time" name="jam_mulai" id="loan-time-start" required class="input-luxury w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium rounded-xl border border-slate-200 focus:border-luxury-primary transition-all cursor-pointer">
                            </div>
                        </div>
                        <!-- Selesai -->
                        <div class="space-y-1.5">
                            <span class="text-xs font-semibold text-slate-600 ml-1">Waktu Selesai:</span>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" name="tgl_selesai" id="loan-date-end" required class="input-luxury w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium rounded-xl border border-slate-200 focus:border-luxury-primary transition-all cursor-pointer">
                                <input type="time" name="jam_selesai" id="loan-time-end" required class="input-luxury w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium rounded-xl border border-slate-200 focus:border-luxury-primary transition-all cursor-pointer">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nomor HP / WhatsApp Yang Dapat Dihubungi (*Wajib Ada) -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 ml-1">
                        <i data-lucide="phone" class="w-4 h-4 text-luxury-primary"></i>
                        Nomor HP / WhatsApp yang Dapat Dihubungi <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="no_hp" id="loan-phone" required placeholder="Contoh: 081234567890 / 08xxxxxxxxxx" class="input-luxury w-full px-4 py-3 text-sm font-medium rounded-xl border border-slate-200 focus:border-luxury-primary transition-all">
                </div>

                <!-- Tujuan Peminjaman (*Wajib Ada) -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 ml-1">
                        <i data-lucide="file-text" class="w-4 h-4 text-luxury-primary"></i>
                        Tujuan & Keperluan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="tujuan" required rows="3" class="input-luxury w-full px-4 py-3 text-sm font-medium rounded-xl border border-slate-200 focus:border-luxury-primary transition-all resize-none" placeholder="Jelaskan secara singkat keperluan peminjaman fasilitas..."></textarea>
                </div>

                <!-- Tombol Action Footer -->
                <div class="flex items-center gap-4 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeLoanModal()" class="px-6 py-4 bg-slate-100 hover:bg-slate-200/80 text-slate-600 font-bold rounded-2xl transition-all duration-300 cursor-pointer text-sm">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 py-4 bg-gradient-to-r from-luxury-primary to-luxury-secondary hover:from-luxury-secondary hover:to-luxury-dark text-white font-bold rounded-2xl shadow-xl shadow-luxury-primary/25 hover:shadow-2xl hover:shadow-luxury-primary/40 transform active:scale-[0.98] transition-all duration-300 flex items-center justify-center gap-2.5 cursor-pointer text-sm group">
                        <span class="tracking-wide">Kirim Pengajuan Sekarang</span>
                        <i data-lucide="send-horizontal" class="w-4 h-4"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="confirm-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-luxury-secondary/80 backdrop-blur-sm hidden animate-fade-in p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-sm w-full p-8 text-center animate-slide-up">
            <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600">
                <i data-lucide="help-circle" class="w-8 h-8"></i>
            </div>
            <h3 class="text-xl font-display font-bold text-luxury-secondary mb-2" id="confirm-title">Konfirmasi</h3>
            <p class="text-slate-500 mb-8" id="confirm-message">Apakah Anda yakin ingin melakukan tindakan ini?</p>
            <div class="flex gap-3">
                <button onclick="closeConfirmModal()" class="flex-1 py-3 bg-slate-100 text-slate-500 font-bold rounded-xl hover:bg-slate-200 transition-colors">Batal</button>
                <a id="confirm-btn-yes" href="#" class="flex-1 py-3 bg-luxury-primary text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:bg-luxury-secondary transition-all">Ya, Lanjutkan</a>
            </div>
        </div>
    </div>

    <div id="password-modal" class="fixed inset-0 z-[90] flex items-center justify-center bg-luxury-secondary/60 backdrop-blur-sm hidden px-4 overflow-y-auto py-4">
        <div class="bg-white rounded-[2rem] shadow-2xl animate-popup max-w-md w-full relative overflow-hidden ring-1 ring-black/5">
            <div class="bg-luxury-primary px-8 py-6 flex justify-between items-center text-white">
                <h2 class="text-xl font-display font-bold">Pengaturan Profil</h2>
                <button onclick="closePasswordModal()" class="text-white/70 hover:text-white cursor-pointer"><i data-lucide="x" class="w-6 h-6"></i></button>
            </div>
            <form action="../auth/update_profil.php" method="POST" enctype="multipart/form-data" class="p-8 space-y-5 bg-white">
                <div class="space-y-1">
                    <div class="flex items-center justify-between ml-1 mb-1">
                        <label class="text-xs font-bold text-luxury-primary uppercase">Foto Profil</label>
                        <?php if(isset($_SESSION['foto_profil']) && $_SESSION['foto_profil'] != 'default.png'): ?>
                        <a href="../auth/hapus_foto.php" onclick="return confirm('Hapus foto profil Anda?');" class="text-[10px] font-bold text-red-500 hover:text-red-600 bg-red-50 px-2 py-1 rounded transition-colors inline-block">
                            HAPUS FOTO
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="relative group">
                        <i data-lucide="image" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-luxury-primary transition-colors"></i>
                        <input type="file" name="foto_profil" accept="image/jpeg, image/png" class="input-luxury w-full py-2 pl-10 pr-4 rounded-xl text-sm">
                    </div>
                    <p class="text-[10px] text-slate-400 ml-1 mt-1">*Maks. 2MB (JPG/PNG)</p>
                </div>
                
                <hr class="border-slate-100 my-4">
                <p class="text-xs font-bold text-slate-400 uppercase mb-4">Ganti Password</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Password Baru</label>
                        <div class="relative group">
                            <i data-lucide="shield-check" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-luxury-primary transition-colors"></i>
                            <input type="password" name="password_baru" class="input-luxury w-full py-3 pl-10 pr-4 rounded-xl text-sm" placeholder="••••••••">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Konfirmasi Password</label>
                        <div class="relative group">
                            <i data-lucide="shield-check" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-luxury-primary transition-colors"></i>
                            <input type="password" name="konfirmasi_password" class="input-luxury w-full py-3 pl-10 pr-4 rounded-xl text-sm" placeholder="••••••••">
                        </div>
                    </div>
                </div>
                <button type="submit" class="w-full py-4 bg-luxury-primary hover:bg-luxury-secondary text-white font-bold rounded-xl shadow-lg mt-6 transition-all cursor-pointer">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <!-- Return Modal -->
    <div id="return-modal" class="fixed inset-0 z-[90] flex items-center justify-center bg-luxury-secondary/60 backdrop-blur-sm hidden px-4 overflow-y-auto py-4">
        <div class="bg-white rounded-[2rem] shadow-2xl animate-popup max-w-md w-full relative overflow-hidden ring-1 ring-black/5">
            <div class="bg-luxury-primary px-8 py-6 flex justify-between items-center text-white">
                <div>
                    <h2 class="text-xl font-display font-bold">Pengembalian Aset</h2>
                    <p id="return-item-name" class="text-xs opacity-80 mt-1 uppercase tracking-widest">Nama Barang</p>
                </div>
                <button type="button" onclick="closeReturnModal()" class="text-white/70 hover:text-white transition-colors cursor-pointer"><i data-lucide="x" class="w-6 h-6"></i></button>
            </div>
            
            <form action="proses_kembali.php" method="POST" class="p-8 space-y-6 bg-white">
                <input type="hidden" name="id" id="return-item-id">
                
                <div class="space-y-2">
                    <label class="text-xs font-bold text-luxury-primary uppercase tracking-wider ml-1">Kondisi Barang Saat Ini</label>
                    <select name="kondisi_kembali" required class="input-luxury w-full p-4 rounded-xl text-sm font-medium border border-slate-200 outline-none focus:border-luxury-primary transition-colors bg-white">
                        <option value="baik">✅ Baik / Berfungsi Normal</option>
                        <option value="rusak">❌ Rusak / Bermasalah</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="flex justify-between items-center text-xs font-bold text-luxury-primary uppercase tracking-wider ml-1">
                        <span>Catatan / Keluhan</span>
                        <span class="text-slate-400 font-medium">(Opsional)</span>
                    </label>
                    <textarea name="keluhan" rows="3" class="input-luxury w-full p-4 rounded-xl text-sm font-medium border border-slate-200 outline-none focus:border-luxury-primary transition-colors resize-none placeholder-slate-300" placeholder="Misal: Kabel proyektor kadang putus nyambung..."></textarea>
                </div>
                
                <button type="submit" class="w-full py-4 bg-luxury-primary hover:bg-luxury-secondary text-white font-bold rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i> Konfirmasi Pengembalian
                </button>
            </form>
        </div>
    </div>

    <div id="main-app-section" class="min-h-screen bg-[#F9FAFB] flex relative">

        <!-- Mobile Sidebar Overlay -->
        <div id="mobile-sidebar-overlay" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-luxury-secondary/40 backdrop-blur-sm z-40 hidden transition-opacity opacity-0 lg:hidden"></div>

        <aside id="main-sidebar" class="fixed inset-y-0 left-0 w-72 bg-white border-r border-slate-100 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 flex flex-col justify-between py-8 px-6">
            <div>
                <div class="flex items-center gap-3 mb-10 px-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-luxury-primary to-luxury-secondary rounded-xl flex items-center justify-center text-white shadow-lg">
                        <i data-lucide="layers" class="w-5 h-5"></i>
                    </div>
                    <span class="text-2xl font-display font-bold text-luxury-secondary">SIFASKA</span>
                </div>
                <nav class="space-y-2">
                    <p class="px-4 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Menu Utama</p>
                    <button onclick="switchTab('dashboard')" id="nav-btn-dashboard" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-bold bg-luxury-primary/10 text-luxury-primary transition-all">
                        <i data-lucide="layout-grid" class="w-5 h-5"></i> Dashboard
                    </button>
                    <button onclick="switchTab('fasilitas')" id="nav-btn-fasilitas" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-luxury-secondary transition-all">
                        <i data-lucide="monitor" class="w-5 h-5"></i> Fasilitas
                    </button>
                    <button onclick="switchTab('riwayat')" id="nav-btn-riwayat" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-luxury-secondary transition-all">
                        <i data-lucide="history" class="w-5 h-5"></i> Riwayat
                    </button>
                </nav>
            </div>
        </aside>

        <main class="flex-1 lg:ml-72 w-full min-h-screen flex flex-col">
            <header class="sticky top-0 z-20 bg-white/70 backdrop-blur-xl border-b border-white/50 px-6 py-4 flex items-center justify-between lg:justify-end">
                <div class="lg:hidden flex items-center gap-3">
                    <button onclick="toggleMobileSidebar()" class="p-2 bg-white border border-slate-100 rounded-xl shadow-sm text-luxury-secondary">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-luxury-primary to-luxury-secondary rounded-lg flex items-center justify-center text-white shadow-sm">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                        </div>
                        <span class="font-display font-bold text-luxury-secondary text-lg">SIFASKA</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden md:flex items-center gap-2 bg-white border border-slate-100 rounded-full px-4 py-2 shadow-sm text-sm text-slate-500">
                        <i data-lucide="calendar" class="w-4 h-4 text-luxury-primary"></i>
                        <span id="current-date-display"></span>
                    </div>
                    <div class="flex items-center gap-3 ml-2 pl-4 md:border-l border-slate-200 relative">
                        <div class="hidden md:block text-right">
                            <p class="text-sm font-bold text-luxury-secondary"><?= htmlspecialchars($nama_user) ?></p>
                            <p class="text-xs font-bold text-luxury-primary uppercase tracking-widest">Mahasiswa</p>
                        </div>
                        <button onclick="toggleProfileDropdown()" class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden border-2 border-white shadow-sm hover:ring-2 hover:ring-luxury-primary/50 transition-all focus:outline-none cursor-pointer">
                            <img src="<?= isset($_SESSION['foto_profil']) && $_SESSION['foto_profil'] != 'default.png' ? '../uploads/profiles/' . $_SESSION['foto_profil'] : 'https://ui-avatars.com/api/?name=' . urlencode($nama_user) . '&background=5B86A4&color=fff' ?>" alt="Profile" class="w-full h-full object-cover">
                        </button>
                        <!-- Dropdown Menu -->
                        <div id="profile-dropdown" class="hidden absolute right-0 top-12 mt-2 w-48 bg-white border border-slate-100 rounded-2xl shadow-xl py-2 z-50">
                            <div class="px-4 py-3 border-b border-slate-50/80 mb-1 lg:hidden">
                                <p class="text-sm font-bold text-slate-700 truncate"><?= htmlspecialchars($nama_user) ?></p>
                                <p class="text-xs text-slate-400 capitalize truncate"><?= htmlspecialchars($prodi_user) ?></p>
                            </div>
                            <button onclick="openPasswordModal()" class="w-full text-left px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-luxury-primary transition-colors flex items-center gap-3 font-medium cursor-pointer">
                                <i data-lucide="user-cog" class="w-4 h-4"></i> Pengaturan Profil
                            </button>
                            <button onclick="confirmLogout()" class="w-full text-left px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors flex items-center gap-3 font-medium mt-1 cursor-pointer">
                                <i data-lucide="log-out" class="w-4 h-4"></i> Keluar Akun
                            </button>
                        </div>
                    </div>
                </div>
            </header>


            <div id="content-dashboard" class="p-6 md:p-10 max-w-7xl mx-auto w-full animate-popup">
            <div class="bg-gradient-to-tr from-luxury-primary to-luxury-secondary rounded-[2.5rem] p-8 md:p-10 text-white mb-8 relative overflow-hidden shadow-xl">
               <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                   <div>
                       <h1 class="text-3xl md:text-3xl lg:text-4xl font-display font-bold mb-2"><?= $sapaan ?>, <?= htmlspecialchars($nama_depan) ?>!</h1>
                       <p class="text-white/80 text-sm md:text-base max-w-xl leading-relaxed mb-6">Selamat datang di Sistem Pengelolaan Inventaris dan Peminjaman Fasilitas Akademik Universitas Muhammadiyah Sorong. Ajukan peminjaman fasilitas dengan mudah di sini.</p>
                       <button onclick="switchTab('fasilitas')" class="px-6 py-3 bg-white text-luxury-secondary rounded-xl font-bold hover:shadow-lg hover:scale-105 transition-all duration-300 inline-flex items-center gap-2 cursor-pointer">
                            <span>Buat Pengajuan</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                   </div>
                   <div class="hidden lg:flex flex-col items-center justify-center p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 min-w-[140px]">
                        <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-1">Status Anda</p>
                        <?php if($count_active > 0): ?>
                            <p class="text-[17px] font-bold text-yellow-300">Meminjam</p>
                        <?php else: ?>
                            <p class="text-[16px] font-bold text-emerald-300">Tidak Ada Pinjaman</p>
                        <?php endif; ?>
                   </div>
               </div>
               <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
               <div class="absolute bottom-0 right-32 w-48 h-48 bg-indigo-400 opacity-20 rounded-full blur-2xl translate-y-1/3"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-[2rem] shadow-soft border border-slate-100 hover:border-luxury-primary/30 transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-3 rounded-2xl bg-blue-50 text-blue-600 group-hover:scale-110 transition-transform"><i data-lucide="clock" class="w-6 h-6"></i></div>
                            <span class="text-xs font-bold text-slate-400 uppercase">Aktif</span>
                        </div>
                        <h3 class="text-4xl font-display font-bold text-luxury-secondary mb-1"><?= $count_active ?></h3>
                        <p class="text-sm font-medium text-slate-500">Peminjaman Berjalan</p>
                    </div>
                     <div class="bg-white p-6 rounded-[2rem] shadow-soft border border-slate-100 hover:border-luxury-primary/30 transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600 group-hover:scale-110 transition-transform"><i data-lucide="check-circle-2" class="w-6 h-6"></i></div>
                            <span class="text-xs font-bold text-slate-400 uppercase">Selesai</span>
                        </div>
                        <h3 class="text-4xl font-display font-bold text-luxury-secondary mb-1"><?= $count_finished ?></h3>
                        <p class="text-sm font-medium text-slate-500">Riwayat Peminjaman</p>
                    </div>
                </div>
            </div>

            <div id="content-fasilitas" class="hidden-section p-6 md:p-10 max-w-7xl mx-auto w-full animate-popup">
                <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-display font-bold text-luxury-secondary">Katalog Aset & Fasilitas</h1>
                        <p class="text-slate-500 mt-1">Daftar fasilitas akademik yang tersedia untuk dipinjam.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php if (mysqli_num_rows($result_items) > 0): ?>
                        <?php while($item = mysqli_fetch_assoc($result_items)): ?>
                            <div class="bg-white rounded-[2rem] border border-slate-200/70 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 group overflow-hidden flex flex-col hover:border-luxury-primary/30">
                                <div class="h-48 bg-slate-50 flex items-center justify-center relative group-hover:bg-luxury-primary/5 transition-colors">
                                    <?php 
                                        $icon = 'box';
                                        if($item['kategori'] == 'Elektronik') $icon = 'monitor';
                                        elseif($item['kategori'] == 'Audio') $icon = 'speaker';
                                        elseif($item['kategori'] == 'Alat Pendukung') $icon = 'plug';
                                        elseif($item['kategori'] == 'Alat Lab') $icon = 'flask-conical';
                                    ?>
                                    <i data-lucide="<?= $icon ?>" class="w-16 h-16 text-slate-300 group-hover:scale-110 group-hover:text-luxury-primary transition-all duration-500"></i>
                                    <?php if($item['stok'] > 0): ?>
                                        <span class="absolute top-4 right-4 bg-emerald-100 text-emerald-700 text-xs font-bold px-2.5 py-1 rounded-full border border-emerald-200">Tersedia: <?= $item['stok'] ?></span>
                                    <?php else: ?>
                                        <span class="absolute top-4 right-4 bg-red-100 text-red-700 text-xs font-bold px-2.5 py-1 rounded-full border border-red-200">Habis</span>
                                    <?php endif; ?>
                                </div>
                                <div class="p-6 flex-1 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <span class="text-xs font-bold text-luxury-primary bg-luxury-primary/10 px-3 py-1 rounded-full uppercase tracking-wider"><?= htmlspecialchars($item['kategori']) ?></span>
                                            <?php if(!empty($item['fakultas'])): ?>
                                                <span class="text-[10px] font-bold px-2.5 py-1 rounded-full truncate max-w-[130px]" style="<?= getFakultasBadgeStyle($item['fakultas']) ?>">
                                                    <i data-lucide="building-2" class="w-3 h-3 inline-block mr-1 -mt-0.5"></i><?= htmlspecialchars($item['fakultas']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="text-lg font-bold text-luxury-secondary mb-2"><?= htmlspecialchars($item['nama_barang']) ?></h3>
                                    </div>
                                    <?php if($item['stok'] > 0): ?>
                                        <button onclick="openLoanModal('<?= $item['nama_barang'] ?>', '<?= $item['id'] ?>')" class="w-full py-3.5 mt-4 rounded-xl bg-gradient-to-r from-luxury-primary to-luxury-secondary hover:from-luxury-secondary hover:to-luxury-dark text-white font-bold transition-all duration-300 shadow-md shadow-luxury-primary/20 hover:shadow-xl hover:shadow-luxury-primary/30 active:scale-95 cursor-pointer flex items-center justify-center gap-2 group/btn">
                                            <span>Ajukan Pinjam</span>
                                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                        </button>
                                    <?php else: ?>
                                        <button disabled class="w-full py-3.5 mt-4 rounded-xl bg-slate-200 text-slate-400 font-bold cursor-not-allowed">
                                            Stok Habis
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center py-12 text-slate-500">
                            Belum ada data fasilitas.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="content-riwayat" class="hidden-section p-6 md:p-10 max-w-7xl mx-auto w-full animate-popup">
                
                <?php if (count($history_data) > 0): ?>
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <h1 class="text-2xl font-display font-bold text-luxury-secondary">Riwayat Aktivitas</h1>
                        <div class="flex items-center gap-3 w-full md:w-auto flex-wrap md:flex-nowrap">
                            <div class="flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2 shadow-sm focus-within:border-luxury-primary focus-within:ring-4 focus-within:ring-luxury-primary/10 transition-all w-full sm:w-auto cursor-text" onclick="document.getElementById('limit-table-riwayat').focus()">
                            <i data-lucide="list-filter" class="w-4 h-4 text-slate-400"></i>
                            <input type="number" id="limit-table-riwayat" value="" min="1" onkeydown="if(event.key === 'Enter') filterTable('search-riwayat', 'table-riwayat')" class="w-10 bg-transparent text-sm font-bold text-luxury-secondary focus:outline-none text-center appearance-none placeholder-slate-300" placeholder="-">
                            
                        </div>
                        
                        </div>
                    </div>
                    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-soft p-8">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left min-w-[600px]">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-l-xl">Barang</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Waktu Pinjam</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-r-xl text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50" id="table-riwayat">
                                    <?php foreach($history_data as $log): ?>
                                        <tr>
                                            <td class="px-6 py-4 font-bold text-slate-700">
                                                <?= $log['nama_barang'] ?>
                                                <p class="text-xs font-normal text-slate-400"><?= $log['kategori'] ?></p>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600">
                                                <?= date('d M Y', strtotime($log['tanggal_pinjam'])) ?><br>
                                                <span class="text-xs text-slate-400"><?= date('H:i', strtotime($log['jam_mulai'])) ?> - <?= date('H:i', strtotime($log['jam_selesai'])) ?></span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <?php if($log['status'] == 'approved'): ?>
                                                    <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold mb-2 inline-block">Sedang Dipinjam</span>
                                                    <br>
                                                    <button onclick="openReturnModal('<?= $log['id'] ?>', '<?= htmlspecialchars($log['nama_barang'], ENT_QUOTES) ?>')" class="px-3 py-1 bg-slate-100 text-slate-500 rounded-full text-xs font-bold hover:bg-slate-200 hover:text-luxury-secondary transition-all inline-flex items-center gap-1">
                                                        <i data-lucide="corner-down-left" class="w-3 h-3"></i> Ajukan Pengembalian
                                                    </button>
                                                <?php elseif($log['status'] == 'return_request'): ?>
                                                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold">Menunggu Verifikasi Petugas</span>
                                                <?php elseif($log['status'] == 'returned'): ?>
                                                    <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-xs font-bold">Dikembalikan</span>
                                                <?php elseif($log['status'] == 'rejected'): ?>
                                                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold">Ditolak</span>
                                                <?php else: ?>
                                                    <span class="bg-slate-200 text-slate-600 px-3 py-1 rounded-full text-xs font-bold">Menunggu Validasi</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="pagination-table-riwayat" class="flex flex-wrap items-center justify-end gap-1.5 mt-auto pt-4 border-t border-slate-100"></div>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center text-center">
                        <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mb-6">
                            <i data-lucide="history" class="w-10 h-10 text-slate-300"></i>
                        </div>
                        <h2 class="text-2xl font-display font-bold text-luxury-secondary">Belum Ada Aktivitas</h2>
                        <p class="text-slate-500 mt-2 max-w-sm">Mulai ajukan peminjaman untuk melihat riwayat aktivitas Anda di sini.</p>
                    </div>
                <?php endif; ?>
            
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        setTimeout(() => {
            const toasts = document.querySelectorAll('#toast-container > div');
            toasts.forEach(toast => {
                toast.style.animation = 'none';
                void toast.offsetWidth;
                toast.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => toast.remove(), 300);
            });
        }, 1500);
        
        function switchTab(tabName) {
            ['dashboard', 'fasilitas', 'riwayat'].forEach(tab => {
                document.getElementById(`content-${tab}`).classList.add('hidden-section');
                const btn = document.getElementById(`nav-btn-${tab}`);
                if(btn) {
                    btn.classList.remove('bg-luxury-primary/10', 'text-luxury-primary');
                    btn.classList.add('text-slate-500', 'hover:bg-slate-50');
                }
            });

            document.getElementById(`content-${tabName}`).classList.remove('hidden-section');
            
            const activeBtn = document.getElementById(`nav-btn-${tabName}`);
            if(activeBtn) {
                activeBtn.classList.remove('text-slate-500', 'hover:bg-slate-50');
                activeBtn.classList.add('bg-luxury-primary/10', 'text-luxury-primary');
            }
            
            if (window.innerWidth < 1024) {
                const sidebar = document.getElementById('main-sidebar');
                if (!sidebar.classList.contains('-translate-x-full')) {
                    toggleMobileSidebar();
                }
            }
            lucide.createIcons();
        }


        let clockInterval;
        
        function updateClock() {
            const now = new Date();
            document.getElementById('real-time-clock').textContent = now.toLocaleTimeString('id-ID');
        }

        function openLoanModal(itemName, itemId) {
            document.getElementById('loan-item-name').textContent = itemName;
            document.getElementById('loan-item-id').value = itemId; 
            document.getElementById('loan-modal').classList.remove('hidden');
            updateClock();
            clockInterval = setInterval(updateClock, 1000);

            const today = new Date().toISOString().split('T')[0];
            document.getElementById('loan-date-start').min = today;
            document.getElementById('loan-date-end').min = today;
        }

        function closeLoanModal() {
            document.getElementById('loan-modal').classList.add('hidden');
            clearInterval(clockInterval);
            document.getElementById('form-peminjaman').reset();
        }

        function showConfirmModal(url, title, message, type = 'normal') {
            const modal = document.getElementById('confirm-modal');
            const titleEl = document.getElementById('confirm-title');
            const msgEl = document.getElementById('confirm-message');
            const btnYes = document.getElementById('confirm-btn-yes');

            titleEl.textContent = title;
            msgEl.textContent = message;
            btnYes.href = url;

            if (type === 'danger') {
                btnYes.className = "flex-1 py-3 bg-red-500 text-white font-bold rounded-xl shadow-lg hover:bg-red-600 transition-all";
            } else {
                btnYes.className = "flex-1 py-3 bg-luxury-primary text-white font-bold rounded-xl shadow-lg hover:bg-luxury-secondary transition-all";
            }
            
            modal.classList.remove('hidden');
        }

        function closeConfirmModal() {
            document.getElementById('confirm-modal').classList.add('hidden');
        }

        function confirmLogout() {
            showConfirmModal('../auth/logout.php', 'Akhiri Sesi?', 'Anda harus login kembali untuk mengakses layanan peminjaman.', 'danger');
        }

        function openReturnModal(id, itemName) {
            document.getElementById('return-item-id').value = id;
            document.getElementById('return-item-name').textContent = itemName;
            document.getElementById('return-modal').classList.remove('hidden');
        }

        function closeReturnModal() {
            document.getElementById('return-modal').classList.add('hidden');
        }
        
        function toggleProfileDropdown() {
            document.getElementById('profile-dropdown').classList.toggle('hidden');
        }
        
        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profile-dropdown');
            if (dropdown && !dropdown.contains(e.target) && !e.target.closest('button[onclick="toggleProfileDropdown()"]')) {
                dropdown.classList.add('hidden');
            }
        });

        function openPasswordModal() {
            document.getElementById('profile-dropdown').classList.add('hidden');
            document.getElementById('password-modal').classList.remove('hidden');
        }

        function closePasswordModal() {
            document.getElementById('password-modal').classList.add('hidden');
        }
        
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const overlay = document.getElementById('mobile-sidebar-overlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.remove('opacity-0'), 10);
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
        }

        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const dateNow = new Date();
        const dateStr = `${days[dateNow.getDay()]}, ${dateNow.getDate()} ${months[dateNow.getMonth()]} ${dateNow.getFullYear()}`;
        document.getElementById('current-date-display').textContent = dateStr;

        const toasts = document.querySelectorAll('#toast-container > div');
        toasts.forEach(toast => {
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        });

        const tableState = {};

        function initTableState(tableId) {
            if (!tableState[tableId]) {
                tableState[tableId] = { currentPage: 1, lastFilter: '', lastLimit: 10 };
            }
        }

        function filterTable(inputId, tableId) {
            const input = document.getElementById(inputId);
            const filter = input ? input.value.toLowerCase() : '';
            const table = document.getElementById(tableId);
            if (!table) return;
            const rows = table.getElementsByTagName('tr');

            const limitInput = document.getElementById('limit-' + tableId);
            let limit = limitInput ? limitInput.value : '';
            limit = (limit === '') ? 10 : (limit.toLowerCase() === 'all' ? 999999 : parseInt(limit));
            if (isNaN(limit) || limit < 1) limit = 10;

            initTableState(tableId);
            const state = tableState[tableId];

            if (state.lastFilter !== filter || state.lastLimit !== limit) {
                state.currentPage = 1;
                state.lastFilter = filter;
                state.lastLimit = limit;
            }

            let matchedRows = [];
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let match = false;
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j]) {
                        const textValue = cells[j].textContent || cells[j].innerText;
                        if (textValue.toLowerCase().indexOf(filter) > -1) {
                            match = true;
                            break;
                        }
                    }
                }
                if (match) {
                    matchedRows.push(rows[i]);
                }
                rows[i].style.display = "none";
            }

            const totalPages = Math.ceil(matchedRows.length / limit);
            if (state.currentPage > totalPages && totalPages > 0) state.currentPage = totalPages;
            if (state.currentPage < 1) state.currentPage = 1;

            const startIndex = (state.currentPage - 1) * limit;
            const endIndex = startIndex + limit;

            for (let i = startIndex; i < endIndex && i < matchedRows.length; i++) {
                matchedRows[i].style.display = "";
            }

            renderPagination(tableId, matchedRows.length, limit, state.currentPage, inputId);
        }

        function changePage(tableId, inputId, newPage) {
            if (!tableState[tableId]) return;
            tableState[tableId].currentPage = newPage;
            filterTable(inputId, tableId);
        }

        function renderPagination(tableId, totalItems, limit, currentPage, inputId) {
            const container = document.getElementById('pagination-' + tableId);
            if (!container) return;
            
            container.innerHTML = '';
            const totalPages = Math.ceil(totalItems / limit);
            if (totalPages <= 1) return;

            const prevBtn = document.createElement('button');
            prevBtn.innerHTML = '<i data-lucide="chevron-left" class="w-4 h-4"></i>';
            prevBtn.className = `px-3 py-1.5 rounded-lg border ${currentPage === 1 ? 'border-slate-100 text-slate-300 cursor-not-allowed' : 'border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer'} flex items-center justify-center bg-white shadow-sm transition-all`;
            if (currentPage > 1) prevBtn.onclick = () => changePage(tableId, inputId, currentPage - 1);
            container.appendChild(prevBtn);

            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = `w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold transition-all cursor-pointer ${i === currentPage ? 'bg-luxury-primary text-white shadow-md' : 'text-slate-600 bg-white border border-slate-200 hover:bg-slate-50'}`;
                if (i !== currentPage) pageBtn.onclick = () => changePage(tableId, inputId, i);
                container.appendChild(pageBtn);
            }

            const nextBtn = document.createElement('button');
            nextBtn.innerHTML = '<i data-lucide="chevron-right" class="w-4 h-4"></i>';
            nextBtn.className = `px-3 py-1.5 rounded-lg border ${currentPage === totalPages ? 'border-slate-100 text-slate-300 cursor-not-allowed' : 'border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer'} flex items-center justify-center bg-white shadow-sm transition-all`;
            if (currentPage < totalPages) nextBtn.onclick = () => changePage(tableId, inputId, currentPage + 1);
            container.appendChild(nextBtn);
            
            lucide.createIcons();
        }

        document.addEventListener("DOMContentLoaded", function() {
            if(document.getElementById('table-riwayat')) filterTable('search-riwayat', 'table-riwayat');
        });
    </script>
</body>
</html>
