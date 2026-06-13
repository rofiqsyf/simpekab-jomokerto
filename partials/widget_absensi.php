<?php
// partials/widget_absensi.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$w_user  = currentUser();
$w_today = date('Y-m-d');

$stmt_w = $pdo->prepare("SELECT * FROM absensi WHERE user_id = ? AND tanggal = ?");
$stmt_w->execute([$w_user['id'], $w_today]);
$w_absensiToday = $stmt_w->fetch();

$w_sudahCheckIn  = $w_absensiToday && $w_absensiToday['check_in'];
$w_sudahCheckOut = $w_absensiToday && $w_absensiToday['check_out'];
$w_csrfToken     = generateCsrfToken();
$w_redirect      = $_SERVER['REQUEST_URI'] ?? '/simpekabjmk/dashboard.php';
?>

<!-- CHECK-IN CARD WIDGET -->
<div class="card" style="position:relative;overflow:hidden;border:1px solid #eaecf0;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.03);margin-bottom:24px;">
  <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;border-radius:50%;background:radial-gradient(circle,rgba(14,165,233,0.1),transparent 70%);"></div>
  <h2 style="font-size:16px;font-weight:700;color:#1a1d1f;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <span class="material-symbols-outlined" style="color:#0ea5e9;background:#e0f2fe;padding:8px;border-radius:12px;font-size:20px;">location_on</span>
    Sistem Presensi GPS
  </h2>

  <!-- Real-Time GPS Map -->
  <div style="border:1px solid #e2e8f0;border-radius:12px;height:140px;margin-bottom:16px;position:relative;overflow:hidden;">
    <iframe 
      src="https://maps.google.com/maps?q=Kantor+Bupati+Wonosobo,+Jawa+Tengah&t=&z=16&ie=UTF8&iwloc=&output=embed" 
      width="100%" 
      height="100%" 
      frameborder="0" 
      style="border:0;" 
      allowfullscreen="" 
      aria-hidden="false" 
      tabindex="0">
    </iframe>
    <!-- Overlay untuk penanda / badge -->
    <div style="position:absolute;bottom:12px;left:50%;transform:translateX(-50%);background:rgba(255,255,255,0.95);padding:6px 16px;border-radius:20px;font-size:11px;color:#1a1d1f;font-weight:700;box-shadow:0 4px 12px rgba(0,0,0,0.15);border:1px solid #eaecf0;white-space:nowrap;display:flex;align-items:center;gap:6px;">
      <span id="w_gpsDot" style="width:8px;height:8px;background:#f59e0b;border-radius:50%;display:inline-block;box-shadow:0 0 0 2px rgba(245,158,11,0.3);animation:pulse 1.5s infinite;"></span>
      <span id="w_radiusText">Mencari sinyal GPS...</span>
    </div>
  </div>

  <!-- Status card -->
  <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;text-align:center;margin-bottom:16px;">
    <?php if ($w_sudahCheckOut): ?>
      <div style="font-size:32px;margin-bottom:8px;">🏠</div>
      <div style="font-size:15px;font-weight:700;color:#1a1d1f;margin-bottom:4px;">Selesai Kerja</div>
      <div style="color:#64748b;font-size:12px;font-weight:500;">
        Masuk: <strong style="color:#1a1d1f;"><?= e($w_absensiToday['check_in']) ?></strong> &nbsp;|&nbsp; Keluar: <strong style="color:#1a1d1f;"><?= e($w_absensiToday['check_out']) ?></strong>
        <div style="margin-top:4px;color:#0ea5e9;font-weight:600;">Durasi: <?= formatDurasi($w_absensiToday['durasi_mnt']) ?></div>
      </div>
    <?php elseif ($w_sudahCheckIn): ?>
      <div style="font-size:32px;margin-bottom:8px;">✅</div>
      <div style="font-size:15px;font-weight:700;color:#10b981;margin-bottom:4px;">Sudah Check-In</div>
      <div style="color:#64748b;font-size:12px;font-weight:500;">
        Pukul <strong style="color:#1a1d1f;"><?= e($w_absensiToday['check_in']) ?></strong> — <?= absensiBadge($w_absensiToday['status']) ?>
      </div>
    <?php else: ?>
      <div style="font-size:32px;margin-bottom:8px;">⏱️</div>
      <div style="font-size:15px;font-weight:700;color:#f59e0b;margin-bottom:4px;">Belum Check-In</div>
      <div style="color:#64748b;font-size:12px;font-weight:500;line-height:1.4;">
        Waktu sekarang: <strong style="color:#1a1d1f;"><?= date('H:i:s') ?></strong><br>
        Jam masuk: 08:00 (Toleransi 0 menit)
      </div>
    <?php endif; ?>
  </div>

  <!-- Action buttons -->
  <?php if (!$w_sudahCheckIn): ?>
  <form method="POST" action="/simpekabjmk/absensi.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $w_csrfToken ?>">
    <input type="hidden" name="action" value="checkin">
    <input type="hidden" name="redirect_to" value="<?= e($w_redirect) ?>">
    <input type="hidden" name="is_luar_radius" id="w_isLuarRadius_in" value="0">

    <div id="w_luarRadiusBlock_in" class="hidden" style="margin-bottom:16px;text-align:left;">
      <div style="background:#fffbeb;border:1px solid #fcd34d;padding:12px;border-radius:12px;margin-bottom:12px;font-size:12px;color:#b45309;display:flex;gap:8px;align-items:start;">
        <span class="material-symbols-outlined" style="font-size:16px;">warning</span>
        <div>Anda berada di luar jangkauan 100 meter. Wajib mengisi keterangan dan mengunggah foto bukti (mis: foto selfie di lokasi). <strong>Peringatan: Absensi di luar jangkauan hanya diperbolehkan untuk keadaan darurat atau tugas luar dinas.</strong></div>
      </div>
      <div class="form-group" style="margin-bottom:12px;">
        <label class="label" style="font-size:12px;">Keterangan <span style="color:red;">*</span></label>
        <textarea name="keterangan" id="w_keterangan_in" class="input-card" rows="2" placeholder="Sebutkan alasan..." style="font-size:13px;"></textarea>
      </div>
      <div class="form-group">
        <label class="label" style="font-size:12px;">Unggah Bukti Foto <span style="color:red;">*</span></label>
        <input type="file" name="bukti_foto" id="w_bukti_foto_in" class="input-card" accept="image/*" style="font-size:13px;">
      </div>
    </div>

    <button type="submit" class="btn-primary" style="width:100%;font-size:14px;padding:12px;justify-content:center;box-shadow:0 8px 24px rgba(245,158,11,0.15);" onclick="return confirm('Konfirmasi check-in pukul <?= date('H:i') ?>?')">
      <span class="material-symbols-outlined" style="font-size:18px;">login</span>
      Check In Sekarang
    </button>
  </form>
  <?php elseif (!$w_sudahCheckOut): ?>
  <form method="POST" action="/simpekabjmk/absensi.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $w_csrfToken ?>">
    <input type="hidden" name="action" value="checkout">
    <input type="hidden" name="redirect_to" value="<?= e($w_redirect) ?>">
    <input type="hidden" name="is_luar_radius" id="w_isLuarRadius_out" value="0">

    <div id="w_luarRadiusBlock_out" class="hidden" style="margin-bottom:16px;text-align:left;">
      <div style="background:#fffbeb;border:1px solid #fcd34d;padding:12px;border-radius:12px;margin-bottom:12px;font-size:12px;color:#b45309;display:flex;gap:8px;align-items:start;">
        <span class="material-symbols-outlined" style="font-size:16px;">warning</span>
        <div>Anda berada di luar jangkauan 100 meter. Wajib mengisi keterangan dan mengunggah foto bukti (mis: foto selfie di lokasi). <strong>Peringatan: Absensi di luar jangkauan hanya diperbolehkan untuk keadaan darurat atau tugas luar dinas.</strong></div>
      </div>
      <div class="form-group" style="margin-bottom:12px;">
        <label class="label" style="font-size:12px;">Keterangan <span style="color:red;">*</span></label>
        <textarea name="keterangan" id="w_keterangan_out" class="input-card" rows="2" placeholder="Sebutkan alasan..." style="font-size:13px;"></textarea>
      </div>
      <div class="form-group">
        <label class="label" style="font-size:12px;">Unggah Bukti Foto <span style="color:red;">*</span></label>
        <input type="file" name="bukti_foto" id="w_bukti_foto_out" class="input-card" accept="image/*" style="font-size:13px;">
      </div>
    </div>

    <button type="submit" class="btn-danger" style="width:100%;font-size:14px;padding:12px;justify-content:center;box-shadow:0 8px 24px rgba(239,68,68,0.15);" onclick="return confirm('Konfirmasi check-out sekarang? Anda tidak bisa check-in lagi hari ini.')">
      <span class="material-symbols-outlined" style="font-size:18px;">logout</span>
      Check Out Pulang
    </button>
  </form>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const w_radiusText = document.getElementById('w_radiusText');
    const w_gpsDot = document.getElementById('w_gpsDot');
    if(!w_radiusText) return;
    
    const KANTOR_LAT = -7.361957;
    const KANTOR_LNG = 109.901170;

    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371e3;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    if ("geolocation" in navigator) {
        navigator.geolocation.watchPosition(
            function(position) {
                const distance = Math.round(calculateDistance(position.coords.latitude, position.coords.longitude, KANTOR_LAT, KANTOR_LNG));
                
                const toggleLuarRadius = (show) => {
                    ['in', 'out'].forEach(type => {
                        const block = document.getElementById('w_luarRadiusBlock_' + type);
                        const isHidden = document.getElementById('w_isLuarRadius_' + type);
                        const ket = document.getElementById('w_keterangan_' + type);
                        const foto = document.getElementById('w_bukti_foto_' + type);
                        if (block && isHidden && ket && foto) {
                            if (show) {
                                block.classList.remove('hidden');
                                isHidden.value = '1';
                                ket.required = true;
                                foto.required = true;
                            } else {
                                block.classList.add('hidden');
                                isHidden.value = '0';
                                ket.required = false;
                                foto.required = false;
                            }
                        }
                    });
                };

                if (distance <= 100) {
                    w_gpsDot.style.background = '#10b981';
                    w_gpsDot.style.boxShadow = '0 0 0 2px rgba(16,185,129,0.3)';
                    w_radiusText.innerHTML = `Di Kantor (Jarak: ${distance}m)`;
                    toggleLuarRadius(false);
                } else {
                    w_gpsDot.style.background = '#ef4444';
                    w_gpsDot.style.boxShadow = '0 0 0 2px rgba(239,68,68,0.3)';
                    w_radiusText.innerHTML = `Di Luar (Jarak: ${distance}m)`;
                    toggleLuarRadius(true);
                }
            },
            function(error) {
                w_gpsDot.style.background = '#ef4444';
                w_gpsDot.style.boxShadow = '0 0 0 2px rgba(239,68,68,0.3)';
                w_radiusText.innerHTML = "Sinyal GPS Error";
            },
            { enableHighAccuracy: true, maximumAge: 10000, timeout: 10000 }
        );
    } else {
        w_radiusText.innerHTML = "Tanpa GPS";
    }
});
</script>
