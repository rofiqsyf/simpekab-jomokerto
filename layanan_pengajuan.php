<?php
// ============================================================
// layanan_pengajuan.php — Pengajuan Layanan Mandiri (Pegawai)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
$currentPage = 'layanan_pengajuan';
$pageTitle   = 'Pengajuan Layanan';
$user        = currentUser();

// Ambil riwayat pengajuan layanan pengguna ini
$stmt = $pdo->prepare("SELECT * FROM pengajuan_layanan WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$layanan_list = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $jenis = $_POST['jenis'] ?? '';
    $tgl_mulai = $_POST['tgl_mulai'] ?? '';
    $tgl_selesai = $_POST['tgl_selesai'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($jenis && $tgl_mulai && $tgl_selesai && $keterangan) {
        $pdo->prepare("INSERT INTO pengajuan_layanan (user_id, jenis, tanggal_mulai, tanggal_selesai, keterangan, status) VALUES (?, ?, ?, ?, ?, 'pending_atasan')")
            ->execute([$user['id'], $jenis, $tgl_mulai, $tgl_selesai, $keterangan]);
        
        setFlash('success', 'Pengajuan ' . $jenis . ' berhasil dikirim ke Atasan.');
        redirect('/simpekabjmk/layanan_pengajuan.php');
    } else {
        setFlash('error', 'Semua form wajib diisi.');
    }
}

generateCsrfToken();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      <?= renderFlash() ?>

      <h1 class="section-title">Layanan Kepegawaian</h1>
      <p class="section-subtitle">Pengajuan Cuti, Izin, dan Layanan Mandiri lainnya</p>

      <div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;margin-top:32px;">
        <!-- Form Pengajuan -->
        <div class="card" style="border:1px solid #eaecf0;align-self:start;">
          <h2 style="font-size:16px;font-weight:700;margin-bottom:20px;border-bottom:1px solid #eaecf0;padding-bottom:12px;">Form Pengajuan Baru</h2>
          <form method="POST" style="display:flex;flex-direction:column;gap:16px;">
            <?= csrfInput() ?>
            <div class="form-group">
              <label class="label">Jenis Layanan</label>
              <select name="jenis" class="input-card" required>
                <option value="">— Pilih Layanan —</option>
                <option value="Cuti Tahunan">Cuti Tahunan</option>
                <option value="Cuti Sakit">Cuti Sakit</option>
                <option value="Cuti Melahirkan">Cuti Melahirkan</option>
                <option value="Izin Belajar">Izin Belajar</option>
              </select>
            </div>
            <div class="form-row cols-2">
              <div class="form-group">
                <label class="label">Tanggal Mulai</label>
                <input type="date" name="tgl_mulai" class="input-card" required />
              </div>
              <div class="form-group">
                <label class="label">Tanggal Selesai</label>
                <input type="date" name="tgl_selesai" class="input-card" required />
              </div>
            </div>
            <div class="form-group">
              <label class="label">Alasan / Keterangan</label>
              <textarea name="keterangan" rows="3" class="input-card" placeholder="Berikan alasan pengajuan secara jelas..." required></textarea>
            </div>
            <div class="form-group">
              <label class="label">Upload Lampiran (Optional)</label>
              <div style="border:1px dashed #cbd5e1;padding:16px;text-align:center;border-radius:8px;background:#f8fafc;color:#64748b;font-size:13px;cursor:not-allowed;">
                Fitur unggah file dinonaktifkan sementara
              </div>
            </div>
            <button type="submit" class="btn-primary" style="margin-top:8px;">
              <span class="material-symbols-outlined">send</span> Ajukan Sekarang
            </button>
          </form>
        </div>

        <!-- Daftar Pengajuan -->
        <div class="card" style="border:1px solid #eaecf0;padding:0;overflow:hidden;">
          <div style="padding:20px;border-bottom:1px solid #eaecf0;background:#f8fafc;">
            <h2 style="font-size:16px;font-weight:700;">Riwayat Pengajuan Saya</h2>
          </div>
          <table class="data-table">
            <thead>
              <tr>
                <th>Layanan & Tanggal</th>
                <th>Keterangan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($layanan_list)): ?>
              <tr><td colspan="3" style="text-align:center;padding:32px;color:#64748b;">Belum ada riwayat pengajuan layanan.</td></tr>
              <?php else: ?>
              <?php foreach ($layanan_list as $l): ?>
              <tr>
                <td>
                  <div style="font-weight:700;color:#1a1d1f;margin-bottom:4px;"><?= e($l['jenis']) ?></div>
                  <div style="color:#64748b;font-size:12px;display:flex;align-items:center;gap:4px;">
                    <span class="material-symbols-outlined" style="font-size:14px;">calendar_month</span>
                    <?= date('d M Y', strtotime($l['tanggal_mulai'])) ?> s.d <?= date('d M Y', strtotime($l['tanggal_selesai'])) ?>
                  </div>
                </td>
                <td style="max-width:200px;font-size:13px;color:#475569;"><?= e($l['keterangan']) ?></td>
                <td>
                  <?php if ($l['status'] === 'pending_atasan'): ?>
                    <span class="badge badge-warning">Menunggu Atasan</span>
                  <?php elseif ($l['status'] === 'approved_atasan'): ?>
                    <span class="badge badge-warning">Menunggu BKPSDM</span>
                  <?php elseif ($l['status'] === 'approved_bkpsdm'): ?>
                    <span class="badge badge-active">Selesai / Disetujui</span>
                  <?php elseif ($l['status'] === 'rejected'): ?>
                    <span class="badge badge-secondary" style="background:#fef2f2;color:#ef4444;">Ditolak</span>
                  <?php endif; ?>
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
