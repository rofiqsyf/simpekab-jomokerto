<?php
// ============================================================
// dashboard_pegawai.php — Self-Service Dashboard untuk Pegawai
// Menampilkan Absensi, Layanan Kepegawaian, E-Kinerja
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
if (!hasRole('pegawai')) {
    if (!hasRole('super_admin')) redirect('/simpekabjmk/dashboard.php');
}

$currentPage = 'dashboard_pegawai';
$pageTitle   = 'Dashboard Pegawai';
$user        = currentUser();

// 1. Ambil Profil Pegawai
$stmtPegawai = $pdo->prepare("SELECT * FROM pegawai WHERE user_id = ?");
$stmtPegawai->execute([$user['id']]);
$profil = $stmtPegawai->fetch();
$nip = $profil['nip'] ?? 'Belum ada NIP';
$divisi = $profil['divisi'] ?? 'Belum di-assign';
$posisi = $profil['posisi'] ?? 'Staf';

// 2. Absensi Hari Ini
$today = date('Y-m-d');
$stmtAbsensiToday = $pdo->prepare("SELECT check_in, check_out, status FROM absensi WHERE user_id = ? AND tanggal = ?");
$stmtAbsensiToday->execute([$user['id'], $today]);
$absensiToday = $stmtAbsensiToday->fetch();

// 3. Statistik Absensi Bulan Ini
$bulanIni = date('m');
$tahunIni = date('Y');
$stmtStat = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
        SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha,
        SUM(CASE WHEN status IN ('izin', 'sakit') THEN 1 ELSE 0 END) as izin
    FROM absensi 
    WHERE user_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?
");
$stmtStat->execute([$user['id'], $bulanIni, $tahunIni]);
$statAbsen = $stmtStat->fetch();

// 4. Layanan Aktif Terakhir
$stmtLayanan = $pdo->prepare("SELECT * FROM pengajuan_layanan WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$stmtLayanan->execute([$user['id']]);
$listLayanan = $stmtLayanan->fetchAll();

// 5. SKP Terakhir
$stmtSKP = $pdo->prepare("SELECT * FROM kinerja_skp WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmtSKP->execute([$user['id']]);
$skp = $stmtSKP->fetch();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      
      <!-- Hero Section -->
      <div class="card" style="margin-bottom:24px;border:none;background:linear-gradient(135deg, #0ea5e9, #3b82f6);color:#ffffff;padding:32px;border-radius:24px;position:relative;overflow:hidden;box-shadow:0 10px 30px rgba(14, 165, 233, 0.2);">
        <div style="position:absolute;top:-50px;right:-50px;width:200px;height:200px;background:rgba(255,255,255,0.1);border-radius:50%;filter:blur(20px);"></div>
        <div style="display:flex;align-items:center;gap:24px;position:relative;z-index:1;">
          <div style="width:80px;height:80px;background:#ffffff;border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:800;color:#0ea5e9;box-shadow:0 8px 16px rgba(0,0,0,0.1);">
            <?= e(getInisial($user['nama'])) ?>
          </div>
          <div>
            <h1 style="font-size:28px;font-weight:800;margin:0;letter-spacing:-0.02em;">Selamat datang, <?= e($user['nama']) ?></h1>
            <p style="font-size:15px;opacity:0.9;margin:8px 0 0 0;display:flex;align-items:center;gap:12px;">
              <span style="display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:16px;">badge</span> <?= e($nip) ?></span>
              <span>•</span>
              <span style="display:flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:16px;">work</span> <?= e($posisi) ?> di <?= e($divisi) ?></span>
            </p>
          </div>
        </div>
      </div>

      <div style="display:grid;gap:24px;grid-template-columns:1fr;@media(min-width:1024px){grid-template-columns:2fr 1fr;}">
        
        <!-- Kiri: Metrik & Tindakan Utama -->
        <div style="display:flex;flex-direction:column;gap:24px;">
          
          <!-- Metrik Absensi Bulanan -->
          <div>
            <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
              <span class="material-symbols-outlined" style="color:#0ea5e9;">calendar_month</span> Statistik Kehadiran (Bulan Ini)
            </h2>
            <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));">
              <?php foreach ([
                ['Hadir',     $statAbsen['hadir'] ?? 0,     '#10b981', '#f0fdf4'],
                ['Terlambat', $statAbsen['terlambat'] ?? 0, '#f59e0b', '#fffbeb'],
                ['Izin/Sakit',$statAbsen['izin'] ?? 0,      '#3b82f6', '#eff6ff'],
                ['Alpha',     $statAbsen['alpha'] ?? 0,     '#ef4444', '#fef2f2'],
              ] as [$l, $v, $c, $bg]): ?>
              <div class="card" style="padding:16px;text-align:center;border:1px solid #eaecf0;box-shadow:0 2px 10px rgba(0,0,0,0.02);">
                <div style="font-size:28px;font-weight:800;color:<?= $c ?>;margin-bottom:4px;"><?= $v ?></div>
                <div style="color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= $l ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Pengajuan Layanan Terakhir -->
          <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;">
            <div style="padding:16px 20px;border-bottom:1px solid #eaecf0;display:flex;justify-content:space-between;align-items:center;background:#ffffff;">
              <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;margin:0;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="color:#8b5cf6;">draft</span> Riwayat Pengajuan Layanan
              </h2>
              <a href="/simpekabjmk/layanan_pengajuan.php" class="btn-primary" style="font-size:12px;padding:6px 12px;border-radius:8px;">+ Ajukan Baru</a>
            </div>
            <div style="padding:0;">
              <?php if (empty($listLayanan)): ?>
              <div style="padding:32px;text-align:center;color:#94a3b8;font-size:14px;">Belum ada pengajuan layanan.</div>
              <?php else: ?>
              <ul style="list-style:none;margin:0;padding:0;">
                <?php foreach ($listLayanan as $layanan): 
                  $stsColor = [
                    'pending_atasan'  => '#f59e0b',
                    'approved_atasan' => '#3b82f6',
                    'approved_bkpsdm' => '#10b981',
                    'rejected'        => '#ef4444'
                  ][$layanan['status']] ?? '#64748b';
                ?>
                <li style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
                  <div>
                    <div style="font-weight:600;color:#1a1d1f;font-size:14px;"><?= e($layanan['jenis']) ?></div>
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">
                      <?= date('d M Y', strtotime($layanan['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($layanan['tanggal_selesai'])) ?>
                    </div>
                  </div>
                  <span class="badge" style="background:<?= $stsColor ?>20;color:<?= $stsColor ?>;border:1px solid <?= $stsColor ?>40;">
                    <?= ucwords(str_replace('_', ' ', $layanan['status'])) ?>
                  </span>
                </li>
                <?php endforeach; ?>
              </ul>
              <?php endif; ?>
            </div>
          </div>

        </div>

        <!-- Kanan: Status Absensi & SKP -->
        <div style="display:flex;flex-direction:column;gap:24px;">
          
          <!-- Box Absensi Hari Ini (Widget GPS) -->
          <?php include __DIR__ . '/partials/widget_absensi.php'; ?>

          <!-- Box E-Kinerja (SKP) -->
          <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);background:linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
            <h2 style="font-size:15px;font-weight:700;color:#1a1d1f;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
              <span class="material-symbols-outlined" style="color:#10b981;">psychology</span> Sasaran Kinerja (SKP)
            </h2>
            <?php if (!$skp): ?>
              <p style="color:#64748b;font-size:13px;">Belum ada SKP yang diinput.</p>
            <?php else: ?>
              <div style="text-align:center;margin-bottom:16px;">
                <div style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;margin-bottom:4px;">Bulan <?= date('M Y', strtotime($skp['bulan'].'-01')) ?></div>
                <div style="font-size:48px;font-weight:900;color:<?= $skp['capaian'] >= 80 ? '#10b981' : '#f59e0b' ?>;line-height:1;">
                  <?= $skp['capaian'] ?? '0' ?>%
                </div>
                <div style="font-size:13px;color:#1a1d1f;font-weight:600;margin-top:8px;">Nilai Atasan: <?= $skp['nilai_atasan'] ? e($skp['nilai_atasan']) : 'Menunggu' ?></div>
              </div>
              <div style="background:#f8fafc;border:1px dashed #cbd5e1;padding:12px;border-radius:8px;font-size:13px;color:#475569;margin-bottom:12px;">
                Kegiatan: "<?= e($skp['kegiatan']) ?>"
              </div>
            <?php endif; ?>
            <a href="/simpekabjmk/kinerja_skp.php" class="btn-ghost" style="width:100%;margin-top:4px;border:1px solid #cbd5e1;justify-content:center;">Kelola SKP</a>
          </div>

        </div>

      </div>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
