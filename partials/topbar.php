<?php
// partials/topbar.php
$user = currentUser();
$role = $user['role'] ?? '';
$nama = $user['nama'] ?? '';
$initials = getInisial($nama);
$avatarColors = ['super_admin'=>'#fee2e2','eksekutif'=>'#fce7f3','admin_bkpsdm'=>'#e0e7ff','atasan'=>'#fef3c7','pegawai'=>'#e0f2fe'];
$avatarTextColors = ['super_admin'=>'#ef4444','eksekutif'=>'#db2777','admin_bkpsdm'=>'#4f46e5','atasan'=>'#f59e0b','pegawai'=>'#0ea5e9'];
$bg  = $avatarColors[$role]  ?? '#e0f2fe';
$fg  = $avatarTextColors[$role] ?? '#0ea5e9';
?>
<header class="topbar">
  <!-- Mobile hamburger -->
  <button class="md:hidden" onclick="document.getElementById('sidebar').classList.toggle('mobile-open')"
    style="background:none;border:none;color:#64748b;cursor:pointer;padding:8px;">
    <span class="material-symbols-outlined">menu</span>
  </button>

  <!-- Search bar with Autocomplete -->
  <div class="relative hidden md:block" style="flex:1;max-width:360px;margin-left:16px;">
    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2" style="color:#94a3b8;font-size:20px;pointer-events:none;">search</span>
    <input type="text" id="topbarSearchInput" placeholder="Cari halaman/fitur..." autocomplete="off"
      style="width:100%;background:#ffffff;border:1px solid #eaecf0;border-radius:999px;padding:10px 16px 10px 42px;color:#1a1d1f;font-size:14px;outline:none;transition:all 0.2s;box-shadow:0 2px 8px rgba(0,0,0,0.02);"
      onfocus="this.style.borderColor='#0ea5e9';this.style.boxShadow='0 0 0 3px rgba(14,165,233,0.15)'" onblur="setTimeout(() => {this.style.borderColor='#eaecf0';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.02)'}, 200)"/>
    
    <!-- Dropdown Suggestions -->
    <div id="searchSuggestions" class="hidden" style="position:absolute;top:100%;left:0;right:0;margin-top:8px;background:#ffffff;border:1px solid #eaecf0;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.1);z-index:100;overflow:hidden;max-height:300px;overflow-y:auto;">
      <!-- list injected by JS -->
    </div>
  </div>

  <!-- Right side -->
  <div class="flex items-center gap-4 ml-auto">
    <!-- Notification -->
    <div style="position:relative;">
      <button onclick="document.getElementById('notifDropdown').classList.toggle('hidden')" style="background:#ffffff;border:1px solid #eaecf0;border-radius:50%;color:#64748b;cursor:pointer;width:40px;height:40px;display:flex;align-items:center;justify-content:center;position:relative;transition:all 0.2s;" title="Notifikasi"
        onmouseover="this.style.background='#f8fafc';this.style.color='#1a1d1f'" onmouseout="this.style.background='#ffffff';this.style.color='#64748b'">
        <span class="material-symbols-outlined" style="font-size:20px;">notifications</span>
        <span id="notifBadge" class="hidden" style="position:absolute;top:10px;right:10px;width:8px;height:8px;background:#ef4444;border-radius:50%;border:2px solid #ffffff;"></span>
      </button>

      <!-- Dropdown Notifikasi -->
      <div id="notifDropdown" class="hidden" style="position:absolute;top:48px;right:0;width:320px;background:#ffffff;border:1px solid #eaecf0;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.05);z-index:50;overflow:hidden;">
        <div style="padding:16px;border-bottom:1px solid #eaecf0;display:flex;justify-content:space-between;align-items:center;background:#f8fafc;">
          <h3 style="font-size:14px;font-weight:700;color:#1a1d1f;margin:0;">Notifikasi</h3>
          <span id="notifCount" style="background:#ef4444;color:#ffffff;font-size:11px;font-weight:600;padding:2px 8px;border-radius:999px;">0 Baru</span>
        </div>
        <div id="notifList" style="max-height:300px;overflow-y:auto;padding:8px 0;">
          <div style="padding:24px 16px;text-align:center;color:#94a3b8;font-size:13px;">Sedang memuat...</div>
        </div>
      </div>
    </div>

    <!-- Security button (admin only) -->
    <?php if (hasRole('super_admin')): ?>
    <a href="/simpekabjmk/keamanan.php"
      class="hidden md:flex items-center gap-2"
      style="background:#fef2f2;border:1px solid #fee2e2;color:#ef4444;padding:8px 16px;border-radius:999px;font-weight:600;font-size:13px;text-decoration:none;transition:all 0.2s;"
      onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
      <span class="material-symbols-outlined" style="font-size:18px;">shield</span>
      Brankas
    </a>
    <?php endif; ?>

    <!-- Profile -->
    <a href="/simpekabjmk/profil.php" class="flex items-center gap-3" style="padding-left:16px;border-left:1px solid #eaecf0;text-decoration:none;">
      <div class="hidden md:block text-right">
        <div style="font-size:14px;font-weight:700;color:#1a1d1f;"><?= e(explode(',', $nama)[0]) ?></div>
        <div style="color:#64748b;font-size:11px;font-weight:500;text-transform:capitalize;"><?= e($role) ?></div>
      </div>
      <div class="avatar avatar-sm" style="background:<?= e($bg) ?>;color:<?= e($fg) ?>;box-shadow:0 2px 8px rgba(0,0,0,0.05);"><?= e($initials) ?></div>
    </a>
  </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('topbarSearchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    if(!searchInput || !searchSuggestions) return;

    // Build available menu items based on PHP logic in sidebar
    const availableMenus = [
        { name: 'Beranda', url: '/simpekabjmk/dashboard.php', icon: 'grid_view' },
        { name: 'Profil Saya', url: '/simpekabjmk/profil.php', icon: 'person' },
        { name: 'Absensi Saya', url: '/simpekabjmk/absensi.php', icon: 'how_to_reg' },
        { name: 'E-Kinerja (SKP)', url: '/simpekabjmk/kinerja_skp.php', icon: 'assignment' },
        { name: 'Pengajuan Layanan', url: '/simpekabjmk/layanan_pengajuan.php', icon: 'folder_managed' },
        <?php if (hasAnyRole(['super_admin','admin_bkpsdm','atasan'])): ?>
        { name: 'Absensi Tim', url: '/simpekabjmk/absensi_tim.php', icon: 'groups' },
        <?php endif; ?>
        <?php if (hasAnyRole(['super_admin','atasan'])): ?>
        { name: 'Evaluasi Kinerja', url: '/simpekabjmk/kinerja_evaluasi.php', icon: 'fact_check' },
        { name: 'Approval Layanan', url: '/simpekabjmk/layanan_approval.php', icon: 'verified_user' },
        <?php endif; ?>
        <?php if (hasAnyRole(['super_admin','admin_bkpsdm'])): ?>
        { name: 'Verifikasi BKPSDM', url: '/simpekabjmk/layanan_verifikasi.php', icon: 'domain_verification' },
        { name: 'Data Pegawai', url: '/simpekabjmk/pegawai.php', icon: 'badge' },
        { name: 'Bantuan IT', url: '/simpekabjmk/permintaan_reset.php', icon: 'support_agent' },
        { name: 'Brankas Keamanan', url: '/simpekabjmk/keamanan.php', icon: 'shield' },
        { name: 'Log Aktivitas', url: '/simpekabjmk/log.php', icon: 'monitoring' },
        { name: 'Approval Absensi', url: '/simpekabjmk/absensi_approval.php', icon: 'rule' },
        <?php endif; ?>
    ];

    searchInput.addEventListener('input', function() {
        const val = this.value.toLowerCase().trim();
        searchSuggestions.innerHTML = '';
        
        if (val.length === 0) {
            searchSuggestions.classList.add('hidden');
            return;
        }

        const matches = availableMenus.filter(m => m.name.toLowerCase().includes(val));
        
        if (matches.length > 0) {
            matches.forEach(m => {
                const item = document.createElement('a');
                item.href = m.url;
                item.style.cssText = 'display:flex;align-items:center;gap:12px;padding:12px 16px;text-decoration:none;color:#1a1d1f;font-size:13px;border-bottom:1px solid #f1f5f9;transition:background 0.2s;';
                item.onmouseover = () => item.style.background = '#f8fafc';
                item.onmouseout = () => item.style.background = 'transparent';
                item.innerHTML = `<span class="material-symbols-outlined" style="color:#94a3b8;font-size:18px;">${m.icon}</span> <span style="font-weight:500;">${m.name}</span>`;
                searchSuggestions.appendChild(item);
            });
            searchSuggestions.classList.remove('hidden');
        } else {
            searchSuggestions.innerHTML = `<div style="padding:16px;color:#94a3b8;font-size:13px;text-align:center;">Tidak ada hasil</div>`;
            searchSuggestions.classList.remove('hidden');
        }
    });

    searchInput.addEventListener('focus', function() {
        if(this.value.trim().length > 0) {
            searchSuggestions.classList.remove('hidden');
        }
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
            searchSuggestions.classList.add('hidden');
        }
    });
});
</script>

<!-- Notifikasi Real-time Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  function fetchNotifications() {
    fetch('/simpekabjmk/api_notifications.php')
      .then(r => r.json())
      .then(data => {
        const badge = document.getElementById('notifBadge');
        const count = document.getElementById('notifCount');
        const list = document.getElementById('notifList');
        
        if (data.count > 0) {
          badge.classList.remove('hidden');
          count.textContent = data.count + ' Baru';
        } else {
          badge.classList.add('hidden');
          count.textContent = '0 Baru';
        }
        
        if (data.items.length > 0) {
          list.innerHTML = data.items.map(item => `
            <a href="${item.link}" style="display:flex;gap:12px;padding:12px 16px;text-decoration:none;border-bottom:1px solid #f1f5f9;transition:background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
              <div style="width:36px;height:36px;border-radius:50%;background:${item.bg};color:${item.color};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span class="material-symbols-outlined" style="font-size:18px;">${item.icon}</span>
              </div>
              <div>
                <div style="font-size:13px;font-weight:600;color:#1a1d1f;margin-bottom:2px;">${item.title}</div>
                <div style="font-size:12px;color:#64748b;margin-bottom:4px;line-height:1.4;">${item.message}</div>
                <div style="font-size:11px;color:#94a3b8;font-weight:500;">${item.time}</div>
              </div>
            </a>
          `).join('');
        } else {
          list.innerHTML = '<div style="padding:32px 16px;text-align:center;color:#94a3b8;font-size:13px;"><span class="material-symbols-outlined" style="font-size:32px;color:#e2e8f0;margin-bottom:8px;display:block;">notifications_off</span>Belum ada notifikasi baru</div>';
        }
      })
      .catch(err => console.error('Error fetching notifs:', err));
  }

  // Panggil pertama kali
  fetchNotifications();
  // Polling setiap 10 detik
  setInterval(fetchNotifications, 10000);

  // Tutup dropdown saat klik di luar
  window.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notifDropdown');
    const button = dropdown.previousElementSibling;
    if (!dropdown.contains(e.target) && !button.contains(e.target) && !dropdown.classList.contains('hidden')) {
      dropdown.classList.add('hidden');
    }
  });
});
</script>
