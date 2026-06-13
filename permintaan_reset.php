<?php
// ============================================================
// permintaan_reset.php — Penanganan Lupa Password (Admin Only)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireRole(['super_admin', 'admin_bkpsdm']); // Admin Only!

$currentPage = 'permintaan_reset';
$pageTitle   = 'Permintaan Reset';
$user        = currentUser();

// Tangani aksi Tandai Selesai / Hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $action = $_POST['action'] ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);

    if ($action === 'resolve' && $requestId > 0) {
        $stmt = $pdo->prepare("UPDATE password_reset_requests SET status = 'resolved' WHERE id = ?");
        $stmt->execute([$requestId]);
        setFlash('success', 'Permintaan berhasil ditandai sebagai selesai.');
    } elseif ($action === 'delete' && $requestId > 0) {
        $stmt = $pdo->prepare("DELETE FROM password_reset_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        setFlash('success', 'Permintaan berhasil dihapus.');
    }
    redirect('/simpekabjmk/permintaan_reset.php');
}

// Ambil daftar permintaan (join dengan tabel users untuk mengecek apakah email terdaftar)
$stmt = $pdo->query("
    SELECT r.id, r.email, r.jenis_layanan, r.status, r.created_at, u.id AS user_id, u.nama, u.role
    FROM password_reset_requests r
    LEFT JOIN users u ON u.email = r.email
    ORDER BY r.status ASC, r.created_at DESC
");
$requests = $stmt->fetchAll();

$csrfToken = generateCsrfToken();
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
          <h1 class="section-title">Permintaan Bantuan IT</h1>
          <p class="section-subtitle">Tinjau dan tangani permintaan layanan/bantuan dari pegawai</p>
        </div>
      </div>

      <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Waktu Request</th>
                <th>Email Pegawai</th>
                <th>Status Pegawai</th>
                <th>Jenis Layanan</th>
                <th>Status Request</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($requests)): ?>
              <tr>
                <td colspan="6" style="text-align:center;color:#64748b;padding:48px;font-weight:500;">Tidak ada permintaan masuk.</td>
              </tr>
              <?php else: ?>
              <?php foreach ($requests as $req): 
                $isPending = $req['status'] === 'pending';
              ?>
              <tr>
                <td style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;white-space:nowrap;">
                  <?= e(date('d M Y H:i', strtotime($req['created_at']))) ?>
                </td>
                <td>
                  <div style="color:#1a1d1f;font-weight:700;font-size:14px;margin-bottom:4px;"><?= e($req['email']) ?></div>
                  <?php if ($req['nama']): ?>
                    <div style="color:#64748b;font-size:13px;font-weight:500;"><?= e($req['nama']) ?> <?= roleBadge($req['role']) ?></div>
                  <?php else: ?>
                    <span class="badge badge-secondary" style="font-size:11px;">Tidak terdaftar</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($req['user_id']): ?>
                    <span class="badge badge-active" style="font-size:11px;">Akun Valid</span>
                  <?php else: ?>
                    <span class="badge badge-danger" style="font-size:11px;">Tidak Ditemukan</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge badge-secondary" style="font-size:11px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;"><?= e($req['jenis_layanan'] ?? 'Lupa Sandi') ?></span>
                </td>
                <td>
                  <?php if ($isPending): ?>
                    <span class="badge badge-warning" style="font-size:11px;">Menunggu Proses</span>
                  <?php else: ?>
                    <span class="badge badge-active" style="font-size:11px;background:#f0fdf4;color:#10b981;">Selesai</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;gap:8px;">
                    <?php if ($isPending && $req['user_id']): ?>
                    <a href="/simpekabjmk/reset_password.php?id=<?= $req['user_id'] ?>" class="btn-primary" style="padding:6px 12px;font-size:12px;text-decoration:none;">
                      Proses
                    </a>
                    <?php endif; ?>
                    
                    <form method="POST" style="margin:0;">
                      <?= csrfInput() ?>
                      <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                      <?php if ($isPending): ?>
                      <button type="submit" name="action" value="resolve" class="btn-ghost" style="padding:6px 12px;font-size:12px;color:#10b981;background:#f0fdf4;">Selesai</button>
                      <?php else: ?>
                      <button type="submit" name="action" value="delete" class="btn-ghost" style="padding:6px 12px;font-size:12px;color:#ef4444;background:#fef2f2;" onclick="return confirm('Hapus riwayat ini?')">Hapus</button>
                      <?php endif; ?>
                    </form>
                  </div>
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

<?php include __DIR__ . '/partials/footer.php'; ?>
