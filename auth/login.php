<?php
session_start();
include '../config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'dekan') {
        header("Location: ../dekan/dashboard.php");
    } else if ($_SESSION['role'] == 'pengurus_fakultas') {
        header("Location: ../pengurus_fakultas/dashboard.php");
    } else if ($_SESSION['role'] == 'mahasiswa') {
        header("Location: ../mahasiswa/dashboard.php");
    } else if ($_SESSION['role'] == 'kaprodi') {
        header("Location: ../kaprodi/dashboard.php");
    } else {
        session_destroy();
        session_start();
        $_SESSION['error_msg'] = "Role Tidak Dikenali!";
        header("Location: ../auth/login.php");
    }
    exit;
}

$error_msg = "";
$success_msg = "";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {
            
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['nama'] = $row['nama'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['fakultas'] = $row['fakultas'];
            $_SESSION['prodi'] = $row['prodi'];
            $_SESSION['foto_profil'] = isset($row['foto_profil']) ? $row['foto_profil'] : 'default.png';

            
            if ($row['role'] == 'dekan') {
                $redirect_url = '../dekan/dashboard.php';
            } else if ($row['role'] == 'pengurus_fakultas') {
                $redirect_url = '../pengurus_fakultas/dashboard.php';
            } else if ($row['role'] == 'mahasiswa') {
                $redirect_url = '../mahasiswa/dashboard.php';
            } else if ($row['role'] == 'kaprodi') {
                $redirect_url = '../kaprodi/dashboard.php';
            } else {
                unset($_SESSION['user_id']);
                session_destroy();
                session_start();
                $_SESSION['error_msg'] = "Role akses tidak valid!";
                header("Location: login.php");
                exit;
            }

            if(isset($_SESSION['user_id'])){

            ?>
            <!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
                <title>Login Sukses</title>
                <script src="https://cdn.tailwindcss.com"></script>
                <style>
                    .loader {
                        border: 4px solid #f3f3f3;
                        border-top: 4px solid #3498db;
                        border-radius: 50%;
                        width: 40px;
                        height: 40px;
                        animation: spin 1s linear infinite;
                    }
                    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                </style>
            </head>
            <body class="bg-slate-50 flex items-center justify-center min-h-screen">
                <div class="text-center animate-pulse">
                    <div class="flex justify-center mb-4">
                        <div class="loader border-t-indigo-600 border-indigo-100"></div>
                    </div>
                    <h2 class="text-xl font-bold text-slate-700">Login Berhasil!</h2>
                    <p class="text-slate-400 text-sm mt-2">Mengalihkan ke dashboard...</p>
                </div>
                <script>
                    setTimeout(() => {
                        window.location.href = '<?= $redirect_url ?>';
                    }, 1500);
                </script>
            </body>
            </html>
            <?php
            exit;
            } // Close if(isset($_SESSION['user_id']))
        } else {
            $_SESSION['error_msg'] = "Password salah!";
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['error_msg'] = "Username tidak ditemukan!";
        header("Location: login.php");
        exit;
    }
}

if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $error_msg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

$initial_view = isset($_GET['view']) && $_GET['view'] == 'register' ? 'register' : 'login';
?>

<?php include '../includes/header.php'; ?>

    <div id="toast-container" class="fixed top-6 right-6 z-[110] flex flex-col gap-3 pointer-events-none">
        <?php if($error_msg): ?>
        <div class="bg-white border-l-4 border-red-500 text-red-700 p-4 rounded-xl shadow-soft flex items-center gap-3 min-w-[300px] animate-slide-in pointer-events-auto">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <span class="text-sm font-bold"><?= $error_msg ?></span>
        </div>
        <?php endif; ?>
        <?php if($success_msg): ?>
        <div class="bg-white border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-xl shadow-soft flex items-center gap-3 min-w-[300px] animate-slide-in pointer-events-auto">
            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
            <span class="text-sm font-bold"><?= $success_msg ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div id="forgot-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-luxury-secondary/80 backdrop-blur-sm hidden px-4 py-4 transition-opacity">
        <div class="bg-white rounded-[2rem] shadow-2xl animate-popup max-w-sm w-full p-8 text-center relative overflow-hidden ring-1 ring-black/5">
            <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600">
                <i data-lucide="info" class="w-8 h-8"></i>
            </div>
            <h3 class="text-xl font-display font-bold text-luxury-secondary mb-2">Lupa Password?</h3>
            <p class="text-slate-500 mb-6 text-sm">Demi keamanan data fasilitas akademik Universitas Muhammadiyah Sorong, sistem belum mendukung *reset password* otomatis. Silakan kunjungi <b>Bagian Tata Usaha Fakultas</b> untuk mendapatkan kata sandi sementara Anda kembali.</p>
            <button onclick="closeForgotModal()" class="w-full py-3 bg-luxury-primary text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:bg-luxury-secondary transition-all cursor-pointer">Mengerti</button>
        </div>
    </div>

    <section id="auth-section" class="min-h-screen relative flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto py-12 bg-[#F0F4F8]">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-luxury-primary/5 blur-[100px] animate-float"></div>
            <div class="absolute top-[40%] -right-[10%] w-[40%] h-[40%] rounded-full bg-luxury-secondary/5 blur-[80px] animate-float" style="animation-delay: 2s"></div>
        </div>

        <div class="w-full max-w-5xl bg-white rounded-[2.5rem] shadow-2xl overflow-visible flex flex-col md:flex-row relative z-10 min-h-[600px] animate-popup">
            
            <div class="hidden md:flex md:w-1/2 relative bg-luxury-primary overflow-hidden rounded-l-[2.5rem] flex-col justify-between p-10 text-white">
                <div class="absolute inset-0 bg-gradient-to-b from-luxury-secondary/40 to-luxury-dark/90 z-0"></div>
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=2301&auto=format&fit=crop')] bg-cover bg-center mix-blend-overlay opacity-30 z-0"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center border border-white/30">
                            <i data-lucide="layers" class="text-white w-6 h-6"></i>
                        </div>
                        <span class="text-2xl font-display font-bold tracking-tight">SIFASKA</span>
                    </div>
                    <h2 class="text-4xl md:text-5xl font-display font-bold leading-tight mb-4">Sistem Pengelolaan <br>Inventaris & Fasilitas</h2>
                    <p class="text-white/80 font-light text-lg">Universitas Muhammadiyah Sorong — Platform digital terpadu untuk pengelolaan inventaris dan peminjaman fasilitas akademik berbasis website.</p>
                </div>
                <div class="relative z-10 mt-auto">
                    <div class="h-1 w-12 bg-white rounded-full"></div>
                </div>
            </div>

            <div class="w-full md:w-1/2 p-6 md:p-12 bg-white flex flex-col justify-center relative">
                
                <div id="view-login" class="<?= $initial_view == 'login' ? '' : 'hidden-section' ?> transition-all duration-500">
                    <div class="mb-10">
                        <h1 class="text-3xl font-display font-bold text-luxury-secondary mb-2">Akses SIFASKA</h1>
                        <p class="text-slate-400">Sistem Pengelolaan Inventaris dan Peminjaman Fasilitas Akademik Universitas Muhammadiyah Sorong.</p>
                    </div>

                    <form id="form-login" method="POST" action="" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-luxury-primary uppercase tracking-wider ml-1">Username / NIM</label>
                            <div class="relative group">
                                <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-luxury-primary transition-colors"></i>
                                <input type="text" name="username" required class="input-luxury w-full py-4 pl-12 pr-4 rounded-2xl text-luxury-dark font-medium placeholder:text-slate-300" placeholder="Masukkan NIM">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center ml-1">
                                <label class="text-xs font-bold text-luxury-primary uppercase tracking-wider">Password</label>
                                <button type="button" onclick="openForgotModal()" class="text-xs font-bold text-slate-400 hover:text-luxury-primary transition-colors cursor-pointer focus:outline-none">Lupa Password?</button>
                            </div>
                            <div class="relative group">
                                <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-luxury-primary transition-colors"></i>
                                <input type="password" name="password" required class="input-luxury w-full py-4 pl-12 pr-4 rounded-2xl text-luxury-dark font-medium placeholder:text-slate-300" placeholder="••••••••">
                            </div>
                        </div>
                        <button type="submit" name="login" class="w-full py-4 bg-luxury-secondary hover:bg-luxury-dark text-white rounded-2xl font-bold shadow-lg shadow-luxury-secondary/30 transform active:scale-[0.98] transition-all duration-300 flex items-center justify-center gap-2 group cursor-pointer">
                            <span>Masuk Sekarang</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </form>

                    <p class="mt-8 text-center text-slate-500">Belum punya akun? <button onclick="toggleAuthView('register')" class="text-luxury-primary font-bold hover:underline cursor-pointer">Daftar disini</button></p>
                </div>

                <div id="view-register" class="<?= $initial_view == 'register' ? '' : 'hidden-section' ?> transition-all duration-500">
                    <button onclick="toggleAuthView('login')" class="absolute top-8 left-8 text-slate-400 hover:text-luxury-primary transition-colors flex items-center gap-2 text-sm font-semibold cursor-pointer">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali
                    </button>

                    <div class="mb-4 mt-6">
                        <h1 class="text-3xl font-display font-bold text-luxury-secondary mb-2">Registrasi Mahasiswa</h1>
                        <p class="text-slate-400">Pastikan data diri sesuai dengan KTM Universitas Muhammadiyah Sorong Anda.</p>
                    </div>

                    <form id="form-register" method="POST" action="register.php" class="space-y-3">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Nama Lengkap</label>
                                <input type="text" name="nama" required class="input-luxury w-full py-3 px-4 rounded-xl text-sm" placeholder="Masukkan Nama Lengkap">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-luxury-primary uppercase ml-1">NIM</label>
                                <input type="text" name="nim" required class="input-luxury w-full py-3 px-4 rounded-xl text-sm" placeholder="Masukkan NIM">
                            </div>
                        </div>
                        
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Password</label>
                            <input type="password" name="password" required class="input-luxury w-full py-3 px-4 rounded-xl text-sm" placeholder="••••••••">
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Fakultas</label>
                            <select name="fakultas" id="reg-fakultas" required onchange="updateProdiDropdown('reg-fakultas', 'reg-prodi')" class="input-luxury w-full py-3 px-4 rounded-xl text-sm">
                                <option value="">-- Pilih Fakultas --</option>
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
                            <label class="text-xs font-bold text-luxury-primary uppercase ml-1">Program Studi</label>
                            <select name="prodi" id="reg-prodi" required class="input-luxury w-full py-3 px-4 rounded-xl text-sm">
                                <option value="">-- Pilih Program Studi --</option>
                            </select>
                        </div>

                        <button type="submit" name="register" class="w-full py-4 bg-luxury-primary hover:bg-luxury-secondary text-white rounded-2xl font-bold shadow-lg shadow-luxury-primary/30 transform active:scale-[0.98] transition-all duration-300 mt-2 cursor-pointer">
                            Verifikasi & Daftar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

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

        function toggleAuthView(viewId) {
            const loginView = document.getElementById('view-login');
            const registerView = document.getElementById('view-register');

            if(viewId === 'register') {
                loginView.classList.add('hidden-section');
                registerView.classList.remove('hidden-section');
            } else {
                registerView.classList.add('hidden-section');
                loginView.classList.remove('hidden-section');
            }
        }

        function openForgotModal() {
            document.getElementById('forgot-modal').classList.remove('hidden');
        }
        function closeForgotModal() {
            document.getElementById('forgot-modal').classList.add('hidden');
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
            prodiSelect.innerHTML = '<option value="">-- Pilih Program Studi --</option>';

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
    </script>
</body>
</html>
