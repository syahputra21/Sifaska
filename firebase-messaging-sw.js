/**
 * Firebase Cloud Messaging Service Worker - SIFASKA UNAMIN Sorong
 * Bertanggung jawab menerima notifikasi push latar belakang (background notification)
 * dan menampilkan pop-up browser notification.
 */

// Import library Firebase Messaging SW dari CDN Google
importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js');

// Konfigurasi Firebase Anda (Akan diganti dengan config Firebase produksi dari Google Console)
const firebaseConfig = {
    apiKey: "AIzaSyDemoKeyUNAMINSorongFCM01",
    authDomain: "sifaska-UNAMIN.firebaseapp.com",
    projectId: "sifaska-UNAMIN",
    storageBucket: "sifaska-UNAMIN.appspot.com",
    messagingSenderId: "123456789012",
    appId: "1:123456789012:web:demo123456789"
};

try {
    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();

    // Event listener untuk menangani pesan FCM latar belakang
    messaging.onBackgroundMessage(function(payload) {
        console.log('[firebase-messaging-sw.js] Menerima pesan FCM background:', payload);

        const notificationTitle = payload.notification ? payload.notification.title : '🔔 SIFASKA Notifikasi';
        const notificationOptions = {
            body: payload.notification ? payload.notification.body : 'Ada pengajuan peminjaman baru.',
            icon: '/sifaska-pwm/assets/icon-fcm.png',
            badge: '/sifaska-pwm/assets/icon-fcm.png',
            data: payload.data || {},
            vibrate: [200, 100, 200, 100, 200, 100, 200]
        };

        self.registration.showNotification(notificationTitle, notificationOptions);
    });
} catch (e) {
    console.log('[FCM SW] Service worker berjalan dalam mode simulasi lokal untuk skripsi:', e.message);
}

// Menangani klik pada notifikasi browser
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            if (clientList.length > 0) {
                let client = clientList[0];
                for (let i = 0; i < clientList.length; i++) {
                    if (clientList[i].focused) {
                        client = clientList[i];
                    }
                }
                return client.focus();
            }
            return clients.openWindow('http://localhost/sifaska-pwm/');
        })
    );
});
