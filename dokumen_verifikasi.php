<?php
// ============================================================
// dokumen_verifikasi.php — Verifikasi Dokumen E-Wallet (Admin BKPSDM)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
requireRole(['admin_bkpsdm', 'super_admin']);

$currentPage = 'dokumen_verifikasi';
$pageTitle   = 'Verifikasi Dokumen Pegawai';
$user        = currentUser();

// ============================================================
// PROSES FORM (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $dokumen_id = (int)($_POST['dokumen_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($dokumen_id > 0 && in_array($action, ['approve_bkn', 'approve_bkpsdm', 'reject'])) {
        $status_baru = '';
        $keterangan = null;
        
        if ($action === 'approve_bkn') {
            $status_baru = 'Telah Diverifikasi BKN';
        } elseif ($action === 'approve_bkpsdm') {
            $status_baru = 'Telah Diverifikasi BKPSDM';
        } elseif ($action === 'reject') {
            $status_baru = 'Ditolak';
            $keterangan = trim($_POST['keterangan_penolakan'] ?? '');
        }
        
        $stmt = $pdo->prepare("UPDATE dokumen_pegawai SET status_verifikasi = ?, keterangan_penolakan = ? WHERE id = ?");
        if ($stmt->execute([$status_baru, $keterangan, $dokumen_id])) {
            logActivity($user['id'], 'VERIFIKASI_DOKUMEN', "Dokumen ID $dokumen_id diubah menjadi $status_baru", 'info');
            setFlash('success', "Status dokumen berhasil diubah menjadi: " . $status_baru);
        } else {
            setFlash('error', "Gagal memperbarui status dokumen.");
        }
        redirect('/simpekabjmk/dokumen_verifikasi.php');
    }
}

// ============================================================
// AMBIL DATA DOKUMEN
// ============================================================
$stmt = $pdo->prepare("
    SELECT d.*, u.nama, p.nip, p.divisi
    FROM dokumen_pegawai d
    JOIN users u ON u.id = d.user_id
    JOIN pegawai p ON p.user_id = u.id
    ORDER BY 
      CASE WHEN d.status_verifikasi = 'Menunggu Verifikasi' THEN 1 ELSE 2 END,
      d.uploaded_at DESC
");
$stmt->execute();
$dokumen_list = $stmt->fetchAll();

generateCsrfToken();
?>
<?php include __DIR__ . '/partials/head.php'; ?>

<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="page-content">
      <?= renderFlash() ?>

      <h1 class="section-title">Verifikasi E-Wallet Dokumen</h1>
      <p class="section-subtitle">Tinjau dan verifikasi keaslian dokumen digital pegawai untuk keperluan administrasi</p>

      <div class="card" style="padding:0;overflow:hidden;margin-top:32px;border:1px solid #eaecf0;background:#ffffff;border-radius:16px;">
        <div style="padding:20px 24px;border-bottom:1px solid #eaecf0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
          <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:32px;height:32px;border-radius:8px;background:#e0e7ff;color:#4f46e5;display:flex;align-items:center;justify-content:center;">
              <span class="material-symbols-outlined" style="font-size:18px;">plagiarism</span>
            </div>
            <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;margin:0;">Daftar Dokumen Masuk (<?= count(array_filter($dokumen_list, fn($d) => $d['status_verifikasi'] === 'Menunggu Verifikasi')) ?> Menunggu)</h2>
          </div>
        </div>

        <div style="padding:24px;display:flex;flex-direction:column;gap:24px;">
          <?php if (empty($dokumen_list)): ?>
            <div style="text-align:center;padding:48px;color:#64748b;font-weight:500;">Belum ada dokumen yang diunggah oleh pegawai.</div>
          <?php else: ?>
            <?php foreach ($dokumen_list as $d): ?>
              <?php 
                $statusColor = '#64748b';
                $statusBg = '#f1f5f9';
                if ($d['status_verifikasi'] === 'Menunggu Verifikasi') {
                    $statusColor = '#d97706';
                    $statusBg = '#fef3c7';
                } elseif (strpos($d['status_verifikasi'], 'Telah Diverifikasi') !== false) {
                    $statusColor = '#166534';
                    $statusBg = '#dcfce7';
                } elseif ($d['status_verifikasi'] === 'Ditolak') {
                    $statusColor = '#ef4444';
                    $statusBg = '#fef2f2';
                }
              ?>
              <div style="border:1px solid #eaecf0;border-radius:12px;padding:20px;background:#ffffff;transition:box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.03)'" onmouseout="this.style.boxShadow='none'">
                
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;flex-wrap:wrap;gap:16px;">
                  <div style="display:flex;align-items:center;gap:16px;">
                    <div style="width:48px;height:48px;border-radius:12px;background:#f8fafc;display:flex;align-items:center;justify-content:center;color:#64748b;border:1px solid #eaecf0;">
                      <span class="material-symbols-outlined" style="font-size:24px;">picture_as_pdf</span>
                    </div>
                    <div>
                      <div style="font-weight:800;color:#1a1d1f;font-size:15px;margin-bottom:4px;"><?= e($d['jenis_dokumen']) ?></div>
                      <div style="color:#64748b;font-size:13px;display:flex;align-items:center;gap:6px;">
                        <span class="material-symbols-outlined" style="font-size:14px;">person</span>
                        <?= e($d['nama']) ?> &bull; NIP: <?= e($d['nip']) ?>
                      </div>
                    </div>
                  </div>
                  <span style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;padding:6px 16px;border-radius:20px;font-size:12px;font-weight:700;border:1px solid <?= $statusColor ?>40;">
                    <?= e($d['status_verifikasi']) ?>
                  </span>
                </div>

                <div style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:16px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                  <div>
                    <div style="color:#64748b;font-size:12px;margin-bottom:4px;font-weight:600;">Waktu Unggah</div>
                    <div style="color:#1a1d1f;font-size:13px;font-weight:500;"><?= formatTanggalId($d['uploaded_at']) ?> - <?= date('H:i', strtotime($d['uploaded_at'])) ?></div>
                  </div>
                  <div>
                    <div style="color:#64748b;font-size:12px;margin-bottom:4px;font-weight:600;">File Terlampir</div>
                    <a href="/simpekabjmk/<?= e($d['file_path']) ?>" target="_blank" style="color:#0ea5e9;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:4px;">
                      <span class="material-symbols-outlined" style="font-size:16px;">visibility</span> Lihat Dokumen
                    </a>
                  </div>
                </div>

                <?php if ($d['status_verifikasi'] === 'Menunggu Verifikasi'): ?>
                <div style="display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;border-top:1px dashed #eaecf0;padding-top:16px;">
                  <!-- Tolak Button & Modal Trigger -->
                  <button type="button" onclick="document.getElementById('modal-reject-<?= $d['id'] ?>').style.display = 'flex'" style="display:flex;align-items:center;gap:6px;padding:8px 20px;border-radius:8px;border:1px solid #fca5a5;background:#fef2f2;color:#ef4444;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">
                    <span class="material-symbols-outlined" style="font-size:16px;">close</span> Tolak
                  </button>

                  <form action="" method="POST" style="margin:0;">
                    <?= csrfInput() ?>
                    <input type="hidden" name="dokumen_id" value="<?= $d['id'] ?>">
                    <input type="hidden" name="action" value="approve_bkpsdm">
                    <button type="submit" onclick="return confirm('Verifikasi dokumen ini sebagai BKPSDM?');" style="display:flex;align-items:center;gap:6px;padding:8px 20px;border-radius:8px;border:none;background:#3b82f6;color:#ffffff;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">
                      <span class="material-symbols-outlined" style="font-size:16px;">verified</span> Verifikasi BKPSDM
                    </button>
                  </form>

                  <form action="" method="POST" style="margin:0;">
                    <?= csrfInput() ?>
                    <input type="hidden" name="dokumen_id" value="<?= $d['id'] ?>">
                    <input type="hidden" name="action" value="approve_bkn">
                    <button type="submit" onclick="return confirm('Verifikasi dokumen ini sebagai BKN?');" style="display:flex;align-items:center;gap:6px;padding:8px 20px;border-radius:8px;border:none;background:#10b981;color:#ffffff;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">
                      <span class="material-symbols-outlined" style="font-size:16px;">verified_user</span> Verifikasi BKN
                    </button>
                  </form>
                </div>

                <!-- Modal Reject -->
                <div id="modal-reject-<?= $d['id'] ?>" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px);">
                  <div style="background:#ffffff;border-radius:16px;width:100%;max-width:400px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);position:relative;overflow:hidden;">
                    <div style="padding:20px;border-bottom:1px solid #eaecf0;display:flex;justify-content:space-between;align-items:center;">
                      <h3 style="font-size:16px;font-weight:700;color:#1a1d1f;margin:0;">Alasan Penolakan Dokumen</h3>
                      <button type="button" onclick="document.getElementById('modal-reject-<?= $d['id'] ?>').style.display = 'none'" style="background:none;border:none;color:#94a3b8;cursor:pointer;">
                        <span class="material-symbols-outlined">close</span>
                      </button>
                    </div>
                    <div style="padding:20px;">
                      <form action="" method="POST">
                        <?= csrfInput() ?>
                        <input type="hidden" name="dokumen_id" value="<?= $d['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <textarea name="keterangan_penolakan" class="input-card" rows="3" placeholder="Tuliskan alasan penolakan agar pegawai bisa memperbaiki dokumennya..." required style="margin-bottom:16px;width:100%;"></textarea>
                        <div style="display:flex;justify-content:flex-end;gap:12px;">
                          <button type="button" class="btn-ghost" onclick="document.getElementById('modal-reject-<?= $d['id'] ?>').style.display = 'none'" style="padding:8px 16px;">Batal</button>
                          <button type="submit" class="btn-primary" style="padding:8px 16px;background:#ef4444;">Tolak Dokumen</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <?php elseif ($d['status_verifikasi'] === 'Ditolak'): ?>
                <div style="border-top:1px dashed #eaecf0;padding-top:16px;">
                  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px;font-size:13px;color:#b91c1c;">
                    <strong>Alasan Ditolak:</strong> <?= e($d['keterangan_penolakan'] ?? 'Tidak ada alasan.') ?>
                  </div>
                </div>
                <?php endif; ?>

              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
// Simple script to toggle classes if needed, though we use onclick inline above for simplicity.
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
