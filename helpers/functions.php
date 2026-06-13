<?php
// ============================================================
// helpers/functions.php
// Fungsi-fungsi utilitas: CSRF, flash message, activity log, helpers
// ============================================================

// ============================================================
// CSRF TOKEN MANAGEMENT
// Referensi: Materi Pertemuan 9 — CSRF Protection
// ============================================================

/**
 * Generate CSRF token baru dan simpan di session.
 * Gunakan di setiap halaman yang memiliki form POST.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validasi CSRF token dari form POST.
 * Gunakan hash_equals() untuk mencegah timing attack.
 */
function validateCsrfToken(): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postToken    = $_POST['csrf_token']    ?? '';

    if (!hash_equals($sessionToken, $postToken)) {
        logActivity(
            $_SESSION['user_id'] ?? null,
            'CSRF_VIOLATION',
            'CSRF token tidak valid di: ' . ($_SERVER['REQUEST_URI'] ?? ''),
            'critical'
        );
        die('<p style="color:red;font-family:monospace;">⚠ CSRF token tidak valid. Silakan muat ulang halaman.</p>');
    }

    // Regenerate token setelah validasi (one-time use per form)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Render hidden input CSRF token untuk dimasukkan ke dalam form
 */
function csrfInput(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// ============================================================
// FLASH MESSAGES
// ============================================================

/**
 * Set flash message (hanya bertahan 1 request)
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

/**
 * Render dan hapus flash messages
 */
function renderFlash(): string
{
    if (empty($_SESSION['flash'])) return '';

    $html = '';
    foreach ($_SESSION['flash'] as $type => $message) {
        $class = match($type) {
            'success' => 'alert-success',
            'error'   => 'alert-error',
            'warning' => 'alert-warning',
            default   => 'alert-info',
        };
        $icon = match($type) {
            'success' => 'check_circle',
            'error'   => 'error',
            'warning' => 'warning',
            default   => 'info',
        };
        $html .= '<div class="alert ' . $class . '">'
               . '<span class="material-symbols-outlined">' . $icon . '</span>'
               . htmlspecialchars($message)
               . '</div>';
    }

    unset($_SESSION['flash']); // Hapus setelah ditampilkan
    return $html;
}

// ============================================================
// ACTIVITY LOG
// ============================================================

/**
 * Catat aksi ke tabel activity_log
 *
 * @param int|null $userId   User yang melakukan aksi (null = anonim/sistem)
 * @param string   $aksi     Nama aksi (contoh: 'LOGIN_SUCCESS', 'ABSENSI_CHECKIN')
 * @param string   $detail   Detail tambahan
 * @param string   $level    'info' | 'warning' | 'critical'
 */
function logActivity(?int $userId, string $aksi, string $detail = '', string $level = 'info'): void
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, aksi, detail, ip_address, user_agent, level)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $aksi,
            $detail,
            getClientIp(),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $level,
        ]);
    } catch (PDOException $e) {
        // Log ke file jika DB tidak available
        error_log('[SIMPEG ActivityLog Error] ' . $e->getMessage());
    }
}

// ============================================================
// REMEMBER ME
// Referensi: Materi Pertemuan 9 — Bagian 2.7 Remember Me
// ============================================================

/**
 * Set remember me cookie + simpan hash token ke database
 * Dipanggil setelah login sukses jika checkbox "Ingat Saya" dicentang
 */
function setRememberMeToken(int $userId): void
{
    // 1. Generate token plain (tidak disimpan di DB)
    $tokenPlain = bin2hex(random_bytes(32));

    // 2. Hash token untuk disimpan di DB
    $tokenHash = hash('sha256', $tokenPlain);

    // 3. Simpan hash ke database
    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
    $stmt->execute([$tokenHash, $userId]);

    // 4. Set cookie dengan format: user_id:token_plain
    setcookie('simpeg_remember', $userId . ':' . $tokenPlain, [
        'expires'  => time() + (30 * 24 * 60 * 60), // 30 hari
        'path'     => '/simpekabjmk/',
        'secure'   => false,   // true jika HTTPS di production
        'httponly' => true,    // JavaScript tidak bisa baca!
        'samesite' => 'Strict',
    ]);
}

/**
 * Hapus remember me token (saat logout)
 */
function clearRememberMeToken(?int $userId = null): void
{
    // Hapus cookie
    setcookie('simpeg_remember', '', [
        'expires'  => time() - 3600,
        'path'     => '/simpekabjmk/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    // Hapus token dari DB
    if ($userId) {
        try {
            $pdo = getPDO();
            $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?")
                ->execute([$userId]);
        } catch (PDOException $e) {
            error_log('[SIMPEG RememberMe Error] ' . $e->getMessage());
        }
    }
}

/**
 * Cek dan proses remember me cookie
 * Dipanggil di awal setiap halaman (via session.php)
 * Jika token valid → auto-login + rotasi token
 */
function checkRememberMe(): void
{
    // Sudah login? Skip
    if (isLoggedIn()) return;

    // Cookie tidak ada? Skip
    if (empty($_COOKIE['simpeg_remember'])) return;

    // Parse cookie: "user_id:token_plain"
    $parts = explode(':', $_COOKIE['simpeg_remember'], 2);
    if (count($parts) !== 2) {
        clearRememberMeToken();
        return;
    }

    [$userId, $tokenPlain] = $parts;
    $userId = (int) $userId;

    if ($userId <= 0 || empty($tokenPlain)) {
        clearRememberMeToken();
        return;
    }

    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT id, nama, email, role, remember_token FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || empty($user['remember_token'])) {
            clearRememberMeToken();
            return;
        }

        // ⚠ Gunakan hash_equals() untuk mencegah timing attack!
        $tokenHash = hash('sha256', $tokenPlain);
        if (!hash_equals($user['remember_token'], $tokenHash)) {
            clearRememberMeToken($userId);
            logActivity($userId, 'REMEMBER_TOKEN_INVALID', 'Token tidak cocok — kemungkinan replay attack', 'critical');
            return;
        }

        // ✅ Token valid — auto login!
        session_regenerate_id(true); // Cegah session fixation

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['nama']       = $user['nama'];
        $_SESSION['email']      = $user['email'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = getClientIp();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['from_remember'] = true; // Flag: login dari remember cookie

        // 🔥 TOKEN ROTATION — generate token baru untuk mencegah replay attack
        setRememberMeToken($user['id']);

        logActivity($user['id'], 'AUTO_LOGIN', 'Login otomatis via remember token', 'info');

    } catch (PDOException $e) {
        error_log('[SIMPEG RememberMe Check Error] ' . $e->getMessage());
    }
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Dapatkan IP address client (dengan penanganan proxy)
 */
function getClientIp(): string
{
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Output string yang sudah di-escape (cegah XSS)
 */
function e(?string $string): string
{
    return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect ke URL dan stop eksekusi
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Format tanggal ke format Indonesia
 */
function formatTanggalId(string $tanggal): string
{
    $bulan = [
        1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
        5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
        9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
    ];
    $ts = strtotime($tanggal);
    return date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
}

/**
 * Format durasi menit ke "Xj Ym"
 */
function formatDurasi(?int $menit): string
{
    if ($menit === null) return '-';
    $j = intdiv($menit, 60);
    $m = $menit % 60;
    return "{$j}j {$m}m";
}

/**
 * Dapatkan inisial dari nama (maks 2 huruf)
 */
function getInisial(string $nama): string
{
    $parts = explode(' ', trim($nama));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return $initials;
}

/**
 * Render badge role dengan styling Jomokerto Obsidian
 */
function roleBadge(string $role): string
{
    $map = [
        'super_admin'  => ['label'=>'Super Admin',  'class'=>'badge-admin',    'icon'=>'security'],
        'eksekutif'    => ['label'=>'Eksekutif',    'class'=>'badge-manager',  'icon'=>'leaderboard'],
        'admin_bkpsdm' => ['label'=>'Admin BKPSDM', 'class'=>'badge-manager',  'icon'=>'manage_accounts'],
        'atasan'       => ['label'=>'Atasan',       'class'=>'badge-manager',  'icon'=>'supervisor_account'],
        'pegawai'      => ['label'=>'Pegawai',      'class'=>'badge-karyawan', 'icon'=>'badge'],
    ];
    $r = $map[$role] ?? ['label'=>ucfirst($role), 'class'=>'badge-karyawan', 'icon'=>'person'];
    return '<span class="badge ' . $r['class'] . '">'
         . '<span class="material-symbols-outlined" style="font-size:12px;">' . $r['icon'] . '</span> '
         . $r['label'] . '</span>';
}

/**
 * Render badge status absensi
 */
function absensiBadge(string $status): string
{
    $map = [
        'hadir'     => '<span class="badge badge-active">Hadir</span>',
        'terlambat' => '<span class="badge badge-warning">Terlambat</span>',
        'alpha'     => '<span class="badge badge-danger">Alpha</span>',
        'izin'      => '<span class="badge badge-info">Izin</span>',
        'sakit'     => '<span class="badge badge-secondary">Sakit</span>',
        'menunggu_konfirmasi' => '<span class="badge badge-warning" style="background:#fffbeb;color:#d97706;border:1px solid #fcd34d;"><span class="material-symbols-outlined" style="font-size:14px;">schedule</span> Menunggu Konfirmasi Admin</span>',
    ];
    return $map[$status] ?? '<span class="badge">' . e($status) . '</span>';
}

/**
 * Render badge status pegawai
 */
function pegawaiBadge(string $status): string
{
    $map = [
        'aktif'    => '<span style="display:flex;align-items:center;gap:6px;"><span class="dot-green"></span><span style="color:#bbc9cf;font-size:13px;">Aktif</span></span>',
        'nonaktif' => '<span style="display:flex;align-items:center;gap:6px;"><span class="dot-gray"></span><span style="color:#859399;font-size:13px;">Nonaktif</span></span>',
        'cuti'     => '<span style="display:flex;align-items:center;gap:6px;"><span class="dot-yellow"></span><span style="color:#ffcc00;font-size:13px;">Cuti</span></span>',
    ];
    return $map[$status] ?? $status;
}

/**
 * Validasi: apakah string kosong/blank?
 */
function isEmpty(?string $val): bool
{
    return $val === null || trim($val) === '';
}
