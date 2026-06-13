<?php
// ============================================================
// helpers/auth_guard.php
// Fungsi-fungsi autentikasi & otorisasi (RBAC Guard)
// Referensi: Materi Pertemuan 9 — Bagian 2.6 RBAC
// ============================================================

/**
 * Cek apakah user sudah login
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Guard: Halaman hanya untuk user yang sudah login.
 * Jika belum login, redirect ke halaman login.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        // Simpan URL yang ingin diakses agar bisa redirect setelah login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses halaman ini.';
        header('Location: /simpekabjmk/login.php');
        exit;
    }
}

/**
 * Guard: Halaman hanya untuk role tertentu.
 * Jika role tidak sesuai, tampilkan 403 Forbidden.
 *
 * @param array $allowedRoles Contoh: ['admin'], ['admin', 'manager']
 */
function requireRole(array $allowedRoles): void
{
    requireLogin(); // Pastikan sudah login dulu

    $userRole = $_SESSION['role'] ?? null;

    if (!in_array($userRole, $allowedRoles, true)) {
        // Log upaya akses tidak sah
        logActivity(
            $_SESSION['user_id'] ?? null,
            'AKSES_DITOLAK',
            '403 Forbidden — Mencoba akses: ' . ($_SERVER['REQUEST_URI'] ?? ''),
            'critical'
        );

        http_response_code(403);
        include __DIR__ . '/../partials/head.php';
        echo renderForbiddenPage($userRole, $allowedRoles);
        include __DIR__ . '/../partials/footer.php';
        exit;
    }
}

/**
 * Tampilkan halaman 403 Forbidden dengan styling Jomokerto Obsidian
 */
function renderForbiddenPage(?string $userRole, array $allowedRoles): string
{
    $rolesText = implode(' / ', array_map('strtoupper', $allowedRoles));
    return '
    <div style="display:flex;align-items:center;justify-content:center;min-height:80vh;">
      <div style="text-align:center;padding:48px;background:#ffffff;border:1px solid #eaecf0;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,0.05);max-width:500px;">
        <div style="font-size:80px;margin-bottom:16px;">🚫</div>
        <h1 style="color:#ef4444;font-size:48px;font-weight:800;margin:0 0 8px;">403</h1>
        <h2 style="color:#1a1d1f;font-size:24px;font-weight:700;margin:0 0 16px;">Akses Ditolak</h2>
        <p style="color:#64748b;margin-bottom:8px;font-size:15px;">Role Anda: <strong style="color:#3b82f6;background:#eff6ff;padding:4px 8px;border-radius:6px;">' . strtoupper(htmlspecialchars($userRole ?? '')) . '</strong></p>
        <p style="color:#64748b;margin-bottom:32px;font-size:15px;">Halaman ini hanya untuk: <strong style="color:#f59e0b;background:#fffbeb;padding:4px 8px;border-radius:6px;">' . htmlspecialchars($rolesText) . '</strong></p>
        <a href="/simpekabjmk/dashboard.php" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;justify-content:center;width:100%;">
          <span class="material-symbols-outlined">arrow_back</span>
          Kembali ke Dashboard
        </a>
      </div>
    </div>';
}

/**
 * Cek apakah user memiliki role tertentu (tanpa redirect/die)
 */
function hasRole(string $role): bool
{
    return ($_SESSION['role'] ?? null) === $role;
}

/**
 * Cek apakah user memiliki salah satu dari beberapa role
 */
function hasAnyRole(array $roles): bool
{
    return in_array($_SESSION['role'] ?? null, $roles, true);
}

/**
 * Dapatkan data user yang sedang login dari session
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'nama'  => $_SESSION['nama']  ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role'  => $_SESSION['role']  ?? '',
    ];
}
