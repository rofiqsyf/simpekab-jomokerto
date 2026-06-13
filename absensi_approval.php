<?php
// ============================================================
// absensi_approval.php — Persetujuan Absensi Luar Radius
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
requireRole(['super_admin', 'admin_bkpsdm']);

$currentPage = 'absensi_approval';
$pageTitle   = 'Persetujuan Absensi';
$user        = currentUser();

// Proses aksi Terima / Tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $id = (int)$_POST['absensi_id'];
    $action = $_POST['action'] ?? '';

    // Pastikan absensi ini berstatus menunggu konfirmasi
    $stmtCek = $pdo->prepare("SELECT id FROM absensi WHERE id = ? AND status = 'menunggu_konfirmasi'");
    $stmtCek->execute([$id]);
    if ($stmtCek->fetch()) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE absensi SET status = 'hadir' WHERE id = ?")->execute([$id]);
            logActivity($user['id'], 'ABSENSI_APPROVE', "Menerima absensi luar radius (ID: $id)");
            setFlash('success', 'Absensi berhasil disetujui sebagai Hadir.');
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE absensi SET status = 'alpha' WHERE id = ?")->execute([$id]);
            logActivity($user['id'], 'ABSENSI_REJECT', "Menolak absensi luar radius (ID: $id)");
            setFlash('success', 'Absensi ditolak dan diset sebagai Alpha.');
        }
    }
    redirect('/simpekabjmk/absensi_approval.php');
}

// Ambil data absensi menunggu konfirmasi
$stmtPending = $pdo->prepare("
    SELECT a.*, u.nama, u.email, p.nip, p.posisi
    FROM absensi a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN pegawai p ON u.id = p.user_id
    WHERE a.status = 'menunggu_konfirmasi'
    ORDER BY a.created_at ASC
");
$stmtPending->execute();
$pendingList = $stmtPending->fetchAll();

generateCsrfToken();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      <?= renderFlash() ?>

      <h1 class="section-title">Persetujuan Absensi Luar Radius</h1>
      <p class="section-subtitle">Daftar presensi pegawai yang berada di luar jarak 100m dari Kantor Bupati</p>

      <div class="card" style="padding:0;overflow:hidden;margin-top:32px;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
        <div style="padding:20px;border-bottom:1px solid #eaecf0;background:#f8fafc;">
          <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="color:#f59e0b;">pending_actions</span> Antrean Konfirmasi
          </h2>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Pegawai</th>
              <th>Waktu</th>
              <th>Keterangan</th>
              <th>Bukti Foto</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pendingList)): ?>
            <tr><td colspan="5" style="text-align:center;padding:32px;color:#64748b;font-weight:500;">Tidak ada antrean persetujuan absensi.</td></tr>
            <?php else: ?>
            <?php foreach ($pendingList as $absen): ?>
            <tr>
              <td>
                <div style="font-weight:600;color:#1a1d1f;"><?= e($absen['nama']) ?></div>
                <div style="color:#64748b;font-size:12px;font-family:'JetBrains Mono',monospace;"><?= e($absen['nip']) ?></div>
              </td>
              <td>
                <div style="font-weight:600;color:#1a1d1f;"><?= date('d M Y', strtotime($absen['tanggal'])) ?></div>
                <div style="color:#64748b;font-size:12px;">In: <?= e($absen['check_in'] ?? '-') ?> | Out: <?= e($absen['check_out'] ?? '-') ?></div>
              </td>
              <td style="max-width:200px;font-size:13px;color:#334155;">
                <?= nl2br(e($absen['keterangan'])) ?>
              </td>
              <td>
                <?php if ($absen['bukti_foto']): ?>
                <button type="button" class="btn-ghost" onclick="openPhotoModal('/simpekabjmk/<?= e($absen['bukti_foto']) ?>')" style="padding:4px 8px;font-size:12px;color:#0ea5e9;border:1px solid #bae6fd;background:transparent;cursor:pointer;">
                  <span class="material-symbols-outlined" style="font-size:14px;">image</span> Lihat Foto
                </button>
                <?php else: ?>
                <span style="color:#94a3b8;font-size:12px;font-style:italic;">Tidak ada bukti</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex;gap:8px;">
                  <form method="POST" style="margin:0;">
                    <?= csrfInput() ?>
                    <input type="hidden" name="absensi_id" value="<?= $absen['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn-primary" style="padding:6px 12px;font-size:12px;background:#10b981;box-shadow:none;" onclick="return confirm('Terima absensi ini sebagai Hadir?')">Terima</button>
                  </form>
                  <form method="POST" style="margin:0;">
                    <?= csrfInput() ?>
                    <input type="hidden" name="absensi_id" value="<?= $absen['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn-danger" style="padding:6px 12px;font-size:12px;box-shadow:none;" onclick="return confirm('Tolak absensi ini (Alpha)?')">Tolak</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Photo Modal -->
      <div id="photoModal" class="hidden" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.8);z-index:999;align-items:center;justify-content:center;opacity:0;transition:opacity 0.2s;">
        <div style="background:#ffffff;padding:16px;border-radius:16px;max-width:90%;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 40px rgba(0,0,0,0.2);">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div style="font-weight:700;color:#1a1d1f;">Bukti Foto Absensi</div>
            <button type="button" onclick="closePhotoModal()" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#64748b;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
              <span class="material-symbols-outlined" style="font-size:18px;">close</span>
            </button>
          </div>
          <div style="flex:1;overflow:hidden;border-radius:8px;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-width:300px;min-height:300px;">
            <img id="photoModalImg" src="" alt="Bukti Foto" style="max-width:100%;max-height:70vh;object-fit:contain;">
          </div>
          <div style="margin-top:16px;text-align:right;">
            <button type="button" class="btn-primary" onclick="closePhotoModal()">Tutup (Kembali)</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function openPhotoModal(src) {
    const modal = document.getElementById('photoModal');
    const img = document.getElementById('photoModalImg');
    if(modal && img) {
        img.src = src;
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
        // Force reflow
        void modal.offsetWidth;
        modal.style.opacity = '1';
    }
}
function closePhotoModal() {
    const modal = document.getElementById('photoModal');
    if(modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.getElementById('photoModalImg').src = '';
        }, 200);
    }
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
