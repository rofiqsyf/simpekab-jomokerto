<?php
// ============================================================
// profil.php — Halaman Profil + Informasi Sesi
// Akses: Semua role
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();

$currentPage = 'profil';
$pageTitle   = 'Profil Saya';
$user        = currentUser();
$errors      = [];

// Ambil data lengkap dari DB
$stmt = $pdo->prepare("
    SELECT u.id, u.nama, u.email, u.role, u.last_login, u.created_at, u.login_attempts, u.locked_until,
           p.nip, p.nik, p.npwp, p.divisi, p.posisi, p.golongan, p.pendidikan, p.jenis_asn, p.no_telp, p.tgl_masuk, p.status
    FROM users u
    LEFT JOIN pegawai p ON p.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$user['id']]);
$profil = $stmt->fetch();

// ============================================================
// GANTI PASSWORD (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();

    $action = $_POST['action'] ?? '';

    if ($action === 'ganti_password') {
        $oldPass  = $_POST['old_password']  ?? '';
        $newPass  = $_POST['new_password']  ?? '';
        $newPass2 = $_POST['new_password2'] ?? '';

        // Validasi
        if (empty($oldPass))       $errors[] = 'Password lama wajib diisi';
        if (strlen($newPass) < 8)  $errors[] = 'Password baru minimal 8 karakter';
        if ($newPass !== $newPass2) $errors[] = 'Konfirmasi password tidak cocok';
        if ($newPass === $oldPass)  $errors[] = 'Password baru tidak boleh sama dengan yang lama';

        if (empty($errors)) {
            // Verifikasi password lama
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();

            if (!password_verify($oldPass, $row['password'])) {
                $errors[] = 'Password lama tidak sesuai';
            } else {
                // Hash password baru dengan Bcrypt
                $newHash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$newHash, $user['id']]);

                logActivity($user['id'], 'PASSWORD_CHANGE', 'Ganti password — Bcrypt rehash', 'info');
                setFlash('success', 'Password berhasil diperbarui dengan Bcrypt cost=12!');
                redirect('/simpekabjmk/profil.php');
            }
        }
    }
}

generateCsrfToken();
$sisaSesiDetik = isset($_SESSION['login_time']) ? max(0, 3600 - (time() - $_SESSION['login_time'])) : 0;
$sisaSesiMenit = intdiv($sisaSesiDetik, 60);
$sisaSesiSaatIni = sprintf('%02d:%02d', intdiv($sisaSesiDetik, 60), $sisaSesiDetik % 60);
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      <?= renderFlash() ?>

      <h1 class="section-title">Profil Saya</h1>
      <p class="section-subtitle">Informasi akun, keamanan sesi, dan cookie</p>

      <?php if (!empty($errors)): ?>
      <div class="alert alert-error" style="margin-bottom:24px;">
        <span class="material-symbols-outlined" style="flex-shrink:0;">error</span>
        <div><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
      </div>
      <?php endif; ?>

      <style>
        .profile-grid { display: grid; gap: 24px; grid-template-columns: 1fr; }
        @media (min-width: 1024px) { .profile-grid { grid-template-columns: 1fr 2fr; } }
        .profile-single { max-width: 500px; margin: 0 auto; }
      </style>
      <div class="<?= hasRole('super_admin') ? 'profile-grid' : 'profile-single' ?>">

        <!-- Profile Card -->
        <div class="card" style="text-align:center;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <div class="avatar avatar-lg" style="background:#e0f2fe;color:#0ea5e9;margin:0 auto 20px;font-size:24px;font-weight:700;"><?= e(getInisial($profil['nama'])) ?></div>
          <h2 style="font-size:20px;font-weight:800;color:#1a1d1f;margin-bottom:8px;"><?= e($profil['nama']) ?></h2>
          <div style="margin-bottom:20px;"><?= roleBadge($profil['role']) ?></div>

          <div style="margin-top:24px;display:flex;flex-direction:column;gap:16px;text-align:left;padding-top:20px;border-top:1px solid #eaecf0;">
            <?php foreach ([
              ['badge',    'NIP / NIK', ($profil['nip'] ?? '—') . ' / ' . ($profil['nik'] ?? '—')],
              ['email',    'Email', $profil['email']],
              ['school',   'Pendidikan', $profil['pendidikan'] ?? '—'],
              ['stars',    'Golongan Ruang', ($profil['golongan'] ?? '—') . ' (' . ($profil['jenis_asn'] ?? 'PNS') . ')'],
              ['apartment','Instansi/Unit Kerja', $profil['divisi'] ?? '—'],
              ['work',     'Jabatan', $profil['posisi'] ?? '—'],
              ['phone',    'No. Telepon', $profil['no_telp'] ?? '—'],
              ['calendar_today', 'TMT / Tgl Masuk', $profil['tgl_masuk'] ? formatTanggalId($profil['tgl_masuk']) : '—'],
            ] as [$icon, $label, $val]): ?>
            <div style="display:flex;align-items:start;gap:12px;color:#1a1d1f;font-size:14px;font-weight:600;">
              <span class="material-symbols-outlined" style="color:#94a3b8;font-size:20px;flex-shrink:0;background:#f8fafc;padding:6px;border-radius:8px;"><?= $icon ?></span>
              <div>
                <div style="color:#64748b;font-size:12px;font-weight:500;margin-bottom:2px;"><?= $label ?></div>
                <div><?= e($val) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <button onclick="showModal('modal-ganti-password')" class="btn-ghost" style="width:100%;margin-top:24px;justify-content:center;background:#ffffff;border:1px solid #eaecf0;padding:12px;font-weight:600;">
            <span class="material-symbols-outlined" style="font-size:18px;">lock_reset</span>
            Ganti Password
          </button>
        </div>

        </div>
        
        <!-- Tab Konten Kanan -->
        <div style="display:flex;flex-direction:column;gap:24px;">
          <!-- E-Wallet Digital Locker -->
          <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
              <h3 style="font-size:16px;font-weight:700;color:#1a1d1f;display:flex;align-items:center;gap:10px;">
                <span class="material-symbols-outlined" style="color:#8b5cf6;background:#f5f3ff;padding:8px;border-radius:12px;">folder_open</span>
                E-Wallet Dokumen (Digital Locker)
              </h3>
              <button class="btn-primary" style="padding:8px 16px;font-size:13px;" onclick="alert('Fitur unggah berkas sedang dikembangkan.')">
                <span class="material-symbols-outlined" style="font-size:16px;">upload</span> Unggah
              </button>
            </div>
            <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
              <?php foreach ([
                ['SK CPNS', 'sk_cpns.pdf', 'Telah Diverifikasi BKN', '#10b981'],
                ['SK PNS', 'sk_pns.pdf', 'Telah Diverifikasi BKN', '#10b981'],
                ['SK Jabatan Terakhir', 'sk_jabatan.pdf', 'Telah Diverifikasi BKPSDM', '#10b981'],
                ['Ijazah Terakhir', 'ijazah.pdf', 'Telah Diverifikasi BKPSDM', '#10b981'],
                ['Sertifikat Diklat PIM', '-', 'Belum Diunggah', '#ef4444'],
              ] as [$doc, $file, $status, $color]): ?>
              <div style="border:1px solid #eaecf0;border-radius:12px;padding:16px;display:flex;align-items:center;gap:16px;background:#ffffff;">
                <div style="width:48px;height:48px;border-radius:12px;background:#f8fafc;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                  <span class="material-symbols-outlined" style="font-size:24px;"><?= $file !== '-' ? 'picture_as_pdf' : 'warning' ?></span>
                </div>
                <div>
                  <div style="font-size:14px;font-weight:700;color:#1a1d1f;margin-bottom:4px;"><?= $doc ?></div>
                  <?php if ($file !== '-'): ?>
                  <a href="#" style="color:#3b82f6;font-size:13px;font-weight:500;text-decoration:none;display:block;margin-bottom:4px;"><?= $file ?></a>
                  <?php endif; ?>
                  <div style="color:<?= $color ?>;font-size:12px;font-weight:600;"><?= $status ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Security Info -->
          <?php if (hasRole('super_admin')): ?>
          <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
            <h3 style="font-size:16px;font-weight:700;color:#1a1d1f;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
              <span class="material-symbols-outlined" style="color:#0ea5e9;background:#e0f2fe;padding:8px;border-radius:12px;">timer</span>
              Informasi Sesi Aktif (PHP $_SESSION)
            </h3>
            <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
              <?php foreach ([
                ['Session ID (partial)',session_id() ? substr(session_id(),0,16).'...' : '—', '#3b82f6'],
                ['Login Time',         $_SESSION['login_time'] ? date('H:i:s', $_SESSION['login_time']) : '—', '#10b981'],
                ['IP Address',         $_SESSION['ip_address'] ?? '—', '#64748b'],
                ['Sisa Waktu Sesi',    $sisaSesiSaatIni . ' menit', $sisaSesiMenit < 5 ? '#ef4444' : '#f59e0b'],
              ] as [$label, $val, $color]): ?>
              <div class="stat-box" style="background:#f8fafc;border:1px solid #eaecf0;border-radius:12px;padding:16px;">
                <div style="color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;"><?= $label ?></div>
                <div style="color:<?= $color ?>;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:700;"><?= e($val) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
            <h3 style="font-size:16px;font-weight:700;color:#1a1d1f;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
              <span class="material-symbols-outlined" style="color:#10b981;background:#f0fdf4;padding:8px;border-radius:12px;">key</span>
              Keamanan Akun
            </h3>
            <div style="display:flex;flex-direction:column;gap:12px;">
              <?php foreach ([
                ['Algoritma Hash',    'Bcrypt (cost=12) — OWASP Compliant',     true],
                ['Session Regenerate','session_regenerate_id(true) saat login',   true],
                ['CSRF Protection',   'bin2hex(random_bytes(32)) — hash_equals()', true],
                ['Rate Limiting',     'Lockout 15 menit setelah 5x gagal',        true],
                ['Percobaan Login Gagal', ($profil['login_attempts'] ?? 0) . 'x (limit: 5x)', ($profil['login_attempts'] ?? 0) < 5],
              ] as [$label, $desc, $ok]): ?>
              <div style="display:flex;align-items:start;gap:12px;padding:16px;background:#ffffff;border:1px solid #eaecf0;border-radius:12px;">
                <span class="material-symbols-outlined" style="color:<?= $ok?'#10b981':'#ef4444' ?>;font-size:24px;flex-shrink:0;"><?= $ok?'check_circle':'cancel' ?></span>
                <div>
                  <div style="color:#1a1d1f;font-weight:700;font-size:14px;margin-bottom:4px;"><?= e($label) ?></div>
                  <div style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:500;"><?= e($desc) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ganti Password -->
<div class="modal-backdrop" id="modal-ganti-password">
  <div class="modal" style="background:#ffffff;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,0.1);border:1px solid #eaecf0;">
    <button onclick="closeModal('modal-ganti-password')" style="position:absolute;top:16px;right:16px;background:none;border:none;color:#94a3b8;cursor:pointer;font-size:24px;line-height:1;transition:color 0.2s;">×</button>
    <h3 style="font-size:20px;font-weight:800;color:#1a1d1f;margin-bottom:8px;">Ganti Password</h3>
    <p style="color:#64748b;font-size:13px;font-weight:500;margin-bottom:24px;">Hash baru menggunakan Bcrypt cost=12</p>
    <form method="POST">
      <?= csrfInput() ?>
      <input type="hidden" name="action" value="ganti_password">
      <div class="form-group">
        <label class="label">Password Lama</label>
        <input type="password" name="old_password" class="input-card" placeholder="Password saat ini" required/>
      </div>
      <div class="form-group">
        <label class="label">Password Baru (min. 8 karakter)</label>
        <input type="password" name="new_password" id="password-new" class="input-card" placeholder="Password baru..." required/>
        <div class="progress-bar" style="margin-top:8px;height:6px;"><div id="pass-strength-bar" class="progress-fill" style="width:0%;background:#ef4444;"></div></div>
        <p id="pass-strength-label" style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:500;margin-top:6px;"></p>
      </div>
      <div class="form-group">
        <label class="label">Konfirmasi Password Baru</label>
        <input type="password" name="new_password2" class="input-card" placeholder="Ulangi password baru..." required/>
      </div>
      <div style="display:flex;gap:12px;margin-top:16px;padding-top:20px;border-top:1px solid #eaecf0;">
        <button type="submit" class="btn-primary" style="padding:10px 20px;">
          <span class="material-symbols-outlined" style="font-size:18px;">save</span> Simpan
        </button>
        <button type="button" class="btn-ghost" onclick="closeModal('modal-ganti-password')" style="padding:10px 20px;background:#ffffff;border:1px solid #eaecf0;">Batal</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
