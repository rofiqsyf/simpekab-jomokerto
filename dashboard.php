<?php
// ============================================================
// dashboard.php — Dashboard Utama
// Akses: Semua role (admin, manager, karyawan)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

// Guard: Wajib login
requireLogin();

$currentPage = 'dashboard';
$pageTitle   = 'Dashboard';
$user        = currentUser();
$role        = $user['role'];

// ============================================================
// QUERY DATA BERDASARKAN ROLE
// ============================================================

$today = date('Y-m-d');

// Jumlah total pegawai (admin & manager: semua, karyawan: hanya dirinya)
if ($role === 'admin') {
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM users");
    $totalPegawai = (int) $stmtTotal->fetchColumn();

    // Jumlah per role
    $stmtPerRole = $pdo->query("SELECT role, COUNT(*) as jumlah FROM users GROUP BY role");
    $perRole = [];
    foreach ($stmtPerRole->fetchAll() as $r) {
        $perRole[$r['role']] = $r['jumlah'];
    }

    // Rekap absensi hari ini — semua pegawai
    $stmtHadir = $pdo->prepare("
        SELECT COUNT(*) FROM absensi
        WHERE tanggal = ? AND status IN ('hadir','terlambat')
    ");
    $stmtHadir->execute([$today]);
    $hadirHariIni = (int) $stmtHadir->fetchColumn();

    // Daftar pegawai terbaru (5 data)
    $stmtList = $pdo->prepare("
        SELECT u.id, u.nama, u.email, u.role,
               p.divisi, p.posisi, p.status as status_pegawai,
               u.last_login,
               (SELECT status FROM absensi WHERE user_id=u.id AND tanggal=? LIMIT 1) as absensi_hari_ini
        FROM users u
        LEFT JOIN pegawai p ON p.user_id = u.id
        ORDER BY u.created_at DESC
        LIMIT 6
    ");
    $stmtList->execute([$today]);
    $listPegawai = $stmtList->fetchAll();

} elseif ($role === 'manager') {
    // Dapatkan divisi manager
    $stmtDiv = $pdo->prepare("SELECT divisi FROM pegawai WHERE user_id = ?");
    $stmtDiv->execute([$user['id']]);
    $divisiManager = $stmtDiv->fetchColumn() ?? '';

    // Jumlah anggota tim (divisi sama)
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM pegawai WHERE divisi = ? AND user_id != ?");
    $stmtTotal->execute([$divisiManager, $user['id']]);
    $totalPegawai = (int) $stmtTotal->fetchColumn();

    // Hadir hari ini di divisi
    $stmtHadir = $pdo->prepare("
        SELECT COUNT(*) FROM absensi a
        JOIN pegawai p ON p.user_id = a.user_id
        WHERE a.tanggal = ? AND a.status IN ('hadir','terlambat') AND p.divisi = ?
    ");
    $stmtHadir->execute([$today, $divisiManager]);
    $hadirHariIni = (int) $stmtHadir->fetchColumn();

    // Daftar anggota tim
    $stmtList = $pdo->prepare("
        SELECT u.id, u.nama, u.email, u.role,
               p.divisi, p.posisi, p.status as status_pegawai,
               u.last_login,
               (SELECT status FROM absensi WHERE user_id=u.id AND tanggal=? LIMIT 1) as absensi_hari_ini
        FROM users u
        JOIN pegawai p ON p.user_id = u.id
        WHERE p.divisi = ? AND u.id != ?
        LIMIT 6
    ");
    $stmtList->execute([$today, $divisiManager, $user['id']]);
    $listPegawai = $stmtList->fetchAll();
    $perRole = [];

} else {
    // Karyawan: hanya statistik dirinya sendiri
    $totalPegawai = 1;
    $hadirHariIni = 0;
    $listPegawai  = [];
    $perRole      = [];
}

// Absensi pribadi hari ini
$stmtAbsensiToday = $pdo->prepare("
    SELECT check_in, check_out, status FROM absensi
    WHERE user_id = ? AND tanggal = ?
");
$stmtAbsensiToday->execute([$user['id'], $today]);
$absensiToday = $stmtAbsensiToday->fetch();

// Statistik absensi bulan ini (semua role)
$stmtStats = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        SUM(status = 'hadir') as hadir,
        SUM(status = 'terlambat') as terlambat,
        SUM(status = 'alpha') as alpha,
        SUM(status = 'izin') as izin
    FROM absensi
    WHERE user_id = ? AND MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())
");
$stmtStats->execute([$user['id']]);
$statsAbsensi = $stmtStats->fetch();
?>
<?php include __DIR__ . '/partials/head.php'; ?>

<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="page-content">
      <?= renderFlash() ?>
      <!-- Page Header -->
      <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:32px;">
        <div>
          <h1 class="section-title">Dashboard Overview</h1>
          <p class="section-subtitle">
            Selamat datang kembali, <strong style="color:#1a1d1f;"><?= e(explode(',', $user['nama'])[0]) ?></strong>
            — <?= date('l, d F Y', strtotime($today)) ?>
          </p>
        </div>
        <div style="display:flex;align-items:center;gap:8px;background:#ffffff;padding:8px 16px;border-radius:999px;border:1px solid #eaecf0;box-shadow:0 2px 10px rgba(0,0,0,0.02);">
          <span class="dot-green"></span>
          <span style="color:#10b981;font-weight:600;font-size:13px;">Sistem Online</span>
        </div>
      </div>

      <!-- ====== METRIC CARDS ====== -->
      <div style="display:grid;gap:24px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));margin-bottom:32px;">

        <!-- Absensi Hari Ini -->
        <div class="card card-dark" style="position:relative;overflow:hidden;">
          <div style="position:absolute;top:-20px;right:-20px;width:100px;height:100px;border-radius:50%;background:radial-gradient(circle,rgba(255,184,0,0.15),transparent 70%);"></div>
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
            <span style="color:#9a9fa5;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">Status Hari Ini</span>
            <span class="material-symbols-outlined" style="color:#ffb800;background:rgba(255,184,0,0.1);padding:8px;border-radius:12px;">fingerprint</span>
          </div>
          <?php if ($absensiToday): ?>
            <div style="font-size:32px;font-weight:800;color:#ffffff;margin-bottom:8px;"><?= strtoupper(e($absensiToday['status'])) ?></div>
            <div style="color:#9a9fa5;font-size:14px;font-weight:500;">
              Masuk: <span style="color:#ffffff;"><?= e($absensiToday['check_in'] ?? '-') ?></span>
              <?php if ($absensiToday['check_out']): ?> | Keluar: <span style="color:#ffffff;"><?= e($absensiToday['check_out']) ?></span><?php endif; ?>
            </div>
          <?php else: ?>
            <div style="font-size:32px;font-weight:800;color:#ffb800;margin-bottom:8px;">BELUM</div>
            <a href="/simpeg_mini/absensi.php" style="color:#ffffff;font-size:14px;font-weight:500;text-decoration:underline;">Input absensi sekarang</a>
          <?php endif; ?>
        </div>

        <!-- Absensi Bulan Ini -->
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
            <span style="color:#64748b;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">Bulan Ini</span>
            <span class="material-symbols-outlined" style="color:#3b82f6;background:#eff6ff;padding:8px;border-radius:12px;">query_stats</span>
          </div>
          <div style="font-size:32px;font-weight:800;color:#1a1d1f;margin-bottom:12px;"><?= e($statsAbsensi['hadir'] + $statsAbsensi['terlambat']) ?><span style="font-size:14px;font-weight:600;color:#94a3b8;margin-left:4px;">hari hadir</span></div>
          <div style="display:flex;gap:16px;font-size:13px;font-weight:600;">
            <span style="color:#f59e0b;display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:16px;">schedule</span> <?= e($statsAbsensi['terlambat']) ?> telat</span>
            <span style="color:#ef4444;display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:16px;">cancel</span> <?= e($statsAbsensi['alpha']) ?> alpha</span>
          </div>
        </div>

        <?php if ($role === 'admin'): ?>
        <!-- Total Pegawai (admin only) -->
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
            <span style="color:#64748b;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">Total Pegawai</span>
            <span class="material-symbols-outlined" style="color:#f59e0b;background:#fffbeb;padding:8px;border-radius:12px;">badge</span>
          </div>
          <div style="font-size:32px;font-weight:800;color:#1a1d1f;margin-bottom:12px;"><?= e($totalPegawai) ?></div>
          <div style="display:flex;flex-wrap:wrap;gap:12px;">
            <?php foreach ([['admin','#ef4444'],['manager','#f59e0b'],['karyawan','#0ea5e9']] as [$r,$c]): ?>
            <div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;">
              <div style="width:8px;height:8px;border-radius:50%;background:<?= $c ?>;flex-shrink:0;"></div>
              <span style="color:#64748b;text-transform:capitalize;"><?= $r ?>:</span>
              <span style="color:<?= $c ?>;"><?= e($perRole[$r] ?? 0) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Hadir Hari Ini (admin) -->
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
            <span style="color:#64748b;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">Hadir Hari Ini</span>
            <span class="material-symbols-outlined" style="color:#10b981;background:#f0fdf4;padding:8px;border-radius:12px;">event_available</span>
          </div>
          <div style="font-size:32px;font-weight:800;color:#1a1d1f;margin-bottom:12px;"><?= e($hadirHariIni) ?><span style="font-size:16px;color:#94a3b8;font-weight:600;margin-left:4px;">/<?= e($totalPegawai) ?></span></div>
          <div class="progress-bar" style="margin-top:12px;height:8px;">
            <div class="progress-fill" style="background:#10b981;width:<?= $totalPegawai > 0 ? round(($hadirHariIni/$totalPegawai)*100) : 0 ?>%;"></div>
          </div>
        </div>

        <?php elseif ($role === 'manager'): ?>
        <!-- Anggota Tim (manager) -->
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
            <span style="color:#64748b;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">Anggota Tim</span>
            <span class="material-symbols-outlined" style="color:#f59e0b;background:#fffbeb;padding:8px;border-radius:12px;">group_work</span>
          </div>
          <div style="font-size:32px;font-weight:800;color:#1a1d1f;margin-bottom:8px;"><?= e($totalPegawai) ?></div>
          <div style="color:#64748b;font-size:14px;font-weight:500;"><?= e($divisiManager ?? '-') ?></div>
          <a href="/simpeg_mini/absensi_tim.php" style="display:inline-flex;align-items:center;gap:4px;color:#3b82f6;font-weight:600;font-size:13px;margin-top:16px;text-decoration:none;">
            Lihat rekap tim <span class="material-symbols-outlined" style="font-size:16px;">arrow_forward</span>
          </a>
        </div>

        <?php else: ?>
        <!-- Sesi Aktif (karyawan) -->
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
            <span style="color:#64748b;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">Keamanan Sesi</span>
            <span class="material-symbols-outlined" style="color:#10b981;background:#f0fdf4;padding:8px;border-radius:12px;">security</span>
          </div>
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:#1a1d1f;"><span class="dot-green"></span> Terlindungi Aktif</div>
            <div style="font-size:13px;font-weight:500;color:#64748b;background:#f8fafc;padding:8px 12px;border-radius:8px;">Bcrypt ✓ | CSRF ✓ | RBAC ✓</div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- ====== QUICK ACTIONS ====== -->
      <div style="margin-bottom:32px;">
        <h2 style="font-size:20px;font-weight:700;color:#1a1d1f;margin-bottom:16px;">Aksi Cepat</h2>
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
          <a href="/simpeg_mini/absensi.php" class="btn-primary">
            <span class="material-symbols-outlined" style="font-size:20px;">checklist</span>
            Input Absensi
          </a>
          <a href="/simpeg_mini/profil.php" class="btn-ghost">
            <span class="material-symbols-outlined" style="font-size:20px;">person</span>
            Profil Saya
          </a>
          <?php if (hasAnyRole(['admin','manager'])): ?>
          <a href="/simpeg_mini/absensi_tim.php" class="btn-ghost">
            <span class="material-symbols-outlined" style="font-size:20px;">groups</span>
            Absensi Tim
          </a>
          <?php endif; ?>
          <?php if (hasRole('admin')): ?>
          <a href="/simpeg_mini/pegawai.php" class="btn-ghost">
            <span class="material-symbols-outlined" style="font-size:20px;">badge</span>
            Data Pegawai
          </a>
          <a href="/simpeg_mini/keamanan.php" class="btn-danger" style="background:#fff;border-color:#e2e8f0;color:#1a1d1f;">
            <span class="material-symbols-outlined" style="font-size:20px;color:#ef4444;">shield_lock</span>
            Brankas Keamanan
          </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- ====== TABLE: DAFTAR PEGAWAI / TIM ====== -->
      <?php if (!empty($listPegawai)): ?>
      <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
        <div style="padding:20px 24px;border-bottom:1px solid #eaecf0;display:flex;justify-content:space-between;align-items:center;background:#ffffff;">
          <h2 style="font-size:18px;font-weight:700;color:#1a1d1f;">
            <?= $role === 'admin' ? 'Registri Pegawai Terbaru' : 'Anggota Tim Saya' ?>
          </h2>
          <?php if ($role === 'admin'): ?>
          <a href="/simpeg_mini/pegawai.php" class="btn-ghost" style="padding:8px 16px;font-size:13px;">
            Lihat Semua <span class="material-symbols-outlined" style="font-size:16px;">arrow_forward</span>
          </a>
          <?php endif; ?>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Pegawai</th>
                <th>Role</th>
                <th>Divisi</th>
                <th>Absensi Hari Ini</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($listPegawai as $p): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:14px;">
                    <div class="avatar avatar-sm" style="background:#e0f2fe;color:#0ea5e9;font-weight:700;"><?= e(getInisial($p['nama'])) ?></div>
                    <div>
                      <div style="font-weight:600;color:#1a1d1f;font-size:14px;"><?= e(explode(',', $p['nama'])[0]) ?></div>
                      <div style="color:#64748b;font-size:12px;font-weight:500;"><?= e($p['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td><?= roleBadge($p['role']) ?></td>
                <td style="color:#475569;font-weight:500;font-size:14px;"><?= e($p['divisi'] ?? '-') ?></td>
                <td><?= $p['absensi_hari_ini'] ? absensiBadge($p['absensi_hari_ini']) : '<span style="color:#94a3b8;font-size:13px;font-weight:600;">—</span>' ?></td>
                <td><?= pegawaiBadge($p['status_pegawai'] ?? 'aktif') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- .page-content -->
  </div><!-- .main-content -->
</div><!-- .app-layout -->

<?php require __DIR__ . '/partials/footer.php'; ?>
