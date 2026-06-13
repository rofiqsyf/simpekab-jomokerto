<?php
// partials/sidebar.php
// Requires: $currentPage (string), session sudah aktif
$user = currentUser();
$role = $user['role'] ?? '';
$nama = $user['nama'] ?? '';
$initials = getInisial($nama);
$avatarColors = ['super_admin'=>'#fee2e2','eksekutif'=>'#fce7f3','admin_bkpsdm'=>'#e0e7ff','atasan'=>'#fef3c7','pegawai'=>'#e0f2fe'];
$avatarTextColors = ['super_admin'=>'#ef4444','eksekutif'=>'#db2777','admin_bkpsdm'=>'#4f46e5','atasan'=>'#f59e0b','pegawai'=>'#0ea5e9'];
$bg  = $avatarColors[$role]  ?? '#e0f2fe';
$fg  = $avatarTextColors[$role] ?? '#0ea5e9';
?>
<nav id="sidebar" class="flex flex-col h-screen sticky top-0 overflow-y-auto">

  <!-- Brand -->
  <div class="p-6" style="border-bottom:1px solid #eaecf0;">
    <div class="flex items-center gap-3">
      <div class="w-12 h-12 flex items-center justify-center">
        <img src="/simpekabjmk/assets/logo_jomokerto.png" alt="Logo" style="width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 4px 6px rgba(0,0,0,0.05));" />
      </div>
      <div>
        <div class="font-extrabold tracking-tight" style="color:#1a1d1f;font-size:16px;line-height:1.2;">SIMPEKAB<br>JOMOKERTO</div>
        <div style="color:#94a3b8;font-family:'Inter',sans-serif;font-weight:500;font-size:10px;margin-top:2px;line-height:1.2;max-width:90px;white-space:normal;">Sistem Informasi Kepegawaian</div>
      </div>
    </div>
  </div>

  <!-- User Info -->
  <div class="p-5" style="border-bottom:1px solid #eaecf0;">
    <div class="flex items-center gap-3">
      <div class="avatar avatar-md" style="background:<?= e($bg) ?>;color:<?= e($fg) ?>;"><?= e($initials) ?></div>
      <div style="overflow:hidden;">
        <div style="font-size:14px;font-weight:700;color:#1a1d1f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e(explode(',', $nama)[0]) ?></div>
        <div style="margin-top:2px;"><?= roleBadge($role) ?></div>
      </div>
    </div>
    <!-- Session info -->
    <div class="mt-4 flex items-center gap-2" style="color:#64748b;font-family:'Inter',sans-serif;font-size:12px;font-weight:500;">
      <span class="material-symbols-outlined" style="font-size:16px;color:#10b981;">check_circle</span>
      Sesi Aktif
      <span class="dot-green" style="margin-left:auto;"></span>
    </div>
  </div>

  <!-- Navigation Links -->
  <div class="flex-1 py-4">
    <div style="padding:8px 24px 8px;color:#94a3b8;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Menu Utama</div>

    <a href="/simpekabjmk/dashboard.php" class="nav-item <?= $currentPage==='dashboard'?'active':'' ?>">
      <span class="material-symbols-outlined">grid_view</span>
      Beranda
    </a>
    <a href="/simpekabjmk/profil.php" class="nav-item <?= $currentPage==='profil'?'active':'' ?>">
      <span class="material-symbols-outlined">person</span>
      Profil Saya
    </a>
    <a href="/simpekabjmk/absensi.php" class="nav-item <?= $currentPage==='absensi'?'active':'' ?>">
      <span class="material-symbols-outlined">how_to_reg</span>
      Absensi Saya
    </a>

    <div style="padding:16px 24px 8px;color:#94a3b8;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Layanan ASN</div>
    <a href="/simpekabjmk/kinerja_skp.php" class="nav-item <?= $currentPage==='kinerja'?'active':'' ?>">
      <span class="material-symbols-outlined">assignment</span>
      E-Kinerja (SKP)
    </a>
    <a href="/simpekabjmk/layanan_pengajuan.php" class="nav-item <?= $currentPage==='layanan_pengajuan'?'active':'' ?>">
      <span class="material-symbols-outlined">folder_managed</span>
      Pengajuan Layanan
    </a>

    <?php if (hasAnyRole(['super_admin','admin_bkpsdm','atasan','eksekutif'])): ?>
    <div style="padding:16px 24px 8px;color:#94a3b8;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Manajemen Tim</div>
    
    <?php if (hasAnyRole(['super_admin','admin_bkpsdm','atasan'])): ?>
    <a href="/simpekabjmk/absensi_tim.php" class="nav-item <?= $currentPage==='absensi_tim'?'active':'' ?>">
      <span class="material-symbols-outlined">groups</span>
      Absensi Tim
    </a>
    <?php endif; ?>

    <?php if (hasAnyRole(['super_admin','atasan'])): ?>
    <a href="/simpekabjmk/kinerja_evaluasi.php" class="nav-item <?= $currentPage==='kinerja_evaluasi'?'active':'' ?>">
      <span class="material-symbols-outlined">fact_check</span>
      Evaluasi Kinerja
    </a>
    <a href="/simpekabjmk/layanan_approval.php" class="nav-item <?= $currentPage==='layanan_approval'?'active':'' ?>">
      <span class="material-symbols-outlined">verified_user</span>
      Approval Layanan
    </a>
    <?php endif; ?>
    <?php if (hasAnyRole(['super_admin','admin_bkpsdm'])): ?>
    <a href="/simpekabjmk/layanan_verifikasi.php" class="nav-item <?= $currentPage==='layanan_verifikasi'?'active':'' ?>">
      <span class="material-symbols-outlined">domain_verification</span>
      Verifikasi Cuti/Izin
    </a>
    <a href="/simpekabjmk/dokumen_verifikasi.php" class="nav-item <?= $currentPage==='dokumen_verifikasi'?'active':'' ?>">
      <span class="material-symbols-outlined">plagiarism</span>
      Verifikasi Dokumen
    </a>
    <?php endif; ?>
    
    <!-- Laporan KGB yang bisa dilihat atasan & eksekutif -->
    <a href="/simpekabjmk/riwayat_kgb.php" class="nav-item <?= $currentPage==='riwayat_kgb'?'active':'' ?>">
      <span class="material-symbols-outlined">history</span>
      Laporan Riwayat KGB
    </a>
    <?php endif; ?>

    <?php if (hasRole('super_admin') || hasRole('admin_bkpsdm')): ?>
    <div style="padding:16px 24px 8px;color:#94a3b8;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Administrasi</div>
    <a href="/simpekabjmk/pegawai.php" class="nav-item <?= $currentPage==='pegawai'?'active':'' ?>">
      <span class="material-symbols-outlined">badge</span>
      Data Pegawai
    </a>
    <a href="/simpekabjmk/permintaan_reset.php" class="nav-item <?= $currentPage==='permintaan_reset'?'active':'' ?>">
      <span class="material-symbols-outlined">support_agent</span>
      Bantuan IT
    </a>
    <a href="/simpekabjmk/keamanan.php" class="nav-item <?= $currentPage==='keamanan'?'active':'' ?>">
      <span class="material-symbols-outlined">shield</span>
      Brankas Keamanan
    </a>
    <a href="/simpekabjmk/log.php" class="nav-item <?= $currentPage==='log'?'active':'' ?>">
      <span class="material-symbols-outlined">monitoring</span>
      Log Aktivitas
    </a>
    <a href="/simpekabjmk/absensi_approval.php" class="nav-item <?= $currentPage==='absensi_approval'?'active':'' ?>">
      <span class="material-symbols-outlined">rule</span>
      Approval Absensi
    </a>
    <?php endif; ?>
  </div>

  <!-- Footer -->
  <div class="p-4" style="border-top:1px solid #eaecf0;background:#f8fafc;">
    <a href="/simpekabjmk/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-500 font-semibold text-sm transition-colors hover:bg-red-50" onclick="return confirm('Yakin ingin keluar dari sesi aman ini?')">
      <span class="material-symbols-outlined">logout</span>
      Keluar
    </a>
  </div>

</nav>
