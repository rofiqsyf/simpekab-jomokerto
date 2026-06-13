<?php
// ============================================================
// keamanan.php — Brankas Keamanan (Admin Only)
// Matriks RBAC, kebijakan kriptografi, log sesi
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireRole(['super_admin']); // Admin Only!

$currentPage = 'keamanan';
$pageTitle   = 'Brankas Keamanan';

// Statistik keamanan
$totalUsers   = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$lockedUsers  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE locked_until > NOW()")->fetchColumn();
$rememberTokens = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE remember_token IS NOT NULL")->fetchColumn();

// Log terbaru
$stmtLog = $pdo->prepare("
    SELECT al.*, u.email, u.nama
    FROM activity_log al
    LEFT JOIN users u ON u.id = al.user_id
    ORDER BY al.created_at DESC
    LIMIT 5
");
$stmtLog->execute();
$recentLogs = $stmtLog->fetchAll();

// Akun terkunci
$stmtLocked = $pdo->prepare("
    SELECT u.id, u.nama, u.email, u.role, u.login_attempts, u.locked_until
    FROM users u
    WHERE u.locked_until > NOW()
    ORDER BY u.locked_until DESC
");
$stmtLocked->execute();
$lockedAccounts = $stmtLocked->fetchAll();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      <?= renderFlash() ?>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;">
        <div>
          <h1 class="section-title" style="display:flex;align-items:center;gap:12px;">
            Brankas Keamanan
            <span class="material-symbols-outlined" style="color:#0ea5e9;font-size:32px;">shield_lock</span>
          </h1>
          <p class="section-subtitle">Manajemen RBAC, kebijakan kriptografi, & log sesi real-time</p>
        </div>
        <span class="badge badge-active" style="display:flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:600;box-shadow:0 4px 10px rgba(16,185,129,0.1);">
          <span class="dot-green animate-ping-slow"></span> Sinkronisasi Langsung
        </span>
      </div>

      <!-- Security Metrics -->
      <div style="display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:32px;">
        <?php foreach ([
          ['Total User',     $totalUsers,       '#3b82f6', '#eff6ff', 'group'],
          ['Akun Terkunci',  $lockedUsers,      $lockedUsers>0?'#ef4444':'#10b981', $lockedUsers>0?'#fef2f2':'#f0fdf4', 'lock'],
          ['Remember Token', $rememberTokens,   '#f59e0b', '#fffbeb', 'cookie'],
          ['CSRF Active',    'Aktif',           '#10b981', '#f0fdf4', 'token'],
        ] as [$l,$v,$c,$bg,$ic]): ?>
        <div class="card" style="display:flex;align-items:center;gap:16px;padding:20px;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <div style="width:56px;height:56px;border-radius:16px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;color:<?= $c ?>;flex-shrink:0;">
            <span class="material-symbols-outlined" style="font-size:28px;"><?= $ic ?></span>
          </div>
          <div>
            <div style="color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;"><?= $l ?></div>
            <div style="font-size:24px;font-weight:800;color:#1a1d1f;"><?= e($v) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div style="display:grid;gap:24px;grid-template-columns:1fr;@media(min-width:1024px){grid-template-columns:2fr 1fr;}margin-bottom:24px;">

        <!-- RBAC Matrix -->
        <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <div style="padding:20px 24px;border-bottom:1px solid #eaecf0;display:flex;align-items:center;gap:10px;background:#ffffff;">
            <span class="material-symbols-outlined" style="color:#8b5cf6;background:#f5f3ff;padding:8px;border-radius:12px;">policy</span>
            <h2 style="font-size:18px;font-weight:700;color:#1a1d1f;">Matriks Izin RBAC</h2>
          </div>
          <div style="overflow-x:auto;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Kapabilitas</th>
                  <th style="text-align:center;">Super Admin</th>
                  <th style="text-align:center;">Eks. / BKPSDM</th>
                  <th style="text-align:center;">Atasan</th>
                  <th style="text-align:center;">Pegawai</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $matrix = [
                  ['Lihat Profil Sendiri',        true,  true,  true,  true],
                  ['Input Absensi Harian',         true,  true,  true,  true],
                  ['Ganti Password Sendiri',       true,  true,  true,  true],
                  ['Lihat Absensi Tim (Divisi)',   true,  false, true,  false],
                  ['CRUD Data Pegawai',            true,  true,  false, false],
                  ['Lihat Semua Absensi',          true,  true,  false, false],
                  ['Reset Password Pegawai',       true,  true,  false, false],
                  ['Kelola Brankas Keamanan',      true,  false, false, false],
                  ['Akses Log Aktivitas Sistem',   true,  false, false, false],
                ];
                foreach ($matrix as [$kap, $admin, $bkpsdm, $atasan, $kry]):
                  $icon = fn($v) => $v
                    ? '<span class="material-symbols-outlined" style="color:#10b981;font-size:24px;" font-variation-settings="\'FILL\' 1">check_circle</span>'
                    : '<span class="material-symbols-outlined" style="color:#cbd5e1;font-size:24px;">cancel</span>';
                ?>
                <tr>
                  <td style="color:#475569;font-size:14px;font-weight:500;"><?= e($kap) ?></td>
                  <td style="text-align:center;"><?= $icon($admin) ?></td>
                  <td style="text-align:center;"><?= $icon($bkpsdm) ?></td>
                  <td style="text-align:center;"><?= $icon($atasan) ?></td>
                  <td style="text-align:center;"><?= $icon($kry) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Kebijakan Kriptografi -->
        <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <h2 style="font-size:18px;font-weight:700;color:#1a1d1f;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
            <span class="material-symbols-outlined" style="color:#0ea5e9;background:#e0f2fe;padding:8px;border-radius:12px;">key</span>
            Kebijakan Kripto
          </h2>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ([
              ['Bcrypt Cost Factor',   '12 (OWASP)',      '#10b981'],
              ['Algoritma Default',    'PASSWORD_BCRYPT',  '#3b82f6'],
              ['CSRF Token',           'random_bytes(32)', '#10b981'],
              ['Session Name',         'SIMPEG_SESS',      '#3b82f6'],
              ['Cookie HttpOnly',      'true',             '#10b981'],
              ['Cookie SameSite',      'Strict',           '#10b981'],
              ['Session Timeout',      '3600 detik',       '#f59e0b'],
              ['Remember Me Token',    'SHA-256 Hash',     '#10b981'],
              ['Rate Limit',           'Lock 15 menit',    '#10b981'],
            ] as [$k,$v,$c]): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f8fafc;border-radius:8px;border:1px solid #eaecf0;">
              <span style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;"><?= e($k) ?></span>
              <span style="color:<?= $c ?>;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;"><?= e($v) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Akun Terkunci -->
      <?php if (!empty($lockedAccounts)): ?>
      <div class="card" style="padding:0;overflow:hidden;margin-bottom:24px;border:1px solid #fecaca;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
        <div style="padding:20px 24px;border-bottom:1px solid #fecaca;background:#fef2f2;display:flex;align-items:center;gap:10px;">
          <span class="material-symbols-outlined" style="color:#ef4444;">lock_person</span>
          <h2 style="font-size:18px;font-weight:700;color:#ef4444;">Akun Terkunci (<?= count($lockedAccounts) ?>)</h2>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead><tr><th>Pegawai</th><th>Role</th><th>Percobaan Gagal</th><th>Terkunci Hingga</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($lockedAccounts as $la): ?>
              <tr>
                <td>
                  <div style="font-weight:600;color:#1a1d1f;font-size:14px;"><?= e($la['nama']) ?></div>
                  <div style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:12px;"><?= e($la['email']) ?></div>
                </td>
                <td><?= roleBadge($la['role']) ?></td>
                <td style="color:#ef4444;font-weight:700;font-family:'JetBrains Mono',monospace;font-size:14px;"><?= e($la['login_attempts']) ?>x</td>
                <td style="color:#f59e0b;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;"><?= e(date('d/m/Y H:i', strtotime($la['locked_until']))) ?></td>
                <td>
                  <a href="/simpekabjmk/reset_password.php?id=<?= $la['id'] ?>" class="btn-ghost" style="font-size:13px;padding:8px 16px;background:#ffffff;border:1px solid #eaecf0;font-weight:600;">
                    <span class="material-symbols-outlined" style="font-size:18px;">lock_open</span>
                    Buka Kunci & Reset
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <!-- Threat & Prevention Cards -->
      <div class="card" style="margin-bottom:24px;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
        <h2 style="font-size:18px;font-weight:700;color:#1a1d1f;margin-bottom:24px;display:flex;align-items:center;gap:10px;">
          <span class="material-symbols-outlined" style="color:#ef4444;background:#fef2f2;padding:8px;border-radius:12px;">report</span>
          Ancaman & Pencegahan Aktif
        </h2>
        <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));">
          <?php foreach ([
            ['lock_person',  'Session Fixation',  '#ef4444', 'Dicegah', '#fef2f2', 'session_regenerate_id(true) setelah login sukses.'],
            ['vpn_key_alert','Brute Force',        '#f59e0b', 'Rate Limited', '#fffbeb', 'Kunci akun 15 menit setelah 5x gagal.'],
            ['cookie_off',   'Session Hijacking', '#8b5cf6', 'Dilindungi', '#f5f3ff', 'HttpOnly + Secure + SameSite=Strict flags.'],
            ['token',        'CSRF Attack',        '#0ea5e9', 'Token Aktif', '#e0f2fe', 'hash_equals() token validation setiap POST.'],
          ] as [$ic,$title,$c,$status,$bg,$desc]): ?>
          <div style="padding:20px;border-radius:12px;background:#ffffff;border:1px solid #eaecf0;box-shadow:0 2px 10px rgba(0,0,0,0.02);">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px;">
              <div style="display:flex;align-items:center;gap:10px;">
                <span class="material-symbols-outlined" style="color:<?= $c ?>;background:<?= $bg ?>;padding:6px;border-radius:8px;"><?= $ic ?></span>
                <span style="font-weight:700;color:#1a1d1f;font-size:15px;"><?= $title ?></span>
              </div>
              <span class="badge badge-active" style="font-size:11px;"><?= $status ?></span>
            </div>
            <p style="color:#64748b;font-size:13px;margin:0;font-weight:500;line-height:1.5;"><?= $desc ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Recent Activity Log -->
      <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
        <div style="padding:20px 24px;border-bottom:1px solid #eaecf0;display:flex;justify-content:space-between;align-items:center;background:#ffffff;">
          <h2 style="font-size:18px;font-weight:700;color:#1a1d1f;display:flex;align-items:center;gap:10px;">
            <span class="material-symbols-outlined" style="color:#3b82f6;background:#eff6ff;padding:8px;border-radius:12px;">radar</span>
            Log Aktivitas Terbaru
          </h2>
          <a href="/simpekabjmk/log.php" class="btn-ghost" style="font-size:13px;padding:8px 16px;background:#ffffff;border:1px solid #eaecf0;font-weight:600;">
            <span class="material-symbols-outlined" style="font-size:18px;">open_in_new</span>
            Lihat Semua
          </a>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>IP</th><th>Level</th></tr></thead>
            <tbody>
              <?php foreach ($recentLogs as $log):
                $lvlBadge = [
                  'info'     => '<span class="badge badge-active">INFO</span>',
                  'warning'  => '<span class="badge badge-warning">WARN</span>',
                  'critical' => '<span class="badge badge-danger">KRITIS</span>',
                ][$log['level']] ?? '';
              ?>
              <tr>
                <td style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:500;"><?= e(date('H:i:s', strtotime($log['created_at']))) ?></td>
                <td style="color:#3b82f6;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;"><?= e($log['email'] ?? $log['ip_address'] ?? '—') ?></td>
                <td style="color:#1a1d1f;font-size:14px;font-weight:500;"><?= e($log['aksi']) ?></td>
                <td style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:500;"><?= e($log['ip_address'] ?? '—') ?></td>
                <td><?= $lvlBadge ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
