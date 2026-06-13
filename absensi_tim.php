<?php
// ============================================================
// absensi_tim.php — Rekap Absensi Tim/Divisi
// Akses: Manager + Admin saja
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireRole(['super_admin', 'admin_bkpsdm', 'atasan']); // RBAC Guard!

$currentPage = 'absensi_tim';
$pageTitle   = 'Absensi Tim';
$user        = currentUser();
$today       = date('Y-m-d');

// Filter bulan & tahun (default: bulan ini)
$bulan = (int)($_GET['bulan'] ?? date('m'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$bulan = max(1, min(12, $bulan));
$tahun = max(2020, min(2030, $tahun));

// ============================================================
// Tentukan divisi yang ditampilkan:
// - Admin: semua divisi (bisa filter per divisi)
// - Manager: hanya divisinya sendiri
// ============================================================
if (hasRole('super_admin') || hasRole('admin_bkpsdm')) {
    $filterDivisi = $_GET['divisi'] ?? '';
    // Ambil semua divisi yang tersedia
    $stmtDivisi = $pdo->query("SELECT DISTINCT divisi FROM pegawai ORDER BY divisi");
    $daftarDivisi = $stmtDivisi->fetchAll(PDO::FETCH_COLUMN);
} else {
    // Manager: ambil divisi sendiri
    $stmtDiv = $pdo->prepare("SELECT divisi FROM pegawai WHERE user_id = ?");
    $stmtDiv->execute([$user['id']]);
    $filterDivisi = $stmtDiv->fetchColumn() ?? '';
    $daftarDivisi = [$filterDivisi];
}

// ============================================================
// Query rekap absensi tim
// ============================================================
$params  = [$bulan, $tahun];
$divWhere = '';
if ($filterDivisi) {
    $divWhere = 'AND p.divisi = ?';
    $params[] = $filterDivisi;
}

$stmtTim = $pdo->prepare("
    SELECT
        u.id, u.nama, u.email, u.role,
        p.divisi, p.posisi, p.nip,
        COUNT(a.id)                          AS total_hari,
        COALESCE(SUM(a.status='hadir'), 0)      AS jml_hadir,
        COALESCE(SUM(a.status='terlambat'), 0)  AS jml_terlambat,
        COALESCE(SUM(a.status='alpha'), 0)      AS jml_alpha,
        COALESCE(SUM(a.status='izin'), 0)       AS jml_izin,
        COALESCE(SUM(a.status='sakit'), 0)      AS jml_sakit,
        (SELECT status FROM absensi WHERE user_id=u.id AND tanggal=CURDATE() LIMIT 1) AS status_hari_ini,
        (SELECT check_in FROM absensi WHERE user_id=u.id AND tanggal=CURDATE() LIMIT 1) AS checkin_hari_ini
    FROM users u
    JOIN pegawai p ON p.user_id = u.id
    LEFT JOIN absensi a ON a.user_id = u.id
        AND MONTH(a.tanggal) = ?
        AND YEAR(a.tanggal) = ?
    WHERE 1=1 {$divWhere}
    GROUP BY u.id, u.nama, u.email, u.role, p.divisi, p.posisi, p.nip
    ORDER BY p.divisi, u.nama
");
$stmtTim->execute($params);
$dataTim = $stmtTim->fetchAll();

// Hitung summary
$totalAnggota  = count($dataTim);
$totalHadirIni = count(array_filter($dataTim, fn($r) => in_array($r['status_hari_ini'], ['hadir','terlambat'])));
$totalAlphaIni = count(array_filter($dataTim, fn($r) => $r['status_hari_ini'] === 'alpha'));
$totalBelum    = count(array_filter($dataTim, fn($r) => !$r['status_hari_ini']));

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
?>
<?php include __DIR__ . '/partials/head.php'; ?>

<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="page-content">
      <?= renderFlash() ?>

      <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:32px;flex-wrap:wrap;gap:16px;">
        <div>
          <h1 class="section-title">Absensi Tim</h1>
          <p class="section-subtitle">
            Rekap kehadiran <?= (hasRole('super_admin') || hasRole('admin_bkpsdm')) ? 'seluruh' : '' ?> anggota
            <?= $filterDivisi ? '— <strong style="color:#1a1d1f;">' . e($filterDivisi) . '</strong>' : '' ?>
          </p>
        </div>
        <!-- Filter -->
        <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
          <?php if (hasRole('super_admin') || hasRole('admin_bkpsdm')): ?>
          <select name="divisi" class="input-card" style="max-width:200px;width:auto;" onchange="this.form.submit()">
            <option value="">Semua Divisi</option>
            <?php foreach ($daftarDivisi as $div): ?>
            <option value="<?= e($div) ?>" <?= $filterDivisi===$div?'selected':'' ?>><?= e($div) ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
          <select name="bulan" class="input-card" style="width:auto;" onchange="this.form.submit()">
            <?php for ($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>" <?= $bulan===$m?'selected':'' ?>><?= $namaBulan[$m] ?></option>
            <?php endfor; ?>
          </select>
          <select name="tahun" class="input-card" style="width:auto;" onchange="this.form.submit()">
            <?php for ($y=2024;$y<=2026;$y++): ?>
            <option value="<?= $y ?>" <?= $tahun===$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
          <a href="/simpekabjmk/export_absensi_tim.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&divisi=<?= urlencode($filterDivisi) ?>" class="btn-primary" style="padding:10px 16px;margin-left:8px;" title="Unduh CSV">
            <span class="material-symbols-outlined" style="font-size:18px;">download</span> CSV
          </a>
        </form>
      </div>

      <!-- Summary Cards -->
      <div style="display:grid;gap:24px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:32px;">
        <?php foreach ([
          ['Anggota Tim',   $totalAnggota,  '#3b82f6', '#eff6ff', 'group'],
          ['Hadir Hari Ini',$totalHadirIni, '#10b981', '#f0fdf4', 'event_available'],
          ['Alpha Hari Ini',$totalAlphaIni, '#ef4444', '#fef2f2', 'person_off'],
          ['Belum Absen',   $totalBelum,    '#f59e0b', '#fffbeb', 'schedule'],
        ] as [$label, $val, $color, $bg, $icon]): ?>
        <div class="card" style="display:flex;align-items:center;gap:16px;">
          <div style="width:56px;height:56px;border-radius:16px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;color:<?= $color ?>;flex-shrink:0;">
            <span class="material-symbols-outlined" style="font-size:28px;"><?= $icon ?></span>
          </div>
          <div>
            <div style="color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;"><?= $label ?></div>
            <div style="font-size:28px;font-weight:800;color:#1a1d1f;"><?= $val ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Rekap Tabel -->
      <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
        <div style="padding:20px 24px;border-bottom:1px solid #eaecf0;display:flex;justify-content:space-between;align-items:center;background:#ffffff;">
          <h2 style="font-size:18px;font-weight:700;color:#1a1d1f;">
            Rekap <?= e($namaBulan[$bulan]) ?> <?= $tahun ?>
          </h2>
          <span class="badge badge-secondary" style="font-size:13px;"><?= $totalAnggota ?> pegawai</span>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Pegawai</th>
                <th>Divisi</th>
                <th>Status Hari Ini</th>
                <th style="text-align:center;">Hadir</th>
                <th style="text-align:center;">Terlambat</th>
                <th style="text-align:center;">Alpha</th>
                <th style="text-align:center;">Izin/Sakit</th>
                <th style="text-align:center;">Kehadiran</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($dataTim)): ?>
              <tr><td colspan="8" style="text-align:center;color:#64748b;padding:48px;font-weight:500;">Tidak ada data pegawai.</td></tr>
              <?php else: ?>
              <?php foreach ($dataTim as $r):
                $totalBulan    = $r['jml_hadir'] + $r['jml_terlambat'] + $r['jml_alpha'];
                $persen        = $totalBulan > 0 ? round(($r['jml_hadir'] + $r['jml_terlambat']) / max($totalBulan,1) * 100) : 0;
                $persenColor   = $persen >= 80 ? '#10b981' : ($persen >= 60 ? '#f59e0b' : '#ef4444');
              ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:14px;">
                    <div class="avatar avatar-sm" style="background:#e0f2fe;color:#0ea5e9;font-weight:700;"><?= e(getInisial($r['nama'])) ?></div>
                    <div>
                      <div style="font-weight:600;color:#1a1d1f;font-size:14px;"><?= e(explode(',', $r['nama'])[0]) ?></div>
                      <div style="color:#64748b;font-size:12px;font-weight:500;"><?= e($r['nip']) ?></div>
                    </div>
                  </div>
                </td>
                <td style="color:#475569;font-weight:500;font-size:14px;"><?= e($r['divisi']) ?></td>
                <td>
                  <?php if ($r['status_hari_ini']): ?>
                    <?= absensiBadge($r['status_hari_ini']) ?>
                    <?php if ($r['checkin_hari_ini']): ?>
                    <div style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:12px;margin-top:6px;font-weight:600;"><?= e($r['checkin_hari_ini']) ?></div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span style="color:#94a3b8;font-size:13px;font-weight:600;">—</span>
                  <?php endif; ?>
                </td>
                <td style="text-align:center;font-weight:700;color:#10b981;font-size:15px;"><?= e($r['jml_hadir']) ?></td>
                <td style="text-align:center;font-weight:700;color:#f59e0b;font-size:15px;"><?= e($r['jml_terlambat']) ?></td>
                <td style="text-align:center;font-weight:700;color:#ef4444;font-size:15px;"><?= e($r['jml_alpha']) ?></td>
                <td style="text-align:center;font-weight:700;color:#3b82f6;font-size:15px;"><?= e($r['jml_izin'] + $r['jml_sakit']) ?></td>
                <td style="text-align:center;">
                  <span style="font-weight:800;color:<?= $persenColor ?>;font-size:15px;"><?= $persen ?>%</span>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
