<?php
// partials/topbar.php
$user = currentUser();
$role = $user['role'] ?? '';
$nama = $user['nama'] ?? '';
$initials = getInisial($nama);
$avatarColors = ['admin'=>'#fee2e2','manager'=>'#fef3c7','karyawan'=>'#e0f2fe'];
$avatarTextColors = ['admin'=>'#ef4444','manager'=>'#f59e0b','karyawan'=>'#0ea5e9'];
$bg  = $avatarColors[$role]  ?? '#e0f2fe';
$fg  = $avatarTextColors[$role] ?? '#0ea5e9';
?>
<header class="topbar">
  <!-- Mobile hamburger -->
  <button class="md:hidden" onclick="document.getElementById('sidebar').classList.toggle('mobile-open')"
    style="background:none;border:none;color:#64748b;cursor:pointer;padding:8px;">
    <span class="material-symbols-outlined">menu</span>
  </button>

  <!-- Search bar -->
  <div class="relative hidden md:block" style="flex:1;max-width:360px;margin-left:16px;">
    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2" style="color:#94a3b8;font-size:20px;pointer-events:none;">search</span>
    <input type="text" placeholder="Search..."
      style="width:100%;background:#ffffff;border:1px solid #eaecf0;border-radius:999px;padding:10px 16px 10px 42px;color:#1a1d1f;font-size:14px;outline:none;transition:all 0.2s;box-shadow:0 2px 8px rgba(0,0,0,0.02);"
      onfocus="this.style.borderColor='#ffb800';this.style.boxShadow='0 0 0 3px rgba(255,184,0,0.15)'" onblur="this.style.borderColor='#eaecf0';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.02)'"/>
  </div>

  <!-- Right side -->
  <div class="flex items-center gap-4 ml-auto">
    <!-- Notification -->
    <button style="background:#ffffff;border:1px solid #eaecf0;border-radius:50%;color:#64748b;cursor:pointer;width:40px;height:40px;display:flex;align-items:center;justify-content:center;position:relative;transition:all 0.2s;" title="Notifikasi"
      onmouseover="this.style.background='#f8fafc';this.style.color='#1a1d1f'" onmouseout="this.style.background='#ffffff';this.style.color='#64748b'">
      <span class="material-symbols-outlined" style="font-size:20px;">notifications</span>
      <span style="position:absolute;top:10px;right:10px;width:8px;height:8px;background:#ef4444;border-radius:50%;border:2px solid #ffffff;"></span>
    </button>

    <!-- Security button (admin only) -->
    <?php if (hasRole('admin')): ?>
    <a href="/simpeg_mini/keamanan.php"
      class="hidden md:flex items-center gap-2"
      style="background:#fef2f2;border:1px solid #fee2e2;color:#ef4444;padding:8px 16px;border-radius:999px;font-weight:600;font-size:13px;text-decoration:none;transition:all 0.2s;"
      onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
      <span class="material-symbols-outlined" style="font-size:18px;">shield</span>
      Brankas
    </a>
    <?php endif; ?>

    <!-- Profile -->
    <a href="/simpeg_mini/profil.php" class="flex items-center gap-3" style="padding-left:16px;border-left:1px solid #eaecf0;text-decoration:none;">
      <div class="hidden md:block text-right">
        <div style="font-size:14px;font-weight:700;color:#1a1d1f;"><?= e(explode(',', $nama)[0]) ?></div>
        <div style="color:#64748b;font-size:11px;font-weight:500;text-transform:capitalize;"><?= e($role) ?></div>
      </div>
      <div class="avatar avatar-sm" style="background:<?= e($bg) ?>;color:<?= e($fg) ?>;box-shadow:0 2px 8px rgba(0,0,0,0.05);"><?= e($initials) ?></div>
    </a>
  </div>
</header>
