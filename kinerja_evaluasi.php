<?php
// ============================================================
// kinerja_evaluasi.php — E-Kinerja Evaluasi (Atasan)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
requireRole(['atasan', 'super_admin']); 

$currentPage = 'kinerja_evaluasi';
$pageTitle   = 'Evaluasi SKP';
$user        = currentUser();

// Ambil pegawai di divisinya, kecuali dirinya sendiri
$stmtPegawai = $pdo->prepare("
    SELECT u.id, u.nama, p.nip, p.posisi
    FROM users u
    JOIN pegawai p ON p.user_id = u.id
    WHERE p.divisi = (SELECT divisi FROM pegawai WHERE user_id = ?) AND u.id != ?
");
$stmtPegawai->execute([$user['id'], $user['id']]);
$bawahan = $stmtPegawai->fetchAll();
$bawahanIds = array_column($bawahan, 'id');

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $skp_id = (int)$_POST['skp_id'];
    $nilai  = (int)$_POST['nilai'];
    
    // Verifikasi SKP ini milik bawahan
    $stmtCek = $pdo->prepare("SELECT user_id FROM kinerja_skp WHERE id = ?");
    $stmtCek->execute([$skp_id]);
    $skp = $stmtCek->fetch();
    
    if ($skp && in_array($skp['user_id'], $bawahanIds)) {
        $pdo->prepare("UPDATE kinerja_skp SET nilai_atasan = ?, status = 'reviewed' WHERE id = ?")
            ->execute([$nilai, $skp_id]);
        setFlash('success', 'Penilaian SKP berhasil disimpan.');
    } else {
        setFlash('error', 'Validasi gagal. Pegawai bukan bawahan Anda.');
    }
    redirect('/simpekabjmk/kinerja_evaluasi.php');
}

// Ambil semua SKP bawahan yang belum dinilai
if (!empty($bawahanIds)) {
    $placeholders = str_repeat('?,', count($bawahanIds) - 1) . '?';
    $stmtSKP = $pdo->prepare("
        SELECT k.*, u.nama, p.nip 
        FROM kinerja_skp k
        JOIN users u ON u.id = k.user_id
        JOIN pegawai p ON p.user_id = u.id
        WHERE k.user_id IN ($placeholders)
        ORDER BY k.created_at DESC
    ");
    $stmtSKP->execute($bawahanIds);
    $skp_list = $stmtSKP->fetchAll();
} else {
    $skp_list = [];
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

      <h1 class="section-title">Evaluasi Kinerja Bawahan</h1>
      <p class="section-subtitle">Review dan berikan penilaian pada capaian SKP staf Anda</p>

      <div class="card" style="padding:0;overflow:hidden;margin-top:32px;border:1px solid #eaecf0;">
        <div style="padding:20px;border-bottom:1px solid #eaecf0;background:#f8fafc;">
          <h2 style="font-size:16px;font-weight:700;">Daftar Pengajuan SKP Bawahan</h2>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Pegawai</th>
              <th>Bulan</th>
              <th>Kegiatan & Capaian</th>
              <th>Status / Nilai</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($skp_list)): ?>
            <tr><td colspan="5" style="text-align:center;padding:32px;color:#64748b;">Belum ada pengajuan SKP dari bawahan.</td></tr>
            <?php else: ?>
            <?php foreach ($skp_list as $skp): ?>
            <tr>
              <td>
                <div style="font-weight:600;"><?= e($skp['nama']) ?></div>
                <div style="color:#64748b;font-size:12px;font-family:monospace;"><?= e($skp['nip']) ?></div>
              </td>
              <td style="font-weight:600;"><?= date('M Y', strtotime($skp['bulan'] . '-01')) ?></td>
              <td>
                <div style="margin-bottom:4px;"><?= e($skp['kegiatan']) ?></div>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="flex-grow:1;height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden;">
                    <div style="height:100%;background:<?= $skp['capaian'] >= 80 ? '#10b981' : ($skp['capaian'] >= 50 ? '#f59e0b' : '#ef4444') ?>;width:<?= $skp['capaian'] ?>%;"></div>
                  </div>
                  <span style="font-size:11px;font-weight:700;"><?= $skp['capaian'] ?>%</span>
                </div>
              </td>
              <td>
                <?php if ($skp['nilai_atasan']): ?>
                  <span class="badge badge-active">Dinilai: <?= e($skp['nilai_atasan']) ?></span>
                <?php else: ?>
                  <span class="badge badge-warning">Menunggu</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!$skp['nilai_atasan']): ?>
                <form method="POST" style="display:flex;gap:8px;align-items:center;">
                  <?= csrfInput() ?>
                  <input type="hidden" name="skp_id" value="<?= $skp['id'] ?>">
                  <input type="number" name="nilai" min="0" max="100" class="input-card" style="width:70px;padding:6px;height:auto;" placeholder="Nilai" required>
                  <button type="submit" class="btn-primary" style="padding:6px 12px;font-size:12px;">Simpan</button>
                </form>
                <?php else: ?>
                <span style="color:#10b981;font-size:13px;font-weight:600;display:flex;align-items:center;gap:4px;">
                  <span class="material-symbols-outlined" style="font-size:16px;">check_circle</span> Selesai
                </span>
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
<?php require __DIR__ . '/partials/footer.php'; ?>
