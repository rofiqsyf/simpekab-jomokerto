<?php
// ============================================================
// kinerja_skp.php — E-Kinerja Pegawai (Input SKP)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
requireRole(['pegawai', 'atasan', 'super_admin']); // Pegawai & Atasan bisa isi SKP

$currentPage = 'kinerja';
$pageTitle   = 'E-Kinerja (SKP)';
$user        = currentUser();

// Ambil data SKP pengguna ini
$stmt = $pdo->prepare("SELECT * FROM kinerja_skp WHERE user_id = ? ORDER BY bulan DESC");
$stmt->execute([$user['id']]);
$skp_list = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $bulan = trim($_POST['bulan'] ?? '');
    $kegiatan = trim($_POST['kegiatan'] ?? '');
    $target = (int)($_POST['target'] ?? 0);
    $realisasi = (int)($_POST['realisasi'] ?? 0);

    if ($bulan && $kegiatan && $target > 0) {
        // Hitung persentase
        $capaian = min(100, round(($realisasi / $target) * 100));
        
        $pdo->prepare("INSERT INTO kinerja_skp (user_id, bulan, kegiatan, target, realisasi, capaian) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$user['id'], $bulan, $kegiatan, $target, $realisasi, $capaian]);
        
        setFlash('success', 'SKP bulan ' . $bulan . ' berhasil ditambahkan.');
        redirect('/simpekabjmk/kinerja_skp.php');
    } else {
        setFlash('error', 'Semua form wajib diisi dan target harus > 0.');
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

      <h1 class="section-title">Sasaran Kinerja Pegawai (E-Kinerja)</h1>
      <p class="section-subtitle">Isi capaian kinerja bulanan Anda untuk dilaporkan ke Atasan Langsung</p>

      <div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;margin-top:32px;">
        <!-- Form Tambah SKP -->
        <div class="card" style="border:1px solid #eaecf0;align-self:start;">
          <h2 style="font-size:16px;font-weight:700;margin-bottom:20px;border-bottom:1px solid #eaecf0;padding-bottom:12px;">Input Kegiatan Baru</h2>
          <form method="POST" style="display:flex;flex-direction:column;gap:16px;">
            <?= csrfInput() ?>
            <div class="form-group">
              <label class="label">Bulan Laporan</label>
              <input type="month" name="bulan" value="<?= date('Y-m') ?>" class="input-card" required />
            </div>
            <div class="form-group">
              <label class="label">Uraian Kegiatan Tugas Jabatan</label>
              <textarea name="kegiatan" rows="3" class="input-card" placeholder="Contoh: Menyusun laporan keuangan bulanan..." required></textarea>
            </div>
            <div class="form-row cols-2">
              <div class="form-group">
                <label class="label">Target Kuantitas</label>
                <input type="number" name="target" placeholder="Mis: 10" class="input-card" min="1" required />
              </div>
              <div class="form-group">
                <label class="label">Realisasi</label>
                <input type="number" name="realisasi" placeholder="Mis: 8" class="input-card" min="0" required />
              </div>
            </div>
            <button type="submit" class="btn-primary" style="margin-top:8px;">
              <span class="material-symbols-outlined">add_task</span> Tambah SKP
            </button>
          </form>
        </div>

        <!-- Daftar SKP -->
        <div class="card" style="border:1px solid #eaecf0;padding:0;overflow:hidden;">
          <div style="padding:20px;border-bottom:1px solid #eaecf0;background:#f8fafc;">
            <h2 style="font-size:16px;font-weight:700;">Riwayat SKP Saya</h2>
          </div>
          <table class="data-table">
            <thead>
              <tr>
                <th>Bulan</th>
                <th>Kegiatan</th>
                <th>Realisasi / Target</th>
                <th>Capaian</th>
                <th>Status Nilai</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($skp_list)): ?>
              <tr><td colspan="5" style="text-align:center;padding:32px;color:#64748b;">Belum ada entri SKP.</td></tr>
              <?php else: ?>
              <?php foreach ($skp_list as $skp): ?>
              <tr>
                <td style="font-weight:600;"><?= date('M Y', strtotime($skp['bulan'] . '-01')) ?></td>
                <td><?= e($skp['kegiatan']) ?></td>
                <td style="font-weight:600;"><?= e($skp['realisasi']) ?> / <?= e($skp['target']) ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <div style="flex-grow:1;height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;">
                      <div style="height:100%;background:<?= $skp['capaian'] >= 80 ? '#10b981' : ($skp['capaian'] >= 50 ? '#f59e0b' : '#ef4444') ?>;width:<?= $skp['capaian'] ?>%;"></div>
                    </div>
                    <span style="font-size:12px;font-weight:700;"><?= $skp['capaian'] ?>%</span>
                  </div>
                </td>
                <td>
                  <?php if ($skp['nilai_atasan']): ?>
                    <span class="badge badge-active">Dinilai: <?= e($skp['nilai_atasan']) ?></span>
                  <?php else: ?>
                    <span class="badge badge-secondary">Menunggu</span>
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
