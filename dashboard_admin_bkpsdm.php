<?php
// ============================================================
// dashboard_admin_bkpsdm.php — Verifikator & Master Data
// Verifikasi Dokumen, CRUD Master Data, Mutasi
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
if (!hasRole('admin_bkpsdm') && !hasRole('super_admin')) {
    redirect('/simpekabjmk/dashboard.php');
}

$currentPage = 'dashboard_admin_bkpsdm';
$pageTitle   = 'Dashboard BKPSDM';
$user        = currentUser();

// 1. Verifikasi Queue (approved_atasan)
$stmtApproveBKPSDM = $pdo->prepare("
    SELECT pl.*, u.nama, p.nip, p.divisi
    FROM pengajuan_layanan pl
    JOIN users u ON u.id = pl.user_id
    JOIN pegawai p ON p.user_id = u.id
    WHERE pl.status = 'approved_atasan'
    ORDER BY pl.updated_at ASC
");
$stmtApproveBKPSDM->execute();
$queueVerifikasi = $stmtApproveBKPSDM->fetchAll();

// 2. Statistik Pegawai per Divisi
$stmtDivStats = $pdo->prepare("
    SELECT divisi, COUNT(*) as total 
    FROM pegawai 
    GROUP BY divisi 
    ORDER BY total DESC
");
$stmtDivStats->execute();
$divStats = $stmtDivStats->fetchAll();

// 3. Status Keaktifan
$stmtAktifStats = $pdo->prepare("
    SELECT status, COUNT(*) as total 
    FROM pegawai 
    GROUP BY status
");
$stmtAktifStats->execute();
$aktifStats = [];
foreach ($stmtAktifStats->fetchAll() as $s) {
    $aktifStats[$s['status']] = $s['total'];
}
$totalAktif = $aktifStats['aktif'] ?? 0;
$totalNonaktif = ($aktifStats['nonaktif'] ?? 0) + ($aktifStats['cuti'] ?? 0);
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      
      <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:32px;">
        <div>
          <h1 class="section-title">BKPSDM Workspace</h1>
          <p class="section-subtitle">Verifikasi Dokumen Elektronik & Master Data ASN</p>
        </div>
        <div style="display:flex;gap:12px;">
            <a href="/simpekabjmk/pegawai.php" class="btn-primary">
                <span class="material-symbols-outlined">manage_accounts</span> Kelola Master Data
            </a>
        </div>
      </div>

      <!-- Summary Metrics -->
      <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:32px;">
        <div class="card" style="display:flex;align-items:center;gap:16px;">
          <div style="width:52px;height:52px;background:#e0e7ff;color:#4f46e5;border-radius:12px;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:28px;">badge</span>
          </div>
          <div>
            <div style="font-size:24px;font-weight:800;color:#1a1d1f;"><?= $totalAktif ?></div>
            <div style="font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">Pegawai Aktif</div>
          </div>
        </div>
        
        <div class="card" style="display:flex;align-items:center;gap:16px;">
          <div style="width:52px;height:52px;background:#fef2f2;color:#ef4444;border-radius:12px;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:28px;">person_off</span>
          </div>
          <div>
            <div style="font-size:24px;font-weight:800;color:#1a1d1f;"><?= $totalNonaktif ?></div>
            <div style="font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">Cuti / Nonaktif</div>
          </div>
        </div>

        <div class="card" style="display:flex;align-items:center;gap:16px;">
          <div style="width:52px;height:52px;background:#fffbeb;color:#d97706;border-radius:12px;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:28px;">verified</span>
          </div>
          <div>
            <div style="font-size:24px;font-weight:800;color:#1a1d1f;"><?= count($queueVerifikasi) ?></div>
            <div style="font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">Perlu Verifikasi</div>
          </div>
        </div>
      </div>

      <div style="display:grid;gap:24px;grid-template-columns:1fr;@media(min-width:1024px){grid-template-columns:2fr 1fr;}">
        
        <!-- Queue Verifikasi Akhir -->
        <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <div style="padding:16px 20px;border-bottom:1px solid #eaecf0;background:#ffffff;display:flex;justify-content:space-between;align-items:center;">
            <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;margin:0;display:flex;align-items:center;gap:8px;">
              <span class="material-symbols-outlined" style="color:#10b981;background:#f0fdf4;padding:6px;border-radius:8px;">fact_check</span> 
              Verifikasi Akhir & Penerbitan SK (<?= count($queueVerifikasi) ?>)
            </h2>
          </div>
          <div style="padding:0;">
            <?php if (empty($queueVerifikasi)): ?>
              <div style="padding:48px;text-align:center;color:#94a3b8;font-size:14px;">
                <span class="material-symbols-outlined" style="font-size:48px;opacity:0.5;margin-bottom:12px;display:block;">check_circle</span>
                Semua dokumen telah diverifikasi.
              </div>
            <?php else: ?>
              <ul style="list-style:none;margin:0;padding:0;">
                <?php foreach ($queueVerifikasi as $q): ?>
                <li style="padding:20px;border-bottom:1px solid #f1f5f9;">
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <div style="display:flex;gap:12px;align-items:center;">
                      <div class="avatar avatar-sm" style="background:#e0e7ff;color:#4f46e5;font-weight:700;"><?= e(getInisial($q['nama'])) ?></div>
                      <div>
                        <div style="font-weight:700;color:#1a1d1f;font-size:14px;"><?= e($q['nama']) ?></div>
                        <div style="font-size:12px;color:#64748b;"><?= e($q['divisi']) ?> • NIP: <?= e($q['nip']) ?></div>
                      </div>
                    </div>
                    <span class="badge" style="background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;">Approved by Atasan</span>
                  </div>
                  <div style="background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:12px;">
                    <div style="font-weight:600;color:#334155;font-size:13px;"><?= e($q['jenis']) ?></div>
                    <div style="font-size:12px;color:#64748b;margin-bottom:4px;">Tanggal: <?= date('d M Y', strtotime($q['tanggal_mulai'])) ?></div>
                    <div style="font-size:13px;color:#475569;">"<?= e($q['keterangan']) ?>"</div>
                  </div>
                  <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button class="btn-ghost" style="padding:6px 12px;font-size:12px;border:1px solid #cbd5e1;"><span class="material-symbols-outlined" style="font-size:16px;">close</span> Tolak</button>
                    <button class="btn-primary" style="padding:6px 12px;font-size:12px;background:#10b981;"><span class="material-symbols-outlined" style="font-size:16px;">publish</span> Terbitkan SK & Sinkron SIASN</button>
                  </div>
                </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <!-- Kolom Kanan -->
        <div style="display:flex;flex-direction:column;gap:24px;">
            <!-- Widget Absensi Mandiri -->
            <?php include __DIR__ . '/partials/widget_absensi.php'; ?>

            <!-- Sebaran Pegawai -->
            <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
            <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="color:#0ea5e9;">account_tree</span> Peta Bezetting (Divisi)
            </h2>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($divStats as $ds): 
                    $percent = $totalAktif > 0 ? round(($ds['total'] / ($totalAktif + $totalNonaktif)) * 100) : 0;
                ?>
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;font-weight:600;">
                        <span style="color:#475569;"><?= e($ds['divisi']) ?></span>
                        <span style="color:#1a1d1f;"><?= $ds['total'] ?> org (<?= $percent ?>%)</span>
                    </div>
                    <div style="width:100%;background:#f1f5f9;height:8px;border-radius:4px;overflow:hidden;">
                        <div style="height:100%;background:#3b82f6;width:<?= $percent ?>%;border-radius:4px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            </div>

            <!-- Pemrosesan Massal -->
            <div class="card" style="border:1px solid #eaecf0;background:linear-gradient(135deg, #4f46e5, #4338ca);color:#fff;">
                <h2 style="font-size:16px;font-weight:700;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                    <span class="material-symbols-outlined">auto_awesome</span> Kenaikan Gaji Berkala
                </h2>
                <p style="font-size:13px;opacity:0.9;margin-bottom:16px;">Terdapat 12 pegawai yang memenuhi syarat KGB otomatis bulan ini.</p>
                <button class="btn-primary" style="background:#ffffff;color:#4f46e5;width:100%;justify-content:center;">
                    Proses Massal Sekarang
                </button>
            </div>
        </div>

      </div>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
