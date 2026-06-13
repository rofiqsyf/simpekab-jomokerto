<?php
// ============================================================
// absensi.php — Input & Riwayat Absensi Pribadi
// Akses: Semua role (admin, manager, karyawan)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();

$currentPage = 'absensi';
$pageTitle   = 'Absensi Saya';
$user        = currentUser();
$today       = date('Y-m-d');
$nowTime     = date('H:i:s');
$errors      = [];

// ============================================================
// PROSES FORM ABSENSI (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();

    $action = $_POST['action'] ?? '';
    $is_luar_radius = $_POST['is_luar_radius'] ?? '0';
    $keterangan_post = trim($_POST['keterangan'] ?? '');
    
    // Fungsi internal untuk handle upload foto secara AMAN (Mencegah Unrestricted File Upload)
    $handleUpload = function() use (&$errors) {
        if (!isset($_FILES['bukti_foto']) || $_FILES['bukti_foto']['error'] !== UPLOAD_ERR_OK) {
            return null; // Tidak wajib foto
        }
        
        $file = $_FILES['bukti_foto'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // 1. Validasi ekstensi
        $allowedExts = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowedExts)) {
            $errors[] = 'Hanya file gambar (JPG/PNG) yang diperbolehkan.';
            return null;
        }
        
        // 2. Validasi MIME Type yang sebenarnya
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, ['image/jpeg', 'image/png'])) {
            $errors[] = 'File yang diunggah bukan gambar valid.';
            return null;
        }
        
        // 3. Validasi Ukuran (Max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Ukuran maksimal file foto adalah 5MB.';
            return null;
        }

        $uploadDir = __DIR__ . '/uploads/absensi/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // 4. Hashing nama file untuk mencegah eksekusi
        $filename = time() . '_' . md5(uniqid('', true)) . '.' . $ext;
        $targetFile = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return 'uploads/absensi/' . $filename;
        }
        
        $errors[] = 'Gagal menyimpan file.';
        return null;
    };

    if ($action === 'checkin') {
        // Cek sudah check-in hari ini?
        $stmt = $pdo->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ?");
        $stmt->execute([$user['id'], $today]);

        if ($stmt->fetch()) {
            setFlash('warning', 'Anda sudah melakukan check-in hari ini.');
        } else {
            // Tentukan status default
            $jamMasuk = '08:00:00';
            $status   = ($nowTime > $jamMasuk) ? 'terlambat' : 'hadir';
            $keterangan = $status === 'terlambat' ? 'Terlambat dari jam 08:00' : 'Tepat waktu';
            $bukti_foto = null;

            if ($is_luar_radius === '1') {
                $status = 'menunggu_konfirmasi';
                $keterangan = $keterangan_post ?: 'Luar radius tanpa keterangan';
                $bukti_foto = $handleUpload();
            }

            if (!empty($errors)) {
                setFlash('error', implode('<br>', $errors));
                $redirectUrl = $_POST['redirect_to'] ?? '/simpekabjmk/absensi.php';
                redirect($redirectUrl);
            }

            $stmt = $pdo->prepare("
                INSERT INTO absensi (user_id, tanggal, check_in, status, keterangan, bukti_foto)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['id'],
                $today,
                date('H:i:s'),
                $status,
                $keterangan,
                $bukti_foto
            ]);

            logActivity($user['id'], 'ABSENSI_CHECKIN', "Check-in pukul " . date('H:i') . " — Status: {$status}", 'info');
            setFlash('success', "Check-in berhasil pukul " . date('H:i') . " (Status: " . strtoupper($status) . ")");
        }
        $redirectUrl = $_POST['redirect_to'] ?? '/simpekabjmk/absensi.php';
        redirect($redirectUrl);
    }

    if ($action === 'checkout') {
        // Cek sudah check-in dan belum check-out
        $stmt = $pdo->prepare("
            SELECT id, check_in FROM absensi
            WHERE user_id = ? AND tanggal = ? AND check_out IS NULL
        ");
        $stmt->execute([$user['id'], $today]);
        $record = $stmt->fetch();

        if (!$record) {
            setFlash('warning', 'Anda belum check-in atau sudah check-out hari ini.');
        } else {
            // Hitung durasi kerja dalam menit
            $checkIn   = strtotime($record['check_in']);
            $checkOut  = time();
            $durasiMnt = (int) round(($checkOut - $checkIn) / 60);

            $updateFields = "check_out = ?, durasi_mnt = ?";
            $updateParams = [date('H:i:s'), $durasiMnt];

            if ($is_luar_radius === '1') {
                $status = 'menunggu_konfirmasi';
                $keterangan = $keterangan_post ?: 'Check-out luar radius';
                $bukti_foto = $handleUpload();
                $updateFields .= ", status = ?, keterangan = ?, bukti_foto = ?";
                array_push($updateParams, $status, $keterangan, $bukti_foto);
            }

            if (!empty($errors)) {
                setFlash('error', implode('<br>', $errors));
                $redirectUrl = $_POST['redirect_to'] ?? '/simpekabjmk/absensi.php';
                redirect($redirectUrl);
            }

            $updateParams[] = $record['id'];

            $stmt = $pdo->prepare("UPDATE absensi SET {$updateFields} WHERE id = ?");
            $stmt->execute($updateParams);

            logActivity($user['id'], 'ABSENSI_CHECKOUT', "Check-out pukul " . date('H:i') . " — Durasi: " . formatDurasi($durasiMnt), 'info');
            setFlash('success', "Check-out berhasil pukul " . date('H:i') . " — Durasi kerja: " . formatDurasi($durasiMnt));
        }
        $redirectUrl = $_POST['redirect_to'] ?? '/simpekabjmk/absensi.php';
        redirect($redirectUrl);
    }
}

// ============================================================
// DATA: Absensi hari ini
// ============================================================
$stmtToday = $pdo->prepare("SELECT * FROM absensi WHERE user_id = ? AND tanggal = ?");
$stmtToday->execute([$user['id'], $today]);
$absensiToday = $stmtToday->fetch();

$sudahCheckIn  = $absensiToday && $absensiToday['check_in'];
$sudahCheckOut = $absensiToday && $absensiToday['check_out'];

// ============================================================
// DATA: Statistik bulan ini
// ============================================================
$stmtStats = $pdo->prepare("
    SELECT
        COUNT(*) as total_hari,
        COALESCE(SUM(status = 'hadir'), 0)     as hadir,
        COALESCE(SUM(status = 'terlambat'), 0) as terlambat,
        COALESCE(SUM(status = 'alpha'), 0)     as alpha,
        COALESCE(SUM(status = 'izin'), 0)      as izin,
        COALESCE(SUM(status = 'sakit'), 0)     as sakit
    FROM absensi
    WHERE user_id = ?
      AND MONTH(tanggal) = MONTH(CURDATE())
      AND YEAR(tanggal)  = YEAR(CURDATE())
");
$stmtStats->execute([$user['id']]);
$stats = $stmtStats->fetch();

// Hitung persentase kehadiran
$totalKerja     = $stats['hadir'] + $stats['terlambat'] + $stats['alpha'];
$persenKehadiran = $totalKerja > 0 ? round(($stats['hadir'] + $stats['terlambat']) / max($totalKerja, 1) * 100) : 0;

// ============================================================
// DATA: Riwayat absensi (bulan ini, diurutkan terbaru)
// ============================================================
$stmtRiwayat = $pdo->prepare("
    SELECT tanggal, check_in, check_out, status, keterangan, durasi_mnt
    FROM absensi
    WHERE user_id = ?
      AND MONTH(tanggal) = MONTH(CURDATE())
      AND YEAR(tanggal)  = YEAR(CURDATE())
    ORDER BY tanggal DESC
");
$stmtRiwayat->execute([$user['id']]);
$riwayat = $stmtRiwayat->fetchAll();

$csrfToken = generateCsrfToken();
?>
<?php include __DIR__ . '/partials/head.php'; ?>

<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="page-content">
      <?= renderFlash() ?>

      <h1 class="section-title">Absensi Harian</h1>
      <p class="section-subtitle">Input kehadiran dan riwayat absensi pribadi — <?= date('l, d F Y') ?></p>

      <div style="display:grid;gap:24px;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));margin-bottom:32px;">

        <!-- CHECK-IN CARD WIDGET -->
        <?php include __DIR__ . '/partials/widget_absensi.php'; ?>

        <!-- STATISTIK BULAN INI -->
        <div class="card">
          <h2 style="font-size:18px;font-weight:700;color:#1a1d1f;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
            <span class="material-symbols-outlined" style="color:#3b82f6;background:#eff6ff;padding:8px;border-radius:12px;">query_stats</span>
            Statistik <?= date('F Y') ?>
          </h2>

          <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ([
              ['Hadir Tepat Waktu', $stats['hadir'], '#10b981'],
              ['Terlambat',         $stats['terlambat'], '#f59e0b'],
              ['Alpha',             $stats['alpha'], '#ef4444'],
              ['Izin',              $stats['izin'], '#3b82f6'],
              ['Sakit',             $stats['sakit'], '#8b5cf6'],
            ] as [$label, $val, $color]): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:#f8fafc;border-radius:12px;border:1px solid #eaecf0;">
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>;flex-shrink:0;"></div>
                <span style="color:#475569;font-weight:600;font-size:14px;"><?= $label ?></span>
              </div>
              <span style="font-weight:800;color:<?= $color ?>;font-size:16px;"><?= e($val) ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Persentase kehadiran -->
          <div style="margin-top:24px;padding-top:20px;border-top:1px solid #eaecf0;">
            <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:13px;font-weight:600;">
              <span style="color:#64748b;">Persentase Kehadiran</span>
              <span style="color:<?= $persenKehadiran >= 80 ? '#10b981' : ($persenKehadiran >= 60 ? '#f59e0b' : '#ef4444') ?>;font-size:16px;font-weight:800;"><?= $persenKehadiran ?>%</span>
            </div>
            <div class="progress-bar" style="height:8px;">
              <div class="progress-fill" style="width:<?= $persenKehadiran ?>%;background:<?= $persenKehadiran >= 80 ? '#10b981' : ($persenKehadiran >= 60 ? '#f59e0b' : '#ef4444') ?>;"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIWAYAT ABSENSI TABLE -->
      <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
        <div style="padding:20px 24px;border-bottom:1px solid #eaecf0;display:flex;justify-content:space-between;align-items:center;background:#ffffff;flex-wrap:wrap;gap:16px;">
          <h2 style="font-size:18px;font-weight:700;color:#1a1d1f;">Riwayat Absensi — <?= date('F Y') ?></h2>
          <div style="display:flex;gap:12px;align-items:center;">
            <span class="badge badge-secondary" style="font-size:13px;"><?= count($riwayat) ?> entri</span>
            <a href="/simpekabjmk/export_absensi_saya.php" class="btn-primary" style="font-size:13px;padding:8px 16px;text-decoration:none;">
              <span class="material-symbols-outlined" style="font-size:16px;">download</span> Unduh CSV
            </a>
            <button class="btn-ghost" style="border:1px solid #eaecf0;background:#ffffff;font-size:13px;padding:8px 16px;" onclick="window.location.href='/simpekabjmk/layanan_pengajuan.php?jenis=koreksi'">
              <span class="material-symbols-outlined" style="font-size:16px;">edit_calendar</span> Ajukan Koreksi
            </button>
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Durasi</th>
                <th>Status</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($riwayat)): ?>
              <tr><td colspan="6" style="text-align:center;color:#64748b;padding:48px;font-weight:500;">Belum ada data absensi bulan ini.</td></tr>
              <?php else: ?>
              <?php foreach ($riwayat as $a): ?>
              <tr>
                <td style="color:#1a1d1f;font-weight:600;font-size:14px;"><?= e(formatTanggalId($a['tanggal'])) ?></td>
                <td style="color:#10b981;font-weight:600;font-size:14px;font-family:'JetBrains Mono',monospace;"><?= e($a['check_in'] ?? '—') ?></td>
                <td style="color:#3b82f6;font-weight:600;font-size:14px;font-family:'JetBrains Mono',monospace;"><?= e($a['check_out'] ?? '—') ?></td>
                <td style="color:#64748b;font-weight:500;font-size:14px;"><?= formatDurasi($a['durasi_mnt']) ?></td>
                <td><?= absensiBadge($a['status']) ?></td>
                <td style="color:#64748b;font-size:13px;font-weight:500;"><?= e($a['keterangan'] ?? '-') ?></td>
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
