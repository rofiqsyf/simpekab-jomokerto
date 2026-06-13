<?php
// ============================================================
// layanan_verifikasi.php — Verifikasi Layanan (Admin BKPSDM)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
requireRole(['admin_bkpsdm', 'super_admin']);

$currentPage = 'layanan_verifikasi';
$pageTitle   = 'Verifikasi Layanan BKPSDM';

// Jika form disubmit (Setuju / Tolak verifikasi akhir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $layanan_id = (int)$_POST['layanan_id'];
    $keputusan  = $_POST['keputusan']; // 'approve' atau 'reject'
    
    $statusBaru = ($keputusan === 'approve') ? 'approved_bkpsdm' : 'rejected';
    $pdo->prepare("UPDATE pengajuan_layanan SET status = ? WHERE id = ?")
        ->execute([$statusBaru, $layanan_id]);
    
    setFlash('success', 'Verifikasi BKPSDM berhasil disimpan: ' . strtoupper($statusBaru));
    redirect('/simpekabjmk/layanan_verifikasi.php');
}

// Ambil semua pengajuan layanan yang sudah disetujui atasan (menunggu verifikasi BKPSDM)
$stmt = $pdo->prepare("
    SELECT l.*, u.nama, p.nip, p.divisi, p.posisi
    FROM pengajuan_layanan l
    JOIN users u ON u.id = l.user_id
    JOIN pegawai p ON p.user_id = u.id
    WHERE l.status IN ('approved_atasan', 'approved_bkpsdm', 'rejected')
    ORDER BY FIELD(l.status, 'approved_atasan', 'approved_bkpsdm', 'rejected'), l.created_at DESC
");
$stmt->execute();
$layanan_list = $stmt->fetchAll();

generateCsrfToken();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      <?= renderFlash() ?>

      <h1 class="section-title">Verifikasi Layanan Kepegawaian (BKPSDM)</h1>
      <p class="section-subtitle">Proses akhir penerbitan Surat Keputusan / Persetujuan untuk Cuti, Izin, dan Layanan Kepegawaian</p>

      <!-- Layout Card UIUX Baru -->
      <div class="card" style="padding:0;overflow:hidden;margin-top:32px;border:1px solid #eaecf0;background:#ffffff;border-radius:16px;">
        <div style="padding:20px 24px;border-bottom:1px solid #eaecf0;display:flex;align-items:center;gap:12px;">
          <div style="width:32px;height:32px;border-radius:8px;background:#ecfdf5;color:#10b981;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:18px;">checklist</span>
          </div>
          <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;margin:0;">Verifikasi Akhir & Penerbitan SK (<?= count(array_filter($layanan_list, fn($l) => $l['status'] === 'approved_atasan')) ?>)</h2>
        </div>

        <div style="padding:24px;display:flex;flex-direction:column;gap:24px;">
          <?php if (empty($layanan_list)): ?>
            <div style="text-align:center;padding:48px;color:#64748b;font-weight:500;">Belum ada pengajuan yang membutuhkan verifikasi BKPSDM.</div>
          <?php else: ?>
            <?php foreach ($layanan_list as $l): ?>
              <!-- Card Item -->
              <div style="border:1px solid #eaecf0;border-radius:12px;padding:20px;">
                
                <!-- Header -->
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;flex-wrap:wrap;gap:16px;">
                  <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#e0e7ff;color:#4f46e5;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:14px;">
                      <?= e(getInisial($l['nama'])) ?>
                    </div>
                    <div>
                      <div style="font-weight:700;color:#1a1d1f;font-size:15px;"><?= e($l['nama']) ?></div>
                      <div style="color:#64748b;font-size:13px;"><?= e($l['divisi']) ?> &bull; NIP: <?= e($l['nip']) ?></div>
                    </div>
                  </div>
                  
                  <?php if ($l['status'] === 'approved_atasan'): ?>
                    <span style="background:#eff6ff;color:#3b82f6;padding:6px 16px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid #bfdbfe;">Approved by Atasan</span>
                  <?php elseif ($l['status'] === 'approved_bkpsdm'): ?>
                    <span style="background:#dcfce7;color:#166534;padding:6px 16px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid #bbf7d0;">Selesai (SK Terbit)</span>
                  <?php elseif ($l['status'] === 'rejected'): ?>
                    <span style="background:#fef2f2;color:#ef4444;padding:6px 16px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid #fecaca;">Ditolak</span>
                  <?php endif; ?>
                </div>

                <!-- Body (Keterangan box) -->
                <div style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:16px;margin-bottom:20px;">
                  <div style="font-weight:700;color:#1a1d1f;font-size:14px;margin-bottom:4px;"><?= e($l['jenis']) ?></div>
                  <div style="color:#64748b;font-size:13px;margin-bottom:12px;">
                    Tanggal: <?= date('d M Y', strtotime($l['tanggal_mulai'])) ?> <?= $l['tanggal_mulai'] !== $l['tanggal_selesai'] ? ' s/d ' . date('d M Y', strtotime($l['tanggal_selesai'])) : '' ?>
                  </div>
                  <div style="color:#475569;font-size:14px;font-style:italic;">
                    "<?= nl2br(e($l['keterangan'])) ?>"
                  </div>
                </div>

                <!-- Footer (Aksi) -->
                <div style="display:flex;justify-content:flex-end;gap:12px;">
                  <?php if ($l['status'] === 'approved_atasan'): ?>
                    <form action="/simpekabjmk/layanan_verifikasi.php" method="POST" style="margin:0;" onsubmit="return confirm('Tolak layanan ini pada tahap akhir?');">
                      <?= csrfInput() ?>
                      <input type="hidden" name="layanan_id" value="<?= $l['id'] ?>">
                      <input type="hidden" name="keputusan" value="reject">
                      <button type="submit" style="display:flex;align-items:center;gap:6px;padding:8px 20px;border-radius:24px;border:1px solid #eaecf0;background:#ffffff;color:#1a1d1f;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                        <span class="material-symbols-outlined" style="font-size:16px;">close</span> Tolak
                      </button>
                    </form>
                    
                    <form action="/simpekabjmk/layanan_verifikasi.php" method="POST" style="margin:0;" onsubmit="return confirm('Terbitkan SK dan sinkronisasi ke SIASN?');">
                      <?= csrfInput() ?>
                      <input type="hidden" name="layanan_id" value="<?= $l['id'] ?>">
                      <input type="hidden" name="keputusan" value="approve">
                      <button type="submit" style="display:flex;align-items:center;gap:6px;padding:8px 20px;border-radius:24px;border:none;background:#10b981;color:#ffffff;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 12px rgba(16,185,129,0.2);" onmouseover="this.style.filter='brightness(1.05)'" onmouseout="this.style.filter='none'">
                        <span class="material-symbols-outlined" style="font-size:16px;">upload</span> Terbitkan SK & Sinkron SIASN
                      </button>
                    </form>
                  <?php else: ?>
                    <button type="button" style="display:flex;align-items:center;gap:6px;padding:8px 20px;border-radius:24px;border:1px solid #eaecf0;background:#f8fafc;color:#64748b;font-weight:600;font-size:13px;cursor:not-allowed;">
                      <span class="material-symbols-outlined" style="font-size:16px;">lock</span> Sudah Diproses
                    </button>
                  <?php endif; ?>
                </div>

              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
