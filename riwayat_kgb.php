<?php
// ============================================================
// riwayat_kgb.php — Riwayat Kenaikan Gaji Berkala (KGB)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
requireRole(['admin_bkpsdm', 'super_admin', 'atasan', 'eksekutif']);

$currentPage = 'riwayat_kgb';
$pageTitle   = 'Riwayat Kenaikan Gaji Berkala';
$user        = currentUser();
$role        = $user['role'] ?? 'pegawai';

// Ambil riwayat KGB
$query = "
    SELECT r.*, u.nama, p.nip, p.divisi
    FROM riwayat_kgb r
    JOIN users u ON u.id = r.user_id
    JOIN pegawai p ON p.user_id = u.id
";
$params = [];

// Jika Atasan, hanya tampilkan pegawai di divisinya sendiri
if ($role === 'atasan') {
    $stmtDiv = $pdo->prepare("SELECT divisi FROM pegawai WHERE user_id = ?");
    $stmtDiv->execute([$user['id']]);
    $myDiv = $stmtDiv->fetchColumn();
    
    $query .= " WHERE p.divisi = ?";
    $params[] = $myDiv;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$riwayatList = $stmt->fetchAll();

generateCsrfToken();
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
          <h1 class="section-title">Laporan Riwayat KGB</h1>
          <p class="section-subtitle">Daftar rekam jejak pemrosesan otomatis Kenaikan Gaji Berkala pegawai</p>
        </div>
        <a href="/simpekabjmk/export_riwayat_kgb.php" class="btn-primary" style="padding:10px 20px;text-decoration:none;">
          <span class="material-symbols-outlined" style="font-size:20px;">download</span> Unduh CSV
        </a>
      </div>

      <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;background:#ffffff;border-radius:16px;">
        <div style="overflow-x:auto;">
          <table style="width:100%;border-collapse:collapse;text-align:left;">
            <thead>
              <tr style="background:#f8fafc;border-bottom:1px solid #eaecf0;">
                <th style="padding:16px 24px;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Pegawai</th>
                <th style="padding:16px 24px;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">TMT KGB Lama</th>
                <th style="padding:16px 24px;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">TMT KGB Baru</th>
                <th style="padding:16px 24px;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Waktu Diproses</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($riwayatList)): ?>
                <tr>
                  <td colspan="4" style="padding:48px;text-align:center;color:#64748b;font-weight:500;">
                    Belum ada riwayat pemrosesan KGB.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($riwayatList as $r): ?>
                  <tr style="border-bottom:1px solid #eaecf0;transition:background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <td style="padding:16px 24px;">
                      <div style="display:flex;align-items:center;gap:12px;">
                        <div class="avatar avatar-sm" style="background:#e0e7ff;color:#4f46e5;font-weight:700;"><?= e(getInisial($r['nama'])) ?></div>
                        <div>
                          <div style="font-weight:700;color:#1a1d1f;font-size:14px;"><?= e($r['nama']) ?></div>
                          <div style="color:#64748b;font-size:13px;"><?= e($r['divisi']) ?> &bull; <?= e($r['nip']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td style="padding:16px 24px;color:#ef4444;font-weight:600;font-size:14px;">
                      <div style="display:flex;align-items:center;gap:6px;">
                        <span class="material-symbols-outlined" style="font-size:16px;">history</span>
                        <?= date('d M Y', strtotime($r['tmt_lama'])) ?>
                      </div>
                    </td>
                    <td style="padding:16px 24px;color:#10b981;font-weight:600;font-size:14px;">
                      <div style="display:flex;align-items:center;gap:6px;">
                        <span class="material-symbols-outlined" style="font-size:16px;">update</span>
                        <?= date('d M Y', strtotime($r['tmt_baru'])) ?>
                      </div>
                    </td>
                    <td style="padding:16px 24px;color:#64748b;font-size:13px;">
                      <?= formatTanggalId($r['created_at']) ?> - <?= date('H:i', strtotime($r['created_at'])) ?>
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
