<?php
// partials/footer.php — Closing HTML tags + shared JS
?>
</div><!-- .page-content -->
</div><!-- .main-content -->
</div><!-- .app-layout -->

<div id="toast-container" style="position:fixed;bottom:24px;right:24px;z-index:1000;display:flex;flex-direction:column;gap:8px;"></div>

<script>
// ==========================================
// TOAST NOTIFICATIONS
// ==========================================
function showToast(message, type = 'info') {
  const container = document.getElementById('toast-container');
  const icons = { success:'check_circle', error:'error', info:'info', warning:'warning' };
  const styles = {
    success: 'background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;',
    error:   'background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;',
    warning: 'background:#fffbeb;border:1px solid #fde68a;color:#b45309;',
    info:    'background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;',
  };
  const toast = document.createElement('div');
  toast.style.cssText = `padding:12px 20px;border-radius:8px;font-size:14px;display:flex;align-items:center;gap:10px;min-width:240px;animation:float-up 0.3s ease forwards;font-family:'Inter',sans-serif;${styles[type]||styles.info}`;
  toast.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px;">${icons[type]||'info'}</span>${message}`;
  container.appendChild(toast);
  setTimeout(() => { toast.style.opacity='0'; toast.style.transform='translateY(10px)'; toast.style.transition='all 0.3s'; setTimeout(()=>toast.remove(),300); }, 3500);
}

// ==========================================
// CONFIRM DELETE HELPER
// ==========================================
function confirmDelete(url, message) {
  if (confirm(message || 'Yakin ingin menghapus data ini? Aksi tidak dapat dibatalkan.')) {
    window.location.href = url;
  }
}

// ==========================================
// MODAL TOGGLE
// ==========================================
function showModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.querySelectorAll('.modal-backdrop').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
});

// ==========================================
// AUTO-DISMISS FLASH MESSAGES
// ==========================================
document.querySelectorAll('.alert').forEach(alert => {
  setTimeout(() => { alert.style.transition='opacity 0.5s'; alert.style.opacity='0'; setTimeout(()=>alert.remove(),500); }, 5000);
});

// ==========================================
// PASSWORD STRENGTH INDICATOR
// ==========================================
const passInput = document.getElementById('password-new');
if (passInput) {
  passInput.addEventListener('input', function() {
    const bar = document.getElementById('pass-strength-bar');
    const label = document.getElementById('pass-strength-label');
    if (!bar || !label) return;
    let score = 0;
    const v = this.value;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const map = [[0,'0%','#ef4444',''],['25%','#ef4444','Lemah'],['50%','#f59e0b','Sedang'],['75%','#3b82f6','Kuat'],['100%','#10b981','Sangat Kuat']];
    const [w,c,l] = map[score] ? [map[score][0],map[score][1],map[score][2]] : ['0%','#ef4444',''];
    bar.style.width = w; bar.style.background = c;
    label.textContent = l ? `Kekuatan: ${l}` : ''; label.style.color = c;
  });
}

// ==========================================
// SHOW FLASH VIA PHP SESSION (jika ada)
// ==========================================
<?php if (!empty($_SESSION['flash_js'])): ?>
showToast(<?= json_encode($_SESSION['flash_js']['msg']) ?>, <?= json_encode($_SESSION['flash_js']['type']) ?>);
<?php unset($_SESSION['flash_js']); endif; ?>
</script>
</body>
</html>
