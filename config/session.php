<?php
// ============================================================
// config/session.php
// Konfigurasi session yang aman (WAJIB dipanggil sebelum session_start)
// Referensi: Materi Pertemuan 9 — Konfigurasi Session Aman
// ============================================================

// Pastikan belum ada output sebelumnya
if (headers_sent()) {
    die('ERROR: Session config harus dipanggil sebelum output apapun!');
}

// 1. Session cookie hanya bisa diakses PHP, bukan JavaScript (cegah XSS)
ini_set('session.cookie_httponly', '1');

// 2. Hanya kirim session cookie via HTTPS (aktifkan di production)
// ini_set('session.cookie_secure', '1');  // Uncomment jika sudah HTTPS

// 3. Cegah CSRF via cookie
ini_set('session.cookie_samesite', 'Strict');

// 4. Tolak session ID yang tidak ada di server (cegah Session Fixation)
ini_set('session.use_strict_mode', '1');

// 5. Jangan terima session ID dari URL (wajib!)
ini_set('session.use_only_cookies', '1');

// 6. Lifetime session: 1 jam (3600 detik)
ini_set('session.gc_maxlifetime', '3600');

// 7. Interval GC (garbage collector) — setiap 30 menit
ini_set('session.gc_divisor', '100');
ini_set('session.gc_probability', '1');

// Nama session custom (lebih aman dari default PHPSESSID)
session_name('SIMPEG_SESS');

// Mulai session
session_start();

// ============================================================
// Session timeout check
// Jika sudah login dan session sudah expire (tidak ada aktivitas > 1 jam)
// ============================================================
if (isset($_SESSION['user_id']) && isset($_SESSION['login_time'])) {
    $maxLifetime = 3600; // 1 jam
    if ((time() - $_SESSION['login_time']) > $maxLifetime) {
        // Session expired — destroy dan redirect
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Strict',
            ]);
        }
        session_destroy();
        header('Location: /simpekabjmk/login.php?timeout=1');
        exit;
    }
    // Perbarui last activity
    $_SESSION['login_time'] = time();
}

// ============================================================
// Periodic session ID regeneration (setiap 5 menit)
// Mencegah session hijacking jangka panjang
// ============================================================
if (isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}
