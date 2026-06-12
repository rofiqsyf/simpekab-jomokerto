<?php
// ============================================================
// logout.php — Handler Logout
// Implementasi: Materi Pertemuan 9 — Bagian 2.5 Logout Aman
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/helpers/auth_guard.php';

// Log aktivitas sebelum menghancurkan session
if (isLoggedIn()) {
    logActivity(
        $_SESSION['user_id'],
        'LOGOUT',
        'User logout — session dihancurkan',
        'info'
    );

    // Hapus remember me token dari DB + cookie
    clearRememberMeToken($_SESSION['user_id']);
}

// 1. Kosongkan semua variabel session
$_SESSION = [];

// 2. Hapus session cookie dari browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Strict',
        ]
    );
}

// 3. Hancurkan session di server
session_destroy();

// 4. Redirect ke halaman login
header('Location: /simpeg_mini/login.php');
exit;
