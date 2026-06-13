<?php
// ============================================================
// dashboard_atasan.php — Approval Center untuk Atasan Langsung
// Menyetujui Cuti, SKP, dan memantau kehadiran Tim.
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
if (!hasRole('atasan') && !hasRole('super_admin')) {
    redirect('/simpekabjmk/dashboard.php');
}

$currentPage = 'dashboard_atasan';
$pageTitle   = 'Dashboard Atasan';
$user        = currentUser();

// 1. Dapatkan Divisi Atasan
$stmtDiv = $pdo->prepare("SELECT divisi FROM pegawai WHERE user_id = ?");
$stmtDiv->execute([$user['id']]);
$divisi = $stmtDiv->fetchColumn();

if (!$divisi) $divisi = 'Umum';

// 2. Query Approval Layanan (pending_atasan) untuk divisi ini
$stmtApproveLayanan = $pdo->prepare("
    SELECT pl.*, u.nama, p.nip 
    FROM pengajuan_layanan pl
    JOIN users u ON u.id = pl.user_id
    JOIN pegawai p ON p.user_id = u.id
    WHERE pl.status = 'pending_atasan' AND p.divisi = ?
    ORDER BY pl.created_at ASC
");
$stmtApproveLayanan->execute([$divisi]);
$queueLayanan = $stmtApproveLayanan->fetchAll();

// 3. Query Reviu SKP (submitted)
$stmtApproveSKP = $pdo->prepare("
    SELECT k.*, u.nama, p.nip 
    FROM kinerja_skp k
    JOIN users u ON u.id = k.user_id
    JOIN pegawai p ON p.user_id = u.id
    WHERE k.status = 'submitted' AND p.divisi = ?
    ORDER BY k.created_at ASC
");
$stmtApproveSKP->execute([$divisi]);
$queueSKP = $stmtApproveSKP->fetchAll();

// 4. Rekap Absensi Tim Hari Ini
$today = date('Y-m-d');
$stmtAbsensiTim = $pdo->prepare("
    SELECT 
        COUNT(p.id) as total_pegawai,
        SUM(CASE WHEN a.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) as alpha,
        SUM(CASE WHEN a.status IN ('izin', 'sakit') THEN 1 ELSE 0 END) as izin
    FROM pegawai p
    LEFT JOIN absensi a ON a.user_id = p.user_id AND a.tanggal = ?
    WHERE p.divisi = ? AND p.user_id != ?
");
$stmtAbsensiTim->execute([$today, $divisi, $user['id']]);
$timStats = $stmtAbsensiTim->fetch();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <div>
          <h1 class="section-title">Approval Center</h1>
          <p class="section-subtitle">Dashboard Atasan — Divisi <strong><?= e($divisi) ?></strong></p>
        </div>
        <div style="display:flex;gap:12px;">
            <a href="/simpekabjmk/absensi_tim.php" class="btn-ghost" style="border:1px solid #eaecf0;background:#ffffff;">
                <span class="material-symbols-outlined">group</span> Rekap Absensi Tim
            </a>
        </div>
      </div>

      <!-- Overview Cards -->
      <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:32px;">
        <div class="card" style="display:flex;align-items:center;gap:16px;background:linear-gradient(135deg, #f59e0b, #d97706);color:#fff;border:none;">
          <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:24px;">pending_actions</span>
          </div>
          <div>
            <div style="font-size:24px;font-weight:800;"><?= count($queueLayanan) + count($queueSKP) ?></div>
            <div style="font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">Menunggu Approval</div>
          </div>
        </div>
        
        <div class="card" style="display:flex;align-items:center;gap:16px;">
          <div style="width:48px;height:48px;background:#f0fdf4;color:#10b981;border-radius:12px;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:24px;">check_circle</span>
          </div>
          <div>
            <div style="font-size:24px;font-weight:800;color:#1a1d1f;"><?= $timStats['hadir'] ?? 0 ?> / <?= $timStats['total_pegawai'] ?? 0 ?></div>
            <div style="font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">Tim Hadir Hari Ini</div>
          </div>
        </div>

        <div class="card" style="display:flex;align-items:center;gap:16px;">
          <div style="width:48px;height:48px;background:#fef2f2;color:#ef4444;border-radius:12px;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:24px;">cancel</span>
          </div>
          <div>
            <div style="font-size:24px;font-weight:800;color:#1a1d1f;"><?= $timStats['alpha'] ?? 0 ?></div>
            <div style="font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">Alpha Hari Ini</div>
          </div>
        </div>
      </div>

      <div style="display:grid;gap:24px;grid-template-columns:1fr;@media(min-width:1024px){grid-template-columns:2fr 1fr;}">
        
        <!-- Kolom Kiri: Antrean Kerja -->
        <div style="display:flex;flex-direction:column;gap:24px;">
          <!-- Antrean Cuti / Izin -->
        <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <div style="padding:16px 20px;border-bottom:1px solid #eaecf0;background:#ffffff;display:flex;justify-content:space-between;align-items:center;">
            <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;margin:0;display:flex;align-items:center;gap:8px;">
              <span class="material-symbols-outlined" style="color:#f59e0b;background:#fffbeb;padding:6px;border-radius:8px;">free_cancellation</span> 
              Persetujuan Layanan (<?= count($queueLayanan) ?>)
            </h2>
          </div>
          <div style="padding:0;">
            <?php if (empty($queueLayanan)): ?>
              <div style="padding:48px;text-align:center;color:#94a3b8;font-size:14px;display:flex;flex-direction:column;align-items:center;gap:12px;">
                <span class="material-symbols-outlined" style="font-size:48px;opacity:0.5;">done_all</span>
                Tidak ada pengajuan layanan yang menunggu persetujuan.
              </div>
            <?php else: ?>
              <ul style="list-style:none;margin:0;padding:0;">
                <?php foreach ($queueLayanan as $l): ?>
                <li style="padding:20px;border-bottom:1px solid #f1f5f9;display:flex;flex-direction:column;gap:12px;">
                  <div style="display:flex;justify-content:space-between;align-items:start;">
                    <div style="display:flex;gap:12px;align-items:center;">
                      <div class="avatar avatar-sm" style="background:#e0f2fe;color:#0ea5e9;font-weight:700;"><?= e(getInisial($l['nama'])) ?></div>
                      <div>
                        <div style="font-weight:700;color:#1a1d1f;font-size:14px;"><?= e($l['nama']) ?></div>
                        <div style="font-size:12px;color:#64748b;font-family:'JetBrains Mono',monospace;"><?= e($l['nip']) ?></div>
                      </div>
                    </div>
                    <span class="badge" style="background:#fef3c7;color:#d97706;border:1px solid #fde68a;">Menunggu</span>
                  </div>
                  <div style="background:#f8fafc;padding:12px;border-radius:8px;border:1px dashed #cbd5e1;">
                    <div style="font-weight:600;color:#334155;font-size:13px;margin-bottom:4px;"><?= e($l['jenis']) ?></div>
                    <div style="font-size:12px;color:#64748b;margin-bottom:8px;">
                      <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">event</span> 
                      <?= date('d M Y', strtotime($l['tanggal_mulai'])) ?> s.d <?= date('d M Y', strtotime($l['tanggal_selesai'])) ?>
                    </div>
                    <div style="font-size:13px;color:#475569;">"<?= e($l['keterangan']) ?>"</div>
                  </div>
                  <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button class="btn-danger" style="padding:6px 12px;font-size:12px;"><span class="material-symbols-outlined" style="font-size:16px;">close</span> Tolak</button>
                    <button class="btn-primary" style="padding:6px 12px;font-size:12px;"><span class="material-symbols-outlined" style="font-size:16px;">check</span> Setujui</button>
                  </div>
                </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <!-- Antrean SKP -->
        <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <div style="padding:16px 20px;border-bottom:1px solid #eaecf0;background:#ffffff;display:flex;justify-content:space-between;align-items:center;">
            <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;margin:0;display:flex;align-items:center;gap:8px;">
              <span class="material-symbols-outlined" style="color:#10b981;background:#f0fdf4;padding:6px;border-radius:8px;">psychology</span> 
              Reviu Kinerja / SKP (<?= count($queueSKP) ?>)
            </h2>
          </div>
          <div style="padding:0;">
            <?php if (empty($queueSKP)): ?>
              <div style="padding:48px;text-align:center;color:#94a3b8;font-size:14px;display:flex;flex-direction:column;align-items:center;gap:12px;">
                <span class="material-symbols-outlined" style="font-size:48px;opacity:0.5;">done_all</span>
                Tidak ada dokumen SKP yang menunggu reviu.
              </div>
            <?php else: ?>
              <ul style="list-style:none;margin:0;padding:0;">
                <?php foreach ($queueSKP as $skp): ?>
                <li style="padding:20px;border-bottom:1px solid #f1f5f9;display:flex;flex-direction:column;gap:12px;">
                  <div style="display:flex;justify-content:space-between;align-items:start;">
                    <div style="display:flex;gap:12px;align-items:center;">
                      <div class="avatar avatar-sm" style="background:#e0e7ff;color:#4f46e5;font-weight:700;"><?= e(getInisial($skp['nama'])) ?></div>
                      <div>
                        <div style="font-weight:700;color:#1a1d1f;font-size:14px;"><?= e($skp['nama']) ?></div>
                        <div style="font-size:12px;color:#64748b;font-weight:600;">SKP Bulan: <?= date('M Y', strtotime($skp['bulan'].'-01')) ?></div>
                      </div>
                    </div>
                    <button class="btn-primary" style="padding:6px 12px;font-size:12px;">Beri Nilai</button>
                  </div>
                  <div style="background:#f8fafc;padding:12px;border-radius:8px;border:1px dashed #cbd5e1;font-size:13px;color:#475569;">
                    "<?= e($skp['kegiatan']) ?>"
                  </div>
                </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
        </div>

        <!-- Kolom Kanan: Widget Absensi Mandiri -->
        <div style="display:flex;flex-direction:column;gap:24px;">
          <?php include __DIR__ . '/partials/widget_absensi.php'; ?>
        </div>

      </div>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
