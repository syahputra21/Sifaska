<?php
/**
 * Komponen Antarmuka Firebase Cloud Messaging (FCM) SIFASKA
 * Menyediakan Toast Notifikasi Real-time, Bell Notifikasi, dan Modal Pengujian FCM
 * untuk Demo Seminar Proposal / Skripsi UNAMIN Sorong.
 */
?>
<!-- Tombol Floating Bell Notifikasi FCM (Bisa diklik dari semua halaman Dashboard) -->
<div class="fixed bottom-6 right-6 z-[100]">
    <button onclick="openFCMModal()"
        class="w-14 h-14 rounded-full bg-gradient-to-r from-luxury-primary to-luxury-secondary hover:from-luxury-secondary hover:to-luxury-dark text-white shadow-2xl shadow-luxury-primary/40 flex items-center justify-center transition-all transform hover:scale-110 active:scale-95 cursor-pointer relative group"
        title="Pusat Notifikasi & Uji Coba FCM">
        <i data-lucide="bell" class="w-6 h-6 animate-pulse"></i>
        <!-- Badge Unread Counter -->
        <span id="fcm-badge-count"
            class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1.5 bg-red-500 text-white font-bold text-[11px] rounded-full flex items-center justify-center shadow-md border-2 border-white hidden animate-bounce">0</span>
        <!-- Tooltip Label -->
        <span
            class="absolute right-16 px-3 py-1.5 bg-slate-900/90 backdrop-blur-md text-white text-[11px] font-bold rounded-xl whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none shadow-lg">
            Pusat Pemberitahuan
        </span>
    </button>
</div>

<!-- Toast Pop-up Notifikasi FCM (Muncul Otomatis Saat Ada Pesan Baru) -->
<div id="fcm-toast-container"
    class="fixed top-6 right-6 z-[120] flex flex-col gap-3 pointer-events-none max-w-sm w-full"></div>

<!-- Elemen Audio Preloaded untuk Suara Notifikasi -->
<audio id="fcm-audio-sound" preload="auto"
    src="/SIFASKA/assets/notif.mp3"></audio>

<!-- Modal Pusat Notifikasi & Uji Coba FCM (Untuk Demo Skripsi) -->
<div id="fcm-demo-modal"
    class="fixed inset-0 z-[110] flex items-center justify-center bg-luxury-secondary/70 backdrop-blur-md hidden px-4 py-6">
    <div
        class="bg-white rounded-[2.5rem] shadow-2xl animate-popup max-w-lg w-full relative overflow-hidden ring-1 ring-slate-900/10">
        <!-- Header -->
        <div
            class="bg-gradient-to-tr from-luxury-secondary via-[#1E293B] to-luxury-primary px-8 py-6 text-white relative overflow-hidden">
            <div
                class="absolute -top-12 -right-12 w-40 h-40 bg-luxury-primary/30 rounded-full blur-2xl pointer-events-none">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold tracking-wider uppercase bg-white/10 backdrop-blur-md border border-white/15 text-luxury-accent mb-2">
                        <i data-lucide="bell-ring" class="w-3.5 h-3.5"></i>
                        SIFASKA UNAMIN Sorong
                    </span>
                    <h3 class="text-xl font-display font-bold">Pusat Pemberitahuan</h3>
                </div>
                <button onclick="closeFCMModal()"
                    class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="p-7 space-y-6 max-h-[75vh] overflow-y-auto">
            <!-- Status Koneksi FCM -->
            <div class="bg-emerald-50 border border-emerald-200/80 rounded-2xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></div>
                    <div>
                        <p class="text-xs font-bold text-emerald-900">Sistem Notifikasi Terhubung</p>
                        <p class="text-[11px] text-emerald-700">Anda akan menerima pembaruan secara real-time.</p>
                    </div>
                </div>
                <span
                    class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-lg">Aktif</span>
            </div>



            <!-- Riwayat Notifikasi Terakhir -->
            <div class="space-y-3 pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Riwayat Pesan Masuk</label>
                    <button type="button" onclick="markAllFCMRead()"
                        class="text-xs text-luxury-primary font-bold hover:underline cursor-pointer">Tandai Semua
                        Dibaca</button>
                </div>
                <div id="fcm-notifications-list" class="space-y-2.5 max-h-48 overflow-y-auto pr-1">
                    <p class="text-xs text-slate-400 text-center py-4">Memuat riwayat notifikasi...</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeFCMModal()"
                class="px-6 py-2.5 bg-luxury-secondary text-white text-xs font-bold rounded-xl hover:bg-luxury-dark transition-all cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- Script Klien FCM Real-Time -->
<script>
    let unreadFCMCount = 0;
    let lastNotifId = 0;

    function initFCMClient() {
        // 1. Minta izin notifikasi langsung dari browser OS (kembali ke cara asli)
        if ('Notification' in window) {
            Notification.requestPermission().then(function (permission) {
                if (permission === 'granted') {
                    console.log('[FCM] Izin notifikasi browser diberikan.');
                    registerFCMServiceWorker();
                }
            });
        }

        // 2. Muat riwayat notifikasi dari server
        fetchFCMNotifications(true);

        // 3. Interval periksa notifikasi baru setiap 5 detik agar lebih responsif (terutama di Android)
        setInterval(() => fetchFCMNotifications(false), 5000);
    }

    function registerFCMServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/SIFASKA/firebase-messaging-sw.js')
                .then(function (reg) {
                    console.log('[FCM] Service Worker berhasil didaftarkan:', reg.scope);
                    // Daftarkan token FCM ke database
                    saveFCMToken('DEMO_WEB_TOKEN_' + Math.random().toString(36).substring(2, 10));
                })
                .catch(function (err) {
                    console.warn('[FCM] Pendaftaran Service Worker gagal (localhost OK):', err);
                    saveFCMToken('DEMO_WEB_TOKEN_LOCAL_' + Math.random().toString(36).substring(2, 10));
                });
        }
    }

    function saveFCMToken(token) {
        const formData = new FormData();
        formData.append('action', 'save_token');
        formData.append('token', token);
        formData.append('device_type', 'web');

        fetch('/SIFASKA/api/fcm_api.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json()).then(data => {
            console.log('[FCM API]', data.message);
        }).catch(err => console.error('[FCM API Error]', err));
    }

    function fetchFCMNotifications(isInitialLoad = false) {
        fetch('/SIFASKA/api/fcm_api.php?action=get_notifications')
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    unreadFCMCount = data.unread_count;
                    updateFCMBadge(unreadFCMCount);
                    renderFCMList(data.data);

                    // Cek jika ada notifikasi baru (untuk trigger popup otomatis)
                    if (data.data.length > 0) {
                        const currentLatestId = parseInt(data.data[0].id);
                        
                        if (isInitialLoad) {
                            // Cukup simpan ID terakhir saat load pertama kali
                            lastNotifId = currentLatestId;
                        } else {
                            // Saat polling interval, jika ada ID yang lebih baru (atau jika sebelumnya 0)
                            if (currentLatestId > lastNotifId) {
                                // Ada notif baru yang masuk! Trigger toast & sound
                                const newNotif = data.data[0];
                                showFCMToastPopup(newNotif.title, newNotif.message, newNotif.type);
                                
                                try {
                                    const audioEl = document.getElementById('fcm-audio-sound');
                                    if (audioEl) {
                                        audioEl.currentTime = 0;
                                        // Coba putar, tapi abaikan error jika diblokir auto-play browser HP
                                        audioEl.play().catch(e => console.log('Auto-play diblokir browser: ', e));
                                    }
                                } catch (e) { }

                                if ('Notification' in window && Notification.permission === 'granted') {
                                    new Notification(newNotif.title, {
                                        body: newNotif.message,
                                        icon: '/SIFASKA/assets/icon-fcm.png'
                                    });
                                }
                                
                                // Update ID terakhir
                                lastNotifId = currentLatestId;

                                // Otomatis refresh tabel dan data secara *siluman* (tanpa reload halaman)
                                refreshDashboardDataSilently();
                            }
                        }
                    }
                }
            })
            .catch(err => console.error('[FCM API Error]', err));
    }

    function updateFCMBadge(count) {
        const badgeEl = document.getElementById('fcm-badge-count');
        if (badgeEl) {
            if (count > 0) {
                badgeEl.textContent = count;
                badgeEl.classList.remove('hidden');
            } else {
                badgeEl.classList.add('hidden');
            }
        }
    }

    function renderFCMList(items) {
        const listEl = document.getElementById('fcm-notifications-list');
        if (!listEl) return;

        if (!items || items.length === 0) {
            listEl.innerHTML = '<p class="text-xs text-slate-400 text-center py-4">Belum ada riwayat notifikasi</p>';
            return;
        }

        let html = '';
        items.forEach(item => {
            const bgClass = item.is_read == 0 ? 'bg-blue-50/70 border-blue-200' : 'bg-slate-50 border-slate-200/60';
            const iconColor = item.type === 'success' ? 'text-emerald-600' : (item.type === 'error' ? 'text-red-600' : 'text-blue-600');
            const iconName = item.type === 'success' ? 'check-circle' : (item.type === 'error' ? 'x-circle' : 'bell');

            html += `
            <div class="p-4 rounded-xl border ${bgClass} flex items-start gap-3.5">
                <i data-lucide="${iconName}" class="w-5 h-5 ${iconColor} shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-bold text-slate-800">${item.title}</p>
                        <span class="text-xs font-mono text-slate-400">${item.created_at.split(' ')[1] || ''}</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1 leading-relaxed">${item.message}</p>
                </div>
            </div>
        `;
        });

        listEl.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // Fungsi canggih untuk memperbarui data tabel dan kartu summary 
    // di latar belakang tanpa mengganggu interaksi/scroll pengguna
    function refreshDashboardDataSilently() {
        fetch(window.location.href)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Daftar semua awalan ID tab konten yang ada di seluruh dashboard SIFASKA
                const contentIds = [
                    'content-dashboard', 'content-fasilitas', 'content-riwayat',
                    'admin-content-dashboard', 'admin-content-persetujuan', 'admin-content-inventaris', 
                    'admin-content-users', 'admin-content-pengajuan', 'admin-content-riwayat', 'admin-content-keluhan'
                ];

                // Ganti hanya bagian dalam (innerHTML) dari masing-masing tab
                contentIds.forEach(id => {
                    const oldEl = document.getElementById(id);
                    const newEl = doc.getElementById(id);
                    if (oldEl && newEl) {
                        oldEl.innerHTML = newEl.innerHTML;
                    }
                });

                // Render ulang icon Lucide pada elemen yang baru di-refresh
                if (typeof lucide !== 'undefined') lucide.createIcons();
                
                // Render ulang tabel paginasi agar data terpotong dengan rapi
                if (typeof filterTable === 'function') {
                    const tbodys = document.querySelectorAll('tbody[id]');
                    tbodys.forEach(tb => {
                        const tableId = tb.id;
                        try {
                            filterTable('', tableId); // Panggil ulang filter (tanpa pencarian)
                        } catch(e) {}
                    });
                }
            })
            .catch(err => console.log('[Silent Refresh]', err));
    }


    function showFCMToastPopup(title, message, type) {
        const container = document.getElementById('fcm-toast-container');
        if (!container) return;

        const id = 'fcm-toast-' + Date.now();
        const borderClass = type === 'success' ? 'border-l-4 border-emerald-500 bg-emerald-50' : (type === 'error' ? 'border-l-4 border-red-500 bg-red-50' : 'border-l-4 border-luxury-primary bg-blue-50');
        const iconName = type === 'success' ? 'check-circle' : (type === 'error' ? 'x-circle' : 'bell');

        const toastHTML = `
        <div id="${id}" class="bg-white border border-slate-200/80 ${borderClass} p-5 rounded-2xl shadow-2xl pointer-events-auto flex items-start gap-4 transform translate-y-0 opacity-100 transition-all duration-300">
            <div class="p-2.5 rounded-xl bg-white shadow-sm shrink-0">
                <i data-lucide="${iconName}" class="w-6 h-6 text-luxury-primary"></i>
            </div>
            <div class="flex-1 pr-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-luxury-primary bg-luxury-primary/10 px-2.5 py-1 rounded">SIFASKA Update</span>
                    <span class="text-xs font-mono text-slate-400">Baru saja</span>
                </div>
                <p class="text-base font-bold text-slate-900 mt-2">${title}</p>
                <p class="text-sm text-slate-600 mt-1 leading-relaxed">${message}</p>
            </div>
            <button onclick="document.getElementById('${id}').remove()" class="text-slate-400 hover:text-slate-600 cursor-pointer p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
    `;

        container.insertAdjacentHTML('afterbegin', toastHTML);
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Hapus otomatis setelah 6 detik
        setTimeout(() => {
            const el = document.getElementById(id);
            if (el) {
                el.style.opacity = '0';
                el.style.transform = 'translateY(-10px)';
                setTimeout(() => el.remove(), 300);
            }
        }, 6000);
    }

    function openFCMModal() {
        document.getElementById('fcm-demo-modal').classList.remove('hidden');
        fetchFCMNotifications();
    }

    function closeFCMModal() {
        document.getElementById('fcm-demo-modal').classList.add('hidden');
    }

    function markAllFCMRead() {
        fetch('/SIFASKA/api/fcm_api.php?action=mark_read')
            .then(res => res.json())
            .then(data => {
                unreadFCMCount = 0;
                updateFCMBadge(0);
                fetchFCMNotifications();
            });
    }

    // Trik khusus: mainkan audio sangat pelan saat user pertama kali menyentuh layar
    // Ini WAJIB untuk Android Chrome agar suara tidak diblokir saat notifikasi masuk.
    let isAudioUnlocked = false;
    function globalAudioUnlocker() {
        if (isAudioUnlocked) return;
        try {
            const audioEl = document.getElementById('fcm-audio-sound');
            if (audioEl) {
                audioEl.volume = 0.01;
                audioEl.play().then(() => {
                    audioEl.pause();
                    audioEl.currentTime = 0;
                    audioEl.volume = 1.0;
                    isAudioUnlocked = true;
                    document.removeEventListener('touchstart', globalAudioUnlocker);
                    document.removeEventListener('click', globalAudioUnlocker);
                }).catch(e => {});
            }
        } catch(e) {}
    }

    document.addEventListener('DOMContentLoaded', function () {
        initFCMClient();
        
        // Listen ke sentuhan pertama user di layar HP / klik mouse di PC
        document.addEventListener('touchstart', globalAudioUnlocker, { once: true });
        document.addEventListener('click', globalAudioUnlocker, { once: true });
    });
</script>
