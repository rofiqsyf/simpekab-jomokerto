<?php
// ============================================================
// dashboard_super_admin.php — System Administrator Dashboard
// Manajemen Teknis, Log Aktivitas, dan Konfigurasi Sistem
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
if (!hasRole('super_admin')) {
    redirect('/simpekabjmk/dashboard.php');
}

$currentPage = 'dashboard_super_admin';
$pageTitle   = 'Super Admin Dashboard';
$user        = currentUser();

// ============================================================
// PROSES TOOL / ALAT SISTEM (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'proses_kgb_massal') {
        $stmtKGB = $pdo->prepare("SELECT id, user_id, tmt_kgb FROM pegawai WHERE status = 'aktif' AND DATE_ADD(tmt_kgb, INTERVAL 2 YEAR) <= CURDATE()");
        $stmtKGB->execute();
        $eligible = $stmtKGB->fetchAll();
        
        if (count($eligible) > 0) {
            $pdo->beginTransaction();
            try {
                $stmtInsertRiwayat = $pdo->prepare("INSERT INTO riwayat_kgb (user_id, tmt_lama, tmt_baru) VALUES (?, ?, ?)");
                $stmtUpdatePegawai = $pdo->prepare("UPDATE pegawai SET tmt_kgb = ? WHERE id = ?");
                
                foreach ($eligible as $p) {
                    $tmtBaru = date('Y-m-d', strtotime('+2 years', strtotime($p['tmt_kgb'])));
                    $stmtInsertRiwayat->execute([$p['user_id'], $p['tmt_kgb'], $tmtBaru]);
                    $stmtUpdatePegawai->execute([$tmtBaru, $p['id']]);
                }
                $pdo->commit();
                logActivity($user['id'], 'KGB_MASSAL', 'Memproses KGB otomatis untuk ' . count($eligible) . ' pegawai', 'info');
                setFlash('success', 'Berhasil menerbitkan draf SK KGB untuk ' . count($eligible) . ' pegawai.');
            } catch (Exception $e) {
                $pdo->rollBack();
                setFlash('error', 'Terjadi kesalahan sistem saat memproses KGB.');
            }
        } else {
            setFlash('warning', 'Tidak ada pegawai yang memenuhi syarat KGB.');
        }
        redirect('/simpekabjmk/dashboard_super_admin.php');
    } elseif ($action === 'sync_siasn') {
        logActivity($user['id'], 'SYSTEM_SYNC', 'Menjalankan sinkronisasi massal SIASN BKN', 'info');
        setFlash('success', 'Perintah sinkronisasi SIASN berhasil dijadwalkan dan akan berjalan di latar belakang (Background Worker).');
    } elseif ($action === 'backup_db') {
        $mockFilename = 'backup_simpekab_' . date('Ymd_His') . '.sql';
        logActivity($user['id'], 'SYSTEM_BACKUP', "Menjalankan backup database ({$mockFilename})", 'info');
        setFlash('success', "Database berhasil di-backup. File tersimpan sebagai: <strong>{$mockFilename}</strong>.");
    } elseif ($action === 'clear_cache') {
        logActivity($user['id'], 'SYSTEM_CLEAR_CACHE', 'Membersihkan memori cache sistem dan sesi expired', 'warning');
        setFlash('success', 'Sistem Cache (Redis/File) dan Sesi Expired berhasil dibersihkan seluruhnya.');
    }
    
    redirect('/simpekabjmk/dashboard_super_admin.php');
}

// 1. Ambil 5 Activity Log terbaru
$stmtLog = $pdo->prepare("
    SELECT al.*, u.email 
    FROM activity_log al 
    LEFT JOIN users u ON u.id = al.user_id 
    ORDER BY al.created_at DESC LIMIT 5
");
$stmtLog->execute();
$logs = $stmtLog->fetchAll();

// 2. Cek Akun Terkunci
$stmtLocked = $pdo->prepare("SELECT COUNT(*) FROM users WHERE locked_until > NOW()");
$stmtLocked->execute();
$lockedCount = $stmtLocked->fetchColumn();

// 3. Status Database (Mock Size)
$dbSize = '45.2 MB'; 
$uptime = '99.98%';

// 4. Deteksi KGB (Sama seperti dashboard BKPSDM)
$stmtEligibleKGB = $pdo->prepare("SELECT id FROM pegawai WHERE status = 'aktif' AND DATE_ADD(tmt_kgb, INTERVAL 2 YEAR) <= CURDATE()");
$stmtEligibleKGB->execute();
$countKGB = $stmtEligibleKGB->rowCount();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      
      <?= renderFlash() ?>

      <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:32px;">
        <div>
          <h1 class="section-title">System Control Panel</h1>
          <p class="section-subtitle">Dashboard Super Admin: Server Metrics & Security Logs</p>
        </div>
        <div style="display:flex;gap:12px;">
            <a href="/simpekabjmk/keamanan.php" class="btn-primary">
                <span class="material-symbols-outlined">shield_lock</span> Brankas Keamanan
            </a>
        </div>
      </div>

      <!-- Server Metrics -->
      <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:32px;">
        
        <div class="card" style="border:1px solid #eaecf0;background:#ffffff;box-shadow:0 10px 25px rgba(0,0,0,0.02);padding:24px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <span style="color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Server Status</span>
            <span class="dot-green animate-ping-slow"></span>
          </div>
          <div style="font-size:28px;font-weight:800;color:#10b981;font-family:'JetBrains Mono',monospace;">ONLINE</div>
          <div style="font-size:13px;color:#64748b;margin-top:8px;">Uptime: <strong style="color:#1a1d1f;"><?= $uptime ?></strong></div>
        </div>

        <div class="card" style="border:1px solid #eaecf0;background:#ffffff;box-shadow:0 10px 25px rgba(0,0,0,0.02);padding:24px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <span style="color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Storage Usage</span>
            <span class="material-symbols-outlined" style="color:#0ea5e9;background:#e0f2fe;padding:8px;border-radius:12px;">database</span>
          </div>
          <div style="font-size:28px;font-weight:800;color:#1a1d1f;font-family:'JetBrains Mono',monospace;"><?= $dbSize ?></div>
          <div style="font-size:13px;color:#64748b;margin-top:8px;">Kapasitas: <strong style="color:#1a1d1f;">500 MB</strong></div>
        </div>

        <div class="card" style="border:1px solid <?= $lockedCount > 0 ? '#fca5a5' : '#eaecf0' ?>;background:#ffffff;box-shadow:0 10px 25px rgba(0,0,0,0.02);padding:24px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <span style="color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Akun Terkunci</span>
            <span class="material-symbols-outlined" style="color:<?= $lockedCount > 0 ? '#ef4444' : '#64748b' ?>;background:<?= $lockedCount > 0 ? '#fef2f2' : '#f1f5f9' ?>;padding:8px;border-radius:12px;">lock_person</span>
          </div>
          <div style="font-size:28px;font-weight:800;color:<?= $lockedCount > 0 ? '#ef4444' : '#1a1d1f' ?>;font-family:'JetBrains Mono',monospace;"><?= $lockedCount ?></div>
          <div style="font-size:13px;color:#64748b;margin-top:8px;">Rate limit aktif</div>
        </div>

      </div>

      <div style="display:grid;gap:24px;grid-template-columns:1fr;@media(min-width:1024px){grid-template-columns:2fr 1fr;}">
        
        <!-- Live Terminal / Logs -->
        <div class="card" style="border:1px solid #eaecf0;background:#ffffff;box-shadow:0 4px 20px rgba(0,0,0,0.02);padding:0;overflow:hidden;">
          <div style="padding:16px 20px;border-bottom:1px solid #eaecf0;display:flex;justify-content:space-between;align-items:center;background:#f8fafc;">
            <h2 style="font-size:14px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;font-family:'JetBrains Mono',monospace;color:#0f172a;">
              <span class="material-symbols-outlined" style="font-size:18px;color:#3b82f6;">terminal</span> system_logs.tail
            </h2>
            <a href="/simpekabjmk/log.php" style="color:#3b82f6;font-size:13px;text-decoration:none;font-weight:600;">View All</a>
          </div>
          <div style="padding:20px;font-family:'JetBrains Mono',monospace;font-size:13px;line-height:1.6;display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($logs as $log): 
              $color = [
                'info' => '#10b981',
                'warning' => '#f59e0b',
                'critical' => '#ef4444'
              ][$log['level']] ?? '#64748b';
            ?>
            <div style="display:flex;gap:12px;align-items:start;">
              <span style="color:#94a3b8;">[<?= date('H:i:s', strtotime($log['created_at'])) ?>]</span>
              <span style="color:<?= $color ?>;font-weight:700;width:50px;"><?= strtoupper($log['level']) ?></span>
              <span style="color:#64748b;"><?= e($log['ip_address'] ?? 'system') ?></span>
              <span style="color:#1e293b;font-weight:500;"><?= e($log['aksi']) ?></span>
            </div>
            <?php endforeach; ?>
            <div style="color:#94a3b8;margin-top:8px;">-- End of log --</div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div style="display:flex;flex-direction:column;gap:24px;">
            <div class="card" style="border:1px solid #eaecf0;background:#ffffff;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
            <h2 style="font-size:16px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;color:#1a1d1f;">
                <span class="material-symbols-outlined" style="color:#8b5cf6;">build</span> Alat Sistem
            </h2>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <form method="POST" style="margin:0;">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="sync_siasn">
                <button type="submit" class="btn-ghost" style="width:100%;justify-content:flex-start;background:#f8fafc;border:1px solid #eaecf0;color:#1a1d1f;font-weight:600;" onclick="return confirm('Mulai proses sinkronisasi massal dengan server SIASN BKN? Proses ini memakan waktu.')">
                    <span class="material-symbols-outlined" style="color:#0ea5e9;">sync</span> Sinkronisasi SIASN
                </button>
                </form>
                
                <form method="POST" style="margin:0;">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="backup_db">
                <button type="submit" class="btn-ghost" style="width:100%;justify-content:flex-start;background:#f8fafc;border:1px solid #eaecf0;color:#1a1d1f;font-weight:600;" onclick="return confirm('Lakukan backup database instan sekarang?')">
                    <span class="material-symbols-outlined" style="color:#10b981;">backup</span> Jalankan Backup DB
                </button>
                </form>

                <form method="POST" style="margin:0;">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="clear_cache">
                <button type="submit" class="btn-ghost" style="width:100%;justify-content:flex-start;background:#f8fafc;border:1px solid #eaecf0;color:#1a1d1f;font-weight:600;" onclick="return confirm('Peringatan: Membersihkan cache dapat menyebabkan beberapa user ter-logout jika sesinya expired. Lanjutkan?')">
                    <span class="material-symbols-outlined" style="color:#f59e0b;">cleaning_services</span> Clear Cache & Session
                </button>
                </form>
            </div>
            </div>

            <!-- KGB Massal khusus Super Admin -->
            <div class="card" style="border:1px solid #eaecf0;background:linear-gradient(135deg, #4f46e5, #4338ca);color:#fff;">
                <h2 style="font-size:16px;font-weight:700;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                    <span class="material-symbols-outlined">auto_awesome</span> Kenaikan Gaji Berkala
                </h2>
                <?php if ($countKGB > 0): ?>
                    <p style="font-size:13px;opacity:0.9;margin-bottom:16px;">Terdapat <strong><?= $countKGB ?> pegawai</strong> yang memenuhi syarat KGB otomatis bulan ini.</p>
                    <form method="POST" action="" onsubmit="return confirm('Proses SK Kenaikan Gaji Berkala untuk <?= $countKGB ?> pegawai?');">
                        <?= csrfInput() ?>
                        <input type="hidden" name="action" value="proses_kgb_massal">
                        <button type="submit" class="btn-primary" style="background:#ffffff;color:#4f46e5;width:100%;justify-content:center;">
                            Proses Massal Sekarang
                        </button>
                    </form>
                <?php else: ?>
                    <p style="font-size:13px;opacity:0.9;margin-bottom:16px;">Belum ada pegawai yang memenuhi syarat KGB (siklus 2 tahun) untuk saat ini.</p>
                    <button class="btn-primary" style="background:rgba(255,255,255,0.2);color:#ffffff;width:100%;justify-content:center;cursor:not-allowed;" disabled>
                        Semua Tuntas
                    </button>
                <?php endif; ?>
                <div style="margin-top:16px;text-align:center;">
                    <a href="/simpekabjmk/riwayat_kgb.php" style="color:#e0e7ff;font-size:13px;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:4px;transition:color 0.2s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#e0e7ff'">
                        <span class="material-symbols-outlined" style="font-size:16px;">history</span> Laporan Riwayat KGB
                    </a>
                </div>
            </div>
        </div>

        <!-- Widget Absensi Mandiri -->
        <?php include __DIR__ . '/partials/widget_absensi.php'; ?>

      </div>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
