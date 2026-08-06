<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Logout SIFASKA</title>
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
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="text-center animate-pulse">
        <div class="flex justify-center mb-4">
             <div class="loader border-t-indigo-600 border-indigo-100"></div>
        </div>
        <h2 class="text-xl font-bold text-slate-700">Mengakhiri Sesi...</h2>
        <p class="text-slate-400 text-sm mt-2">Anda akan dialihkan ke halaman login.</p>
    </div>

    <script>
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 1500);
    </script>
</body>
</html>
