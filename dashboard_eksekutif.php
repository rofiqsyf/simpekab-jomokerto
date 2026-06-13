<?php
// ============================================================
// dashboard_eksekutif.php — Executive Dashboard
// Analisis Makro, KPI, Proyeksi Anggaran
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
if (!hasRole('eksekutif') && !hasRole('super_admin')) {
    redirect('/simpekabjmk/dashboard.php');
}

$currentPage = 'dashboard_eksekutif';
$pageTitle   = 'Dashboard Eksekutif';
$user        = currentUser();

// 1. Total Pegawai Aktif
$totalPegawai = (int) $pdo->query("SELECT COUNT(*) FROM pegawai WHERE status='aktif'")->fetchColumn();

// 2. Proyeksi Anggaran Belanja Pegawai (Gaji Pokok + TPP Asumsi)
$anggaranBulanIni = $totalPegawai * 5500000; // Asumsi rata-rata Rp 5,5 juta/pegawai
$anggaranTahunIni = $anggaranBulanIni * 12;

// 3. KPI Disiplin (Kehadiran Bulan Ini)
$bulanIni = date('m');
$tahunIni = date('Y');
$stmtDisiplin = $pdo->prepare("
    SELECT 
        COUNT(*) as total_hari,
        SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as total_hadir
    FROM absensi 
    WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?
");
$stmtDisiplin->execute([$bulanIni, $tahunIni]);
$kpi = $stmtDisiplin->fetch();
$persenDisiplin = $kpi['total_hari'] > 0 ? round(($kpi['total_hadir'] / $kpi['total_hari']) * 100, 1) : 0;

// 4. Komposisi Jenis ASN
$stmtJenisASN = $pdo->query("
    SELECT p.jenis_asn, COUNT(p.id) as total 
    FROM pegawai p 
    WHERE p.status = 'aktif'
    GROUP BY p.jenis_asn
");
$komposisiJenis = [];
foreach ($stmtJenisASN->fetchAll() as $r) {
    $komposisiJenis[$r['jenis_asn']] = $r['total'];
}

// 5. Komposisi Golongan
$stmtGolongan = $pdo->query("
    SELECT p.golongan, COUNT(p.id) as total 
    FROM pegawai p 
    WHERE p.status = 'aktif' AND p.golongan IS NOT NULL AND p.golongan != ''
    GROUP BY p.golongan
    ORDER BY p.golongan ASC
");
$komposisiGolongan = [];
foreach ($stmtGolongan->fetchAll() as $r) {
    $komposisiGolongan[$r['golongan']] = $r['total'];
}
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      
      <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:32px;">
        <div>
          <h1 class="section-title">Executive Insight</h1>
          <p class="section-subtitle">Data Makro, KPI ASN, dan Proyeksi Anggaran Kabupaten</p>
        </div>
        <button class="btn-primary" style="background:#1a1d1f;color:#fff;border-radius:999px;padding:10px 20px;">
          <span class="material-symbols-outlined">download</span> Unduh Laporan Eksekutif
        </button>
      </div>

      <!-- KPI Macro Cards -->
      <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));margin-bottom:32px;">
        
        <div class="card" style="padding:24px;border:1px solid #eaecf0;background:#ffffff;box-shadow:0 10px 25px rgba(0,0,0,0.02);">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
            <div style="color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Total Pegawai Aktif</div>
            <span class="material-symbols-outlined" style="color:#0ea5e9;background:#e0f2fe;padding:8px;border-radius:12px;">group</span>
          </div>
          <div style="font-size:36px;font-weight:900;line-height:1;color:#1a1d1f;"><?= number_format($totalPegawai, 0, ',', '.') ?></div>
          <div style="margin-top:12px;font-size:13px;color:#64748b;display:flex;align-items:center;gap:4px;">
            <span class="material-symbols-outlined" style="font-size:16px;color:#10b981;">trending_up</span> <span style="color:#10b981;font-weight:600;">+2.4%</span> dari tahun lalu
          </div>
        </div>

        <div class="card" style="padding:24px;border:1px solid #eaecf0;background:#ffffff;box-shadow:0 10px 25px rgba(0,0,0,0.02);">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
            <div style="color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Proyeksi Gaji Bulanan</div>
            <span class="material-symbols-outlined" style="color:#8b5cf6;background:#f5f3ff;padding:8px;border-radius:12px;">account_balance_wallet</span>
          </div>
          <div style="font-size:32px;font-weight:900;line-height:1;color:#1a1d1f;">Rp <?= number_format($anggaranBulanIni/1000000, 1, ',', '.') ?> M</div>
          <div style="margin-top:12px;font-size:13px;color:#64748b;display:flex;align-items:center;gap:4px;">
            Estimasi 1 Tahun: <strong style="color:#1a1d1f;">Rp <?= number_format($anggaranTahunIni/1000000000, 2, ',', '.') ?> M</strong>
          </div>
        </div>

        <div class="card" style="padding:24px;border:1px solid #eaecf0;background:#ffffff;box-shadow:0 10px 25px rgba(0,0,0,0.02);">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
            <div style="color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Indeks Disiplin ASN</div>
            <span class="material-symbols-outlined" style="color:#10b981;background:#dcfce7;padding:8px;border-radius:12px;">speed</span>
          </div>
          <div style="font-size:36px;font-weight:900;line-height:1;color:#1a1d1f;"><?= $persenDisiplin ?>%</div>
          <div style="margin-top:12px;font-size:13px;color:#64748b;display:flex;align-items:center;gap:4px;">
            Target kabupaten: <strong style="color:#1a1d1f;">90.0%</strong>
          </div>
        </div>

      </div>

      <div style="display:grid;gap:24px;grid-template-columns:1fr;@media(min-width:1024px){grid-template-columns:2fr 1fr;}">
        
        <div style="display:flex;flex-direction:column;gap:24px;">
        <!-- Komposisi Jenis ASN -->
        <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;margin-bottom:24px;display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="color:#f59e0b;">pie_chart</span> Komposisi Status Kepegawaian (ASN)
          </h2>
          <div style="display:flex;flex-direction:column;gap:16px;">
            <?php foreach ([
                'PNS' => ['Pegawai Negeri Sipil (PNS)', '#10b981'],
                'PPPK' => ['Pegawai Pemerintah dengan Perjanjian Kerja', '#0ea5e9'],
                'Non-ASN' => ['Tenaga Honorer / Non-ASN', '#f59e0b'],
            ] as $key => [$label, $color]): 
                $val = $komposisiJenis[$key] ?? 0;
                $pct = $totalPegawai > 0 ? round(($val / $totalPegawai) * 100) : 0;
            ?>
            <div>
              <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px;">
                <span style="font-weight:600;color:#334155;"><?= $label ?></span>
                <span style="font-weight:700;color:#1a1d1f;"><?= $val ?> org (<?= $pct ?>%)</span>
              </div>
              <div style="width:100%;height:10px;background:#f1f5f9;border-radius:5px;overflow:hidden;">
                <div style="height:100%;background:<?= $color ?>;width:<?= $pct ?>%;border-radius:5px;"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Proyeksi Pensiun -->
        <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);background:url('data:image/svg+xml;utf8,<svg width=\"20\" height=\"20\" viewBox=\"0 0 20 20\" xmlns=\"http://www.w3.org/2000/svg\"><circle cx=\"2\" cy=\"2\" r=\"1\" fill=\"%23e2e8f0\"/></svg>') repeat;">
          <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="color:#ef4444;">timeline</span> Proyeksi Pensiun (5 Tahun)
          </h2>
          <p style="color:#64748b;font-size:13px;margin-bottom:24px;">Deteksi dini kebutuhan rekrutmen CASN / PPPK berdasarkan Batas Usia Pensiun (BUP).</p>
          <div style="display:flex;align-items:flex-end;height:150px;gap:8px;border-bottom:1px solid #cbd5e1;padding-bottom:8px;">
            <?php 
            $pensiunData = [
                ['tahun' => 2025, 'jumlah' => 15, 'h' => '40%'],
                ['tahun' => 2026, 'jumlah' => 28, 'h' => '70%'],
                ['tahun' => 2027, 'jumlah' => 10, 'h' => '25%'],
                ['tahun' => 2028, 'jumlah' => 45, 'h' => '95%'],
                ['tahun' => 2029, 'jumlah' => 32, 'h' => '80%']
            ];
            foreach ($pensiunData as $pd): ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;">
              <div style="color:#1a1d1f;font-size:12px;font-weight:800;"><?= $pd['jumlah'] ?></div>
              <div style="width:100%;background:linear-gradient(to top, #ef4444, #f87171);height:<?= $pd['h'] ?>;border-radius:4px 4px 0 0;opacity:0.8;"></div>
              <div style="font-size:11px;color:#64748b;font-weight:600;"><?= $pd['tahun'] ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        </div>

        <!-- Kolom Kanan -->
        <div style="display:flex;flex-direction:column;gap:24px;">
            <!-- Widget Absensi Mandiri -->
            <?php include __DIR__ . '/partials/widget_absensi.php'; ?>
        </div>
      </div>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
