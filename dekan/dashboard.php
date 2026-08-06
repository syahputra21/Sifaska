<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dekan') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_user = $_SESSION['nama'];

date_default_timezone_set('Asia/Jakarta');
$jam = date('H');
if ($jam < 11) $sapaan = 'Selamat Pagi';
elseif ($jam < 15) $sapaan = 'Selamat Siang';
elseif ($jam < 18) $sapaan = 'Selamat Sore';
else $sapaan = 'Selamat Malam';
$nama_depan = explode(' ', $nama_user)[0];

$fakultas_admin = $_SESSION['fakultas'] ?? '';

$res_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM loans JOIN users ON loans.user_id = users.id WHERE loans.status IN ('pending', 'return_request') AND users.fakultas = '$fakultas_admin'");
$count_pending = mysqli_fetch_assoc($res_pending)['total'];

$res_active = mysqli_query($conn, "SELECT COUNT(*) as total FROM loans JOIN users ON loans.user_id = users.id WHERE loans.status = 'approved' AND users.fakultas = '$fakultas_admin'");
$count_active = mysqli_fetch_assoc($res_active)['total'];

$res_items = mysqli_query($conn, "SELECT COUNT(*) as total FROM items");
$count_items = mysqli_fetch_assoc($res_items)['total'];


$query_pending = "SELECT loans.*, users.nama as peminjam, users.fakultas, users.prodi, items.nama_barang 
                  FROM loans 
                  JOIN users ON loans.user_id = users.id 
                  JOIN items ON loans.item_id = items.id 
                  WHERE loans.status IN ('pending', 'return_request') 
                  AND users.fakultas = '$fakultas_admin'
                  ORDER BY loans.created_at ASC";
$result_pending = mysqli_query($conn, $query_pending);

$query_inventory = "SELECT * FROM items WHERE fakultas = '$fakultas_admin' ORDER BY created_at DESC";
$result_inventory = mysqli_query($conn, $query_inventory);

$query_active = "SELECT loans.*, users.nama as peminjam, items.nama_barang 
                 FROM loans 
                 JOIN users ON loans.user_id = users.id 
                 JOIN items ON loans.item_id = items.id 
                 WHERE loans.status = 'approved' 
                 AND users.fakultas = '$fakultas_admin'
                 ORDER BY loans.tanggal_pinjam ASC";
$result_active = mysqli_query($conn, $query_active);

$query_users = "SELECT * FROM users WHERE fakultas = '$fakultas_admin' ORDER BY created_at DESC";
$result_users = mysqli_query($conn, $query_users);

$query_history = "SELECT loans.*, users.nama as peminjam, users.fakultas, users.prodi, items.nama_barang 
                  FROM loans 
                  JOIN users ON loans.user_id = users.id 
                  JOIN items ON loans.item_id = items.id 
                  WHERE loans.status IN ('returned', 'rejected') 
                  AND users.fakultas = '$fakultas_admin'
                  ORDER BY loans.created_at DESC";
$result_history = mysqli_query($conn, $query_history);

$query_requests = "SELECT item_requests.*, items.nama_barang, users.nama as pengaju 
                   FROM item_requests 
                   JOIN items ON item_requests.item_id = items.id 
                   JOIN users ON item_requests.user_id = users.id 
                   WHERE users.fakultas = '$fakultas_admin' AND item_requests.status = 'approved_kaprodi'
                   ORDER BY item_requests.created_at ASC";
$result_requests = mysqli_query($conn, $query_requests);

$res_pending_req = mysqli_query($conn, "SELECT COUNT(*) as total FROM item_requests JOIN users ON item_requests.user_id = users.id WHERE item_requests.status = 'approved_kaprodi' AND users.fakultas = '$fakultas_admin'");
$count_pending_req = mysqli_fetch_assoc($res_pending_req)['total'];

$query_chart = "SELECT items.nama_barang, 
                       SUM(CASE WHEN loans.status IN ('approved', 'returned') THEN 1 ELSE 0 END) as total_approved,
                       SUM(CASE WHEN loans.status IN ('rejected') THEN 1 ELSE 0 END) as total_rejected
                FROM loans 
                JOIN items ON loans.item_id = items.id
                JOIN users ON loans.user_id = users.id
                WHERE users.fakultas = '$fakultas_admin'
                GROUP BY items.id";
$result_chart = mysqli_query($conn, $query_chart);
$chart_labels = [];
$chart_approved = [];
$chart_rejected = [];
while ($row = mysqli_fetch_assoc($result_chart)) {
    $chart_labels[] = $row['nama_barang'];
    $chart_approved[] = $row['total_approved'];
    $chart_rejected[] = $row['total_rejected'];
}


?>

<?php include '../includes/header.php'; ?>

<div id="toast-container" class="fixed top-6 right-6 z-[110] flex flex-col gap-3 pointer-events-none">
    <?php if (isset($_SESSION['success_msg'])): ?>
        <div
            class="bg-white border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-xl shadow-soft flex items-center gap-3 min-w-[300px] animate-slide-in pointer-events-auto">
            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
            <span class="text-sm font-bold"><?= $_SESSION['success_msg'] ?></span>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div
            class="bg-white border-l-4 border-red-500 text-red-700 p-4 rounded-xl shadow-soft flex items-center gap-3 min-w-[300px] animate-slide-in pointer-events-auto">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <span class="text-sm font-bold"><?= $_SESSION['error_msg'] ?></span>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>
</div>

<div id="inventory-modal"
    class="fixed inset-0 z-[90] flex items-center justify-center bg-luxury-secondary/60 backdrop-blur-sm hidden px-4 overflow-y-auto py-4">
    <div
        class="bg-white rounded-[2rem] shadow-2xl animate-popup max-w-lg w-full relative overflow-visible ring-1 ring-black/5">
        <div class="bg-luxury-primary px-8 py-6 flex justify-between items-center text-white rounded-t-[2rem]">
            <h2 class="text-xl font-display font-bold" id="modal-title">Input Data Aset</h2>
            <button onclick="closeInventoryModal()" class="text-white/70 hover:text-white"><i data-lucide="x"
                    class="w-6 h-6"></i></button>
        </div>
        <form id="form-inventory" action="tambah_barang.php" method="POST" class="p-8 space-y-5 bg-white">
            <input type="hidden" name="id" id="edit-id">
            <div class="space-y-1">
                <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Nama Barang</label>
                <input type="text" name="nama_barang" id="edit-nama" required
                    class="input-luxury w-full py-3 px-4 rounded-xl text-sm"
                    placeholder="Contoh: Proyektor Epson EB-E500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Kategori</label>
                    <select name="kategori" id="edit-kategori" class="input-luxury w-full py-3 px-4 rounded-xl text-sm">
                        <option value="Elektronik">Elektronik</option>
                        <option value="Audio">Audio</option>
                        <option value="Alat Pendukung">Alat Pendukung</option>
                        <option value="Alat Lab">Alat Lab</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Stok Awal</label>
                    <input type="number" name="stok" id="edit-stok" required
                        class="input-luxury w-full py-3 px-4 rounded-xl text-sm" placeholder="0">
                </div>
                <div class="space-y-1 col-span-2">
                    <label class="text-xs font-bold text-red-500 uppercase ml-1">Stok Rusak (Tidak Bisa Dipinjam)</label>
                    <input type="number" name="stok_rusak" id="edit-stok-rusak" value="0" required
                        class="input-luxury w-full py-3 px-4 rounded-xl text-sm border-red-200 focus:border-red-500" placeholder="0">
                </div>
            </div>
            <button type="submit"
                class="w-full py-4 bg-luxury-primary hover:bg-luxury-secondary text-white font-bold rounded-xl shadow-lg mt-4 transition-all"
                id="btn-submit">Simpan ke Database</button>
        </form>
    </div>
</div>

<div id="user-modal"
    class="fixed inset-0 z-[90] flex items-center justify-center bg-luxury-secondary/60 backdrop-blur-sm hidden px-4 overflow-y-auto py-4">
    <div
        class="bg-white rounded-[2rem] shadow-2xl animate-popup max-w-lg w-full relative overflow-visible ring-1 ring-black/5">
        <div class="bg-luxury-primary px-8 py-6 flex justify-between items-center text-white rounded-t-[2rem]">
            <h2 class="text-xl font-display font-bold" id="user-modal-title">Input Data User</h2>
            <button onclick="closeUserModal()" class="text-white/70 hover:text-white"><i data-lucide="x"
                    class="w-6 h-6"></i></button>
        </div>
        <form id="form-user" action="tambah_user.php" method="POST" class="p-8 space-y-5 bg-white">
            <input type="hidden" name="id" id="edit-user-id">
            <div class="space-y-1">
                <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Nama Lengkap</label>
                <input type="text" name="nama" id="edit-user-nama" required
                    class="input-luxury w-full py-3 px-4 rounded-xl text-sm" placeholder="Nama Lengkap">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Username</label>
                    <input type="text" name="username" id="edit-user-username" required
                        class="input-luxury w-full py-3 px-4 rounded-xl text-sm" placeholder="Username">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Password</label>
                    <input type="password" name="password" id="edit-user-password"
                        class="input-luxury w-full py-3 px-4 rounded-xl text-sm"
                        placeholder="Kosongkan jika tidak ubah">
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Role</label>
                <select name="role" id="edit-user-role" class="input-luxury w-full py-3 px-4 rounded-xl text-sm">
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="pengurus_fakultas">Staff Fakultas</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Fakultas</label>
                    <select name="fakultas" id="edit-user-fakultas" onchange="updateProdiDropdown('edit-user-fakultas', 'edit-user-prodi')"
                        class="input-luxury w-full py-3 px-4 rounded-xl text-sm">
                        <option value="">-- Tidak Ada / Umum --</option>
                        <option value="Fakultas FISIP">Fakultas FISIP</option>
                        <option value="Fakultas Hukum">Fakultas Hukum</option>
                        <option value="Fakultas Teknik">Fakultas Teknik</option>
                        <option value="Fakultas Pertanian">Fakultas Pertanian</option>
                        <option value="Fakultas Perikanan">Fakultas Perikanan</option>
                        <option value="Fakultas FKIP">Fakultas FKIP</option>
                        <option value="Fakultas Ekonomi dan Bisnis">Fakultas Ekonomi dan Bisnis</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Prodi</label>
                    <select name="prodi" id="edit-user-prodi"
                        class="input-luxury w-full py-3 px-4 rounded-xl text-sm">
                        <option value="">-- Tidak Ada --</option>
                    </select>
                </div>
            </div>

            <button type="submit"
                class="w-full py-4 bg-luxury-primary hover:bg-luxury-secondary text-white font-bold rounded-xl shadow-lg mt-4 transition-all"
                id="btn-user-submit">Simpan User</button>
        </form>
    </div>
</div>

    <div id="main-app-section" class="min-h-screen bg-[#F9FAFB] flex relative">

        <!-- Mobile Sidebar Overlay -->
        <div id="mobile-sidebar-overlay" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-luxury-secondary/40 backdrop-blur-sm z-40 hidden transition-opacity opacity-0 lg:hidden"></div>

        <aside id="main-sidebar" class="fixed inset-y-0 left-0 w-72 bg-white border-r border-slate-100 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 flex flex-col justify-between py-8 px-6">
        <div>
            <div class="flex items-center gap-3 mb-10 px-2">
                <div
                    class="w-10 h-10 bg-gradient-to-br from-luxury-primary to-luxury-secondary rounded-xl flex items-center justify-center text-white shadow-lg">
                    <i data-lucide="layers" class="w-5 h-5"></i>
                </div>
                <span class="text-2xl font-display font-bold text-luxury-secondary">SIFASKA</span>
            </div>
            <nav class="space-y-2">
                <p class="px-4 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Menu Dekan</p>
                <button onclick="switchAdminTab('dashboard')" id="admin-btn-dashboard"
                    class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-bold bg-luxury-primary/10 text-luxury-primary transition-all">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                </button>

                <button onclick="switchAdminTab('pengajuan')" id="admin-btn-pengajuan"
                    class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-luxury-secondary transition-all">
                    <i data-lucide="file-check-2" class="w-5 h-5"></i> Pengajuan Barang
                    <?php if ($count_pending_req > 0): ?>
                        <span class="ml-auto bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-xs font-bold"><?= $count_pending_req ?></span>
                    <?php endif; ?>
                </button>

                <button onclick="switchAdminTab('riwayat')" id="admin-btn-riwayat"
                    class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-luxury-secondary transition-all">
                    <i data-lucide="history" class="w-5 h-5"></i> Riwayat
                </button>
            </nav>
        </div>
    </aside>

    <div id="confirm-modal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-luxury-secondary/80 backdrop-blur-sm hidden animate-fade-in p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-sm w-full p-8 text-center animate-slide-up">
            <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600">
                <i data-lucide="help-circle" class="w-8 h-8"></i>
            </div>
            <h3 class="text-xl font-display font-bold text-luxury-secondary mb-2" id="confirm-title">Konfirmasi</h3>
            <p class="text-slate-500 mb-8" id="confirm-message">Apakah Anda yakin ingin melakukan tindakan ini?</p>
            <div class="flex gap-3">
                <button onclick="closeConfirmModal()"
                    class="flex-1 py-3 bg-slate-100 text-slate-500 font-bold rounded-xl hover:bg-slate-200 transition-colors">Batal</button>
                <a id="confirm-btn-yes" href="#"
                    class="flex-1 py-3 bg-luxury-primary text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:bg-luxury-secondary transition-all">Ya,
                    Lanjutkan</a>
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

    <main class="flex-1 lg:ml-72 w-full min-h-screen flex flex-col">
        <header class="sticky top-0 z-20 bg-white/70 backdrop-blur-xl border-b border-white/50 px-6 py-4 flex items-center justify-between lg:justify-end">
            <div class="lg:hidden flex items-center gap-3">
                <button onclick="toggleMobileSidebar()"
                    class="p-2 bg-white border border-slate-100 rounded-xl shadow-sm text-luxury-secondary">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 bg-gradient-to-br from-luxury-primary to-luxury-secondary rounded-lg flex items-center justify-center text-white shadow-sm">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                    </div>
                    <span class="font-display font-bold text-luxury-secondary text-lg">SIFASKA</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3 ml-2 pl-4 md:border-l border-slate-200 relative">
                    <div class="hidden md:block text-right">
                        <p class="text-sm font-bold text-luxury-secondary"><?= htmlspecialchars($nama_user) ?></p>
                        <p class="text-xs font-bold text-luxury-primary uppercase tracking-widest">Dekan</p>
                    </div>
                    <button onclick="toggleProfileDropdown()" class="w-10 h-10 rounded-full bg-indigo-50 overflow-hidden border-2 border-white shadow-sm hover:ring-2 hover:ring-luxury-primary/50 transition-all focus:outline-none cursor-pointer">
                        <img src="<?= isset($_SESSION['foto_profil']) && $_SESSION['foto_profil'] != 'default.png' ? '../uploads/profiles/' . $_SESSION['foto_profil'] : 'https://ui-avatars.com/api/?name=' . urlencode($nama_user) . '&background=4f46e5&color=fff' ?>" alt="Dekan" class="w-full h-full object-cover">
                    </button>
                    <!-- Dropdown Menu -->
                    <div id="profile-dropdown" class="hidden absolute right-0 top-12 mt-2 w-48 bg-white border border-slate-100 rounded-2xl shadow-xl py-2 z-50">
                        <div class="px-4 py-3 border-b border-slate-50/80 mb-1 lg:hidden">
                            <p class="text-sm font-bold text-slate-700 truncate"><?= htmlspecialchars($nama_user) ?></p>
                            <p class="text-xs text-slate-400 capitalize truncate">Dekan</p>
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



        <div id="admin-content-dashboard" class="p-6 md:p-10 max-w-7xl mx-auto w-full animate-popup">
            
            <div class="bg-gradient-to-tr from-luxury-primary to-luxury-secondary rounded-[2.5rem] p-8 md:p-10 text-white mb-8 relative overflow-hidden shadow-xl">
               <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                   <div>
                       <h1 class="text-3xl md:text-3xl lg:text-4xl font-display font-bold mb-2"><?= $sapaan ?>, <?= htmlspecialchars($nama_depan) ?>!</h1>
                       <p class="text-white/80 text-sm md:text-base max-w-xl leading-relaxed">Selamat datang di panel pemantauan Sistem Pengelolaan Inventaris dan Peminjaman Fasilitas Akademik Universitas Muhammadiyah Sorong. Terdapat <b class="text-luxury-secondary bg-white px-2 py-0.5 rounded-md font-bold mx-1"><?= $count_pending ?> pengajuan</b> yang sedang diproses oleh pengurus.</p>
                   </div>
                   <div class="hidden lg:flex flex-col items-center justify-center p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 min-w-[140px]">
                        <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-1">Hari Ini</p>
                        <p class="text-xl font-bold"><?= date('d F Y') ?></p>
                   </div>
               </div>
               <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
               <div class="absolute bottom-0 right-32 w-48 h-48 bg-indigo-400 opacity-20 rounded-full blur-2xl translate-y-1/3"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div
                    class="text-left bg-luxury-secondary rounded-[2rem] p-8 text-white shadow-soft relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-xs font-bold opacity-60 uppercase tracking-widest">Menunggu Verifikasi</p>
                        <h2 class="text-5xl font-display font-bold mt-2"><?= $count_pending ?></h2>
                    </div>
                    <i data-lucide="inbox"
                        class="absolute -bottom-4 -right-4 w-32 h-32 text-white/10"></i>
                </div>
                <div class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Sedang Dipinjam</p>
                    <h2 class="text-5xl font-display font-bold text-luxury-secondary mt-2"><?= $count_active ?></h2>
                </div>
                <div class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Inventaris</p>
                    <h2 class="text-5xl font-display font-bold text-luxury-secondary mt-2"><?= $count_items ?></h2>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-soft p-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                    <h3 class="text-xl font-bold text-luxury-secondary">Barang Sedang Dipinjam (<?= $count_active ?>)
                    </h3>
                    <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 w-full md:w-auto">
                        <div class="flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2 shadow-sm focus-within:border-luxury-primary focus-within:ring-4 focus-within:ring-luxury-primary/10 transition-all w-full sm:w-auto cursor-text" onclick="document.getElementById('limit-table-active').focus()">
                            <i data-lucide="list-filter" class="w-4 h-4 text-slate-400"></i>
                            <input type="number" id="limit-table-active" value="" min="1" onkeydown="if(event.key === 'Enter') filterTable('search-active', 'table-active')" class="w-10 bg-transparent text-sm font-bold text-luxury-secondary focus:outline-none text-center appearance-none placeholder-slate-300" placeholder="-">
                            
                        </div>
                        
                    </div>
                </div>
                <?php if ($count_active > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left min-w-[600px]">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-l-xl">
                                        Peminjam</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                        Barang
                                    </th>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-r-xl">
                                        Waktu Pinjam</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50" id="table-active">
                                <?php while ($row = mysqli_fetch_assoc($result_active)): ?>
                                    <tr>
                                        <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($row['peminjam']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($row['nama_barang']) ?></td>
                                        <td class="px-6 py-4 text-xs text-slate-500">
                                            <?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="pagination-table-active" class="flex flex-wrap items-center justify-end gap-1.5 mt-auto pt-4 border-t border-slate-100"></div>
                <?php else: ?>
                    <p class="text-slate-500 italic">Tidak ada barang yang sedang dipinjam.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="admin-content-pengajuan" class="hidden-section p-6 md:p-10 max-w-7xl mx-auto w-full animate-popup">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <h1 class="text-2xl font-display font-bold text-luxury-secondary">Persetujuan Penggantian Barang</h1>
                <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 w-full md:w-auto">
                    <div class="flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2 shadow-sm focus-within:border-luxury-primary focus-within:ring-4 focus-within:ring-luxury-primary/10 transition-all w-full sm:w-auto cursor-text" onclick="document.getElementById('limit-table-pengajuan').focus()">
                            <i data-lucide="list-filter" class="w-4 h-4 text-slate-400"></i>
                            <input type="number" id="limit-table-pengajuan" value="" min="1" onkeydown="if(event.key === 'Enter') filterTable('search-pengajuan', 'table-pengajuan')" class="w-10 bg-transparent text-sm font-bold text-luxury-secondary focus:outline-none text-center appearance-none placeholder-slate-300" placeholder="-">
                            
                        </div>
                        
                </div>
            </div>
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-soft p-8">
                <?php if (mysqli_num_rows($result_requests) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left min-w-[800px]">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-l-xl">Diajukan Oleh</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Barang & Qty</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Alasan</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-r-xl text-right">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50" id="table-pengajuan">
                                <?php while ($req = mysqli_fetch_assoc($result_requests)): ?>
                                    <tr>
                                        <td class="px-6 py-4 font-bold text-slate-700">
                                            <?= htmlspecialchars($req['pengaju']) ?><br>
                                            <span class="text-xs text-slate-400 font-normal"><?= date('d/m/Y H:i', strtotime($req['created_at'])) ?></span>
                                            <div class="mt-1"><span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-[10px] font-bold"><i data-lucide="check-circle" class="w-3 h-3 inline"></i> ACC Kaprodi</span></div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600 font-medium">
                                            <?= htmlspecialchars($req['nama_barang']) ?> <br>
                                            <span class="text-xs bg-slate-100 px-2 py-0.5 rounded-md text-slate-600 mt-1 inline-block"><?= $req['qty'] ?> Unit</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-500 max-w-xs"><?= htmlspecialchars($req['alasan']) ?></td>
                                        <td class="px-6 py-4 text-right flex justify-end gap-2">
                                            <button onclick="confirmAction('proses_pengajuan.php?id=<?= $req['id'] ?>&status=approved_dekan', 'Setujui Penggantian?', 'Status akan berubah menjadi disetujui.')" class="px-3 py-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors font-bold text-xs flex items-center gap-1">
                                                <i data-lucide="check-square" class="w-4 h-4"></i> Setujui
                                            </button>
                                            <button onclick="confirmAction('proses_pengajuan.php?id=<?= $req['id'] ?>&status=rejected_dekan', 'Tolak Pengajuan?', 'Tindakan ini tidak dapat dibatalkan.', 'danger')" class="px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors font-bold text-xs flex items-center gap-1">
                                                <i data-lucide="x-square" class="w-4 h-4"></i> Tolak
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="pagination-table-pengajuan" class="flex flex-wrap items-center justify-end gap-1.5 mt-auto pt-4 border-t border-slate-100"></div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center text-center">
                        <i data-lucide="shield-check" class="w-12 h-12 text-emerald-100 mb-4"></i>
                        <h3 class="text-lg font-bold text-slate-700 mb-1">Tidak ada pengajuan pending</h3>
                        <p class="text-slate-500 text-sm">Semua pengajuan barang dari program studi telah Anda proses.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="admin-content-persetujuan" class="hidden-section p-6 md:p-10 max-w-7xl mx-auto w-full animate-popup">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <h1 class="text-2xl font-display font-bold text-luxury-secondary">Pantau Pengajuan</h1>
                <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 w-full md:w-auto">
                    <div class="flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2 shadow-sm focus-within:border-luxury-primary focus-within:ring-4 focus-within:ring-luxury-primary/10 transition-all w-full sm:w-auto cursor-text" onclick="document.getElementById('limit-table-persetujuan').focus()">
                            <i data-lucide="list-filter" class="w-4 h-4 text-slate-400"></i>
                            <input type="number" id="limit-table-persetujuan" value="" min="1" onkeydown="if(event.key === 'Enter') filterTable('search-persetujuan', 'table-persetujuan')" class="w-10 bg-transparent text-sm font-bold text-luxury-secondary focus:outline-none text-center appearance-none placeholder-slate-300" placeholder="-">
                            
                        </div>
                        
                </div>
            </div>
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-soft p-8">
                <?php
                mysqli_data_seek($result_pending, 0);
                if (mysqli_num_rows($result_pending) > 0):
                    ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left min-w-[800px]">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-l-xl">
                                        Pemohon</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                        Barang
                                    </th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                        Status
                                        / Ket</th>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-r-xl text-right">
                                        Keputusan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50" id="table-persetujuan">
                                <?php while ($row = mysqli_fetch_assoc($result_pending)): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-700"><?= htmlspecialchars($row['peminjam']) ?></p>
                                            <p class="text-xs text-slate-400"><?= htmlspecialchars($row['fakultas']) ?> -
                                                <?= htmlspecialchars($row['prodi']) ?>
                                            </p>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($row['nama_barang']) ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($row['status'] == 'return_request'): ?>
                                                <span
                                                    class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs font-bold">Minta
                                                    Kembali</span>
                                                <?php if($row['kondisi_kembali'] == 'rusak'): ?>
                                                    <div class="mt-2 bg-red-50 p-2 rounded-lg border border-red-100 max-w-[200px]">
                                                        <p class="text-xs font-bold text-red-600 mb-1"><i data-lucide="alert-triangle" class="w-3 h-3 inline"></i> Dilaporkan Cacat/Rusak</p>
                                                        <p class="text-[10px] text-red-500 leading-tight"><?= htmlspecialchars($row['keluhan']) ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span
                                                    class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs font-bold">Baru</span>
                                            <?php endif; ?>
                                            <div class="text-xs text-slate-400 mt-1">
                                                <?= date('d M H:i', strtotime($row['created_at'])) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="pagination-table-persetujuan" class="flex flex-wrap items-center justify-end gap-1.5 mt-auto pt-4 border-t border-slate-100"></div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center text-center">
                        <i data-lucide="check-circle" class="w-12 h-12 text-emerald-100 mb-3"></i>
                        <p class="text-slate-500 font-medium">Semua pengajuan telah ditinjau.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="admin-content-inventaris" class="hidden-section p-6 md:p-10 max-w-7xl mx-auto w-full animate-popup">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <h1 class="text-2xl font-display font-bold text-luxury-secondary">Data Induk Aset</h1>
                <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 w-full md:w-auto">
                    <div class="flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2 shadow-sm focus-within:border-luxury-primary focus-within:ring-4 focus-within:ring-luxury-primary/10 transition-all w-full sm:w-auto cursor-text" onclick="document.getElementById('limit-inventory-list').focus()">
                            <i data-lucide="list-filter" class="w-4 h-4 text-slate-400"></i>
                            <input type="number" id="limit-inventory-list" value="" min="1" onkeydown="if(event.key === 'Enter') filterTable('search-inventory', 'inventory-list')" class="w-10 bg-transparent text-sm font-bold text-luxury-secondary focus:outline-none text-center appearance-none placeholder-slate-300" placeholder="-">
                            
                        </div>
                        
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-soft p-8">
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[700px]">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-l-xl">
                                    Nama Aset</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                    Kategori</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-r-xl">
                                    Stok
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50" id="inventory-list">
                            <?php while ($item = mysqli_fetch_assoc($result_inventory)): ?>
                                <tr>
                                    <td class="px-6 py-4 font-bold text-slate-700 flex items-center gap-3">
                                        <?php
                                        $icon = 'box';
                                        if ($item['kategori'] == 'Elektronik')
                                            $icon = 'monitor';
                                        elseif ($item['kategori'] == 'Audio')
                                            $icon = 'speaker';
                                        elseif ($item['kategori'] == 'Alat Pendukung')
                                            $icon = 'plug';
                                        elseif ($item['kategori'] == 'Alat Lab')
                                            $icon = 'flask-conical';
                                        ?>
                                        <div
                                            class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                                            <i data-lucide="<?= $icon ?>" class="w-4 h-4"></i>
                                        </div>
                                        <?= htmlspecialchars($item['nama_barang']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($item['kategori']) ?></td>
                                    <td class="px-6 py-4">
                                        <span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full text-xs font-bold"><?= $item['stok'] ?> Unit</span>
                                        <?php if(isset($item['stok_rusak']) && $item['stok_rusak'] > 0): ?>
                                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-bold ml-1"><?= $item['stok_rusak'] ?> Rusak</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="admin-content-users" class="hidden-section p-6 md:p-10 max-w-7xl mx-auto w-full animate-popup">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <h1 class="text-2xl font-display font-bold text-luxury-secondary">Manajemen Pengguna</h1>
                <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 w-full md:w-auto">
                    <div class="flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2 shadow-sm focus-within:border-luxury-primary focus-within:ring-4 focus-within:ring-luxury-primary/10 transition-all w-full sm:w-auto cursor-text" onclick="document.getElementById('limit-table-users').focus()">
                            <i data-lucide="list-filter" class="w-4 h-4 text-slate-400"></i>
                            <input type="number" id="limit-table-users" value="" min="1" onkeydown="if(event.key === 'Enter') filterTable('search-users', 'table-users')" class="w-10 bg-transparent text-sm font-bold text-luxury-secondary focus:outline-none text-center appearance-none placeholder-slate-300" placeholder="-">
                            
                        </div>
                        
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-soft p-8">
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[800px]">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-l-xl">
                                    Nama</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                    Username</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                    Role</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-r-xl">
                                    Fakultas/Prodi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50" id="table-users">
                            <?php while ($user = mysqli_fetch_assoc($result_users)): ?>
                                <tr>
                                    <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($user['nama']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($user['username']) ?></td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2 py-1 rounded-full text-xs font-bold <?= $user['role'] == 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-600' ?>">
                                            <?= strtoupper($user['role']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500">
                                        <?php if ($user['role'] == 'mahasiswa'): ?>
                                            <?= htmlspecialchars($user['fakultas']) ?> <br>
                                            <span class="text-slate-400"><?= htmlspecialchars($user['prodi']) ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div id="pagination-table-users" class="flex flex-wrap items-center justify-end gap-1.5 mt-auto pt-4 border-t border-slate-100"></div>
            </div>
        </div>

        <div id="admin-content-riwayat" class="hidden-section p-6 md:p-10 max-w-7xl mx-auto w-full animate-popup">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <h1 class="text-2xl font-display font-bold text-luxury-secondary">Riwayat Peminjaman</h1>
                <div class="flex items-center gap-3 w-full md:w-auto flex-wrap md:flex-nowrap">
                    <div class="flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2 shadow-sm focus-within:border-luxury-primary focus-within:ring-4 focus-within:ring-luxury-primary/10 transition-all w-full sm:w-auto cursor-text" onclick="document.getElementById('limit-table-riwayat-admin').focus()">
                            <i data-lucide="list-filter" class="w-4 h-4 text-slate-400"></i>
                            <input type="number" id="limit-table-riwayat-admin" value="" min="1" onkeydown="if(event.key === 'Enter') filterTable('search-riwayat', 'table-riwayat-admin')" class="w-10 bg-transparent text-sm font-bold text-luxury-secondary focus:outline-none text-center appearance-none placeholder-slate-300" placeholder="-">
                            
                        </div>
                        
                    <a href="export_riwayat.php" class="px-4 py-2 bg-emerald-500 text-white font-bold rounded-xl shadow-lg hover:bg-emerald-600 transition-all flex items-center justify-center gap-2 whitespace-nowrap">
                        <i data-lucide="download" class="w-4 h-4"></i> Export
                    </a>
                </div>
            </div>

            <!-- Chart Container -->
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-soft p-8 mb-6">
                <h2 class="text-lg font-bold text-luxury-secondary mb-4">Visualisasi Kebutuhan Fasilitas</h2>
                <div class="w-full h-64 md:h-80">
                    <canvas id="riwayatKebutuhanChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-soft p-8">
                <?php if (mysqli_num_rows($result_history) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left min-w-[700px]">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-l-xl">
                                        Peminjam</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                        Barang</th>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest rounded-r-xl">
                                        Waktu</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50" id="table-riwayat-admin">
                                <?php while ($row = mysqli_fetch_assoc($result_history)): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-700"><?= htmlspecialchars($row['peminjam']) ?></p>
                                            <p class="text-xs text-slate-400"><?= htmlspecialchars($row['fakultas']) ?> -
                                                <?= htmlspecialchars($row['prodi']) ?>
                                            </p>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($row['nama_barang']) ?></td>
                                        <td class="px-6 py-4 text-xs text-slate-500">
                                            <?= date('d M Y H:i', strtotime($row['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($row['status'] == 'returned'): ?>
                                                <span
                                                    class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full text-xs font-bold">Dikembalikan</span>
                                                <?php if($row['kondisi_kembali'] == 'rusak'): ?>
                                                    <div class="mt-2 bg-red-50 p-2 rounded-lg border border-red-100 max-w-[200px]">
                                                        <p class="text-[10px] font-bold text-red-600 mb-0.5"><i data-lucide="alert-triangle" class="w-2.5 h-2.5 inline"></i> Rusak</p>
                                                        <p class="text-[9px] text-red-500 leading-tight line-clamp-2"><?= htmlspecialchars($row['keluhan']) ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif ($row['status'] == 'rejected'): ?>
                                                <span
                                                    class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-bold">Ditolak</span>
                                            <?php else: ?>
                                                <span
                                                    class="bg-slate-100 text-slate-700 px-2 py-1 rounded-full text-xs font-bold"><?= $row['status'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="pagination-table-riwayat-admin" class="flex flex-wrap items-center justify-end gap-1.5 mt-auto pt-4 border-t border-slate-100"></div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center text-center">
                        <i data-lucide="history" class="w-12 h-12 text-slate-200 mb-3"></i>
                        <p class="text-slate-500 font-medium">Belum ada riwayat peminjaman.</p>
                    </div>
                <?php endif; ?>
            </div>
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

    function switchAdminTab(tabName) {
        ['dashboard', 'persetujuan', 'inventaris', 'users', 'riwayat', 'pengajuan'].forEach(tab => {
            document.getElementById(`admin-content-${tab}`).classList.add('hidden-section');

            const btn = document.getElementById(`admin-btn-${tab}`);
            if (btn) {
                btn.classList.remove('bg-luxury-primary/10', 'text-luxury-primary');
                btn.classList.add('text-slate-500');
            }
        });

        document.getElementById(`admin-content-${tabName}`).classList.remove('hidden-section');

        const activeBtn = document.getElementById(`admin-btn-${tabName}`);
        if (activeBtn) {
            activeBtn.classList.remove('text-slate-500');
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


    function openInventoryModal() {
        document.getElementById('modal-title').textContent = "Input Data Aset";
        document.getElementById('form-inventory').action = "tambah_barang.php";
        document.getElementById('form-inventory').reset();
        document.getElementById('edit-id').value = "";
        document.getElementById('edit-stok-rusak').value = "0";
        document.getElementById('btn-submit').textContent = "Simpan ke Database";
        document.getElementById('inventory-modal').classList.remove('hidden');
    }

    function closeInventoryModal() {
        document.getElementById('inventory-modal').classList.add('hidden');
        document.getElementById('form-inventory').reset();
    }

    const fakultasProdiMap = {
        "Fakultas FISIP": ["Ilmu Administrasi Negara", "Ilmu Pemerintahan", "Sosiologi"],
        "Fakultas Hukum": ["Ilmu Hukum", "Magister Ilmu Hukum"],
        "Fakultas Teknik": ["Sipil", "Industri", "Informatika", "Lingkungan", "Perancangan Wilayah & Kota"],
        "Fakultas Pertanian": ["Agroteknologi", "Kehutanan"],
        "Fakultas Perikanan": ["Manajemen Sumber Daya Perairan", "Pengolahan Hasil Perikanan"],
        "Fakultas FKIP": ["Pendidikan Bahasa Inggris", "Pendidikan Matematika", "Pendidikan Guru Sekolah Dasar", "Pendidikan Jasmani", "Magister Pedagogik"],
        "Fakultas Ekonomi dan Bisnis": ["Manajemen", "Akuntansi", "Pariwisata", "Magister Manajemen"]
    };

    function updateProdiDropdown(fakId, prodiId, selectedProdi = '') {
        const fakSelect = document.getElementById(fakId);
        const prodiSelect = document.getElementById(prodiId);
        if (!fakSelect || !prodiSelect) return;

        const selectedFak = fakSelect.value;
        prodiSelect.innerHTML = '<option value="">-- Tidak Ada --</option>';

        let prodiList = fakultasProdiMap[selectedFak];
        if (!prodiList) {
            for (const key in fakultasProdiMap) {
                if (key.toLowerCase().includes(selectedFak.toLowerCase()) || selectedFak.toLowerCase().includes(key.toLowerCase())) {
                    prodiList = fakultasProdiMap[key];
                    break;
                }
            }
        }

        if (prodiList) {
            prodiList.forEach(prodi => {
                const option = document.createElement('option');
                option.value = prodi;
                option.textContent = prodi;
                if (prodi.toLowerCase() === selectedProdi.toLowerCase()) {
                    option.selected = true;
                }
                prodiSelect.appendChild(option);
            });
        }
    }

    function openUserModal() {
        document.getElementById('user-modal-title').textContent = "Input Data User";
        document.getElementById('form-user').action = "tambah_user.php";
        document.getElementById('form-user').reset();
        document.getElementById('edit-user-id').value = "";
        document.getElementById('edit-user-prodi').innerHTML = '<option value="">-- Tidak Ada --</option>';
        document.getElementById('btn-user-submit').textContent = "Simpan User";
        document.getElementById('edit-user-password').required = true;
        document.getElementById('user-modal').classList.remove('hidden');
    }

    function closeUserModal() {
        document.getElementById('user-modal').classList.add('hidden');
        document.getElementById('form-user').reset();
    }

    function editUser(id, nama, username, role, fakultas, prodi) {
        document.getElementById('user-modal-title').textContent = "Edit Data User";
        document.getElementById('form-user').action = "edit_user.php";

        document.getElementById('edit-user-id').value = id;
        document.getElementById('edit-user-nama').value = nama;
        document.getElementById('edit-user-username').value = username;
        document.getElementById('edit-user-role').value = role;
        
        const fakElem = document.getElementById('edit-user-fakultas');
        if (fakElem) {
            fakElem.value = fakultas || '';
            if (fakElem.value === '' && fakultas) {
                Array.from(fakElem.options).forEach(opt => {
                    if (opt.value && (opt.value.toLowerCase().includes(fakultas.toLowerCase()) || fakultas.toLowerCase().includes(opt.value.toLowerCase()))) {
                        fakElem.value = opt.value;
                    }
                });
            }
        }
        updateProdiDropdown('edit-user-fakultas', 'edit-user-prodi', prodi || '');

        document.getElementById('edit-user-password').required = false;
        document.getElementById('edit-user-password').placeholder = "Kosongkan jika tidak ubah password";

        document.getElementById('btn-user-submit').textContent = "Update User";
        document.getElementById('user-modal').classList.remove('hidden');
    }

    function editItem(id, nama, kategori, stok, stok_rusak) {
        document.getElementById('modal-title').textContent = "Edit Data Aset";
        document.getElementById('form-inventory').action = "edit_barang.php";

        document.getElementById('edit-id').value = id;
        document.getElementById('edit-nama').value = nama;
        document.getElementById('edit-kategori').value = kategori;
        document.getElementById('edit-stok').value = stok;
        document.getElementById('edit-stok-rusak').value = stok_rusak || 0;

        document.getElementById('btn-submit').textContent = "Update Data";
        document.getElementById('inventory-modal').classList.remove('hidden');
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

    function confirmAction(url, title, message, type = 'normal') {
        showConfirmModal(url, title, message, type);
    }

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
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if(document.getElementById('table-active')) filterTable('search-active', 'table-active');
        if(document.getElementById('inventory-list')) filterTable('search-inventory', 'inventory-list');
        if(document.getElementById('table-users')) filterTable('search-users', 'table-users');
        if(document.getElementById('table-riwayat-admin')) filterTable('search-riwayat', 'table-riwayat-admin');
        if(document.getElementById('table-persetujuan')) filterTable('search-persetujuan', 'table-persetujuan');
        if(document.getElementById('table-pengajuan')) filterTable('search-pengajuan', 'table-pengajuan');
        const ctx = document.getElementById('riwayatKebutuhanChart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [
                        {
                            label: 'Disetujui / Dikembalikan',
                            data: <?= json_encode($chart_approved) ?>,
                            backgroundColor: 'rgba(52, 211, 153, 0.8)',
                            borderRadius: 6
                        },
                        {
                            label: 'Ditolak',
                            data: <?= json_encode($chart_rejected) ?>,
                            backgroundColor: 'rgba(248, 113, 113, 0.8)',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        }
    });
</script>
</body>

</html>
