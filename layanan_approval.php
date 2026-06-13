<?php
// ============================================================
// layanan_approval.php — Approval Layanan Mandiri (Atasan)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
requireRole(['atasan', 'super_admin']);

$currentPage = 'layanan_approval';
$pageTitle   = 'Approval Layanan';
$user        = currentUser();

// Ambil pegawai di divisinya, kecuali dirinya sendiri
$stmtPegawai = $pdo->prepare("
    SELECT id FROM users 
    WHERE id IN (
        SELECT user_id FROM pegawai WHERE divisi = (SELECT divisi FROM pegawai WHERE user_id = ?)
    ) AND id != ?
");
$stmtPegawai->execute([$user['id'], $user['id']]);
$bawahanIds = $stmtPegawai->fetchAll(PDO::FETCH_COLUMN);

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $layanan_id = (int)$_POST['layanan_id'];
    $keputusan  = $_POST['keputusan']; // 'approve' atau 'reject'
    
    $stmtCek = $pdo->prepare("SELECT user_id FROM pengajuan_layanan WHERE id = ?");
    $stmtCek->execute([$layanan_id]);
    $layanan = $stmtCek->fetch();
    
    if ($layanan && in_array($layanan['user_id'], $bawahanIds)) {
        $statusBaru = ($keputusan === 'approve') ? 'approved_atasan' : 'rejected';
        $pdo->prepare("UPDATE pengajuan_layanan SET status = ? WHERE id = ?")
            ->execute([$statusBaru, $layanan_id]);
        setFlash('success', 'Keputusan berhasil disimpan: ' . strtoupper($statusBaru));
    } else {
        setFlash('error', 'Validasi gagal. Pegawai bukan bawahan Anda.');
    }
    redirect('/simpekabjmk/layanan_approval.php');
}

// Ambil pengajuan layanan bawahan
if (!empty($bawahanIds)) {
    $placeholders = str_repeat('?,', count($bawahanIds) - 1) . '?';
    $stmtLayanan = $pdo->prepare("
        SELECT l.*, u.nama, p.nip 
        FROM pengajuan_layanan l
        JOIN users u ON u.id = l.user_id
        JOIN pegawai p ON p.user_id = u.id
        WHERE l.user_id IN ($placeholders)
        ORDER BY FIELD(l.status, 'pending_atasan', 'approved_atasan', 'approved_bkpsdm', 'rejected'), l.created_at DESC
    ");
    $stmtLayanan->execute($bawahanIds);
    $layanan_list = $stmtLayanan->fetchAll();
} else {
    $layanan_list = [];
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

      <h1 class="section-title">Pusat Persetujuan (Approval Center)</h1>
      <p class="section-subtitle">Review pengajuan cuti, izin, dan layanan mandiri dari staf Anda</p>

      <div class="card" style="padding:0;overflow:hidden;margin-top:32px;border:1px solid #eaecf0;">
        <div style="padding:20px;border-bottom:1px solid #eaecf0;background:#f8fafc;">
          <h2 style="font-size:16px;font-weight:700;">Daftar Pengajuan Masuk</h2>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Pegawai</th>
              <th>Jenis Layanan</th>
              <th>Tanggal</th>
              <th>Keterangan</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($layanan_list)): ?>
            <tr><td colspan="6" style="text-align:center;padding:32px;color:#64748b;">Tidak ada pengajuan layanan dari bawahan.</td></tr>
            <?php else: ?>
            <?php foreach ($layanan_list as $l): ?>
            <tr>
              <td>
                <div style="font-weight:600;"><?= e($l['nama']) ?></div>
                <div style="color:#64748b;font-size:12px;font-family:monospace;"><?= e($l['nip']) ?></div>
              </td>
              <td style="font-weight:600;"><?= e($l['jenis']) ?></td>
              <td>
                <div style="color:#1a1d1f;font-size:13px;"><?= date('d M Y', strtotime($l['tanggal_mulai'])) ?></div>
                <div style="color:#64748b;font-size:12px;">s/d <?= date('d M Y', strtotime($l['tanggal_selesai'])) ?></div>
              </td>
              <td style="max-width:200px;font-size:13px;color:#475569;"><?= e($l['keterangan']) ?></td>
              <td>
                <?php if ($l['status'] === 'pending_atasan'): ?>
                  <span class="badge badge-warning">Menunggu Anda</span>
                <?php elseif ($l['status'] === 'approved_atasan'): ?>
                  <span class="badge badge-active">Disetujui (Menunggu BKPSDM)</span>
                <?php elseif ($l['status'] === 'approved_bkpsdm'): ?>
                  <span class="badge badge-active" style="background:#dcfce7;color:#166534;">Selesai (SK Terbit)</span>
                <?php elseif ($l['status'] === 'rejected'): ?>
                  <span class="badge badge-secondary" style="background:#fef2f2;color:#ef4444;">Ditolak</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($l['status'] === 'pending_atasan'): ?>
                <div style="display:flex;gap:8px;">
                  <form action="/simpekabjmk/layanan_approval.php" method="POST" onsubmit="return confirm('Setujui pengajuan ini?');">
                    <?= csrfInput() ?>
                    <input type="hidden" name="layanan_id" value="<?= $l['id'] ?>">
                    <input type="hidden" name="keputusan" value="approve">
                    <button type="submit" class="btn-primary" style="padding:6px 10px;font-size:12px;background:#10b981;box-shadow:none;">Setuju</button>
                  </form>
                  <form action="/simpekabjmk/layanan_approval.php" method="POST" onsubmit="return confirm('Tolak pengajuan ini?');">
                    <?= csrfInput() ?>
                    <input type="hidden" name="layanan_id" value="<?= $l['id'] ?>">
                    <input type="hidden" name="keputusan" value="reject">
                    <button type="submit" class="btn-danger" style="padding:6px 10px;font-size:12px;box-shadow:none;">Tolak</button>
                  </form>
                </div>
                <?php else: ?>
                <span style="color:#64748b;font-size:12px;font-style:italic;">Diproses</span>
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
