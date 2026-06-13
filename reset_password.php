<?php
// ============================================================
// reset_password.php — Reset Password Pegawai (Admin Only)
// Bcrypt password_hash dengan cost=12
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireRole(['super_admin', 'admin_bkpsdm']);

$currentPage = 'pegawai';
$pageTitle   = 'Reset Password';
$errors      = [];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('/simpekabjmk/pegawai.php');

// Fetch pegawai
$stmt = $pdo->prepare("SELECT u.id, u.nama, u.email, p.posisi FROM users u LEFT JOIN pegawai p ON p.user_id=u.id WHERE u.id=?");
$stmt->execute([$id]);
$pegawai = $stmt->fetch();
if (!$pegawai) { setFlash('error','Pegawai tidak ditemukan.'); redirect('/simpekabjmk/pegawai.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();

    $newPass  = $_POST['new_password']  ?? '';
    $newPass2 = $_POST['new_password2'] ?? '';

    if (strlen($newPass) < 8)    $errors[] = 'Password minimal 8 karakter';
    if ($newPass !== $newPass2)  $errors[] = 'Konfirmasi password tidak cocok';

    if (empty($errors)) {
        $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare("UPDATE users SET password=?, login_attempts=0, locked_until=NULL WHERE id=?")
            ->execute([$hash, $id]);
            
        // Auto-resolve setiap permintaan layanan/reset yang masih pending untuk user ini
        $pdo->prepare("UPDATE password_reset_requests SET status='resolved' WHERE email=? AND status='pending'")
            ->execute([$pegawai['email']]);

        logActivity(currentUser()['id'], 'PASSWORD_RESET', "Reset password untuk: {$pegawai['email']}", 'warning');
        setFlash('success', "Password {$pegawai['nama']} berhasil direset. Tiket bantuan IT terkait juga otomatis ditandai selesai.");
        redirect('/simpekabjmk/pegawai.php');
    }
}

generateCsrfToken();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      <?= renderFlash() ?>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;">
        <h1 class="section-title">Reset Password</h1>
        <a href="/simpekabjmk/pegawai.php" class="btn-ghost" style="background:#ffffff;border:1px solid #eaecf0;box-shadow:0 2px 5px rgba(0,0,0,0.02);">
          <span class="material-symbols-outlined">arrow_back</span> Kembali
        </a>
      </div>

      <div style="max-width:560px;">
        <!-- Target pegawai info -->
        <div class="card" style="margin-bottom:24px;display:flex;align-items:center;gap:20px;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <div class="avatar avatar-md" style="background:#e0f2fe;color:#0ea5e9;font-weight:700;font-size:20px;width:56px;height:56px;"><?= e(getInisial($pegawai['nama'])) ?></div>
          <div>
            <div style="font-size:18px;font-weight:700;color:#1a1d1f;margin-bottom:4px;"><?= e($pegawai['nama']) ?></div>
            <div style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;"><?= e($pegawai['email']) ?></div>
          </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin-bottom:24px;">
          <span class="material-symbols-outlined" style="flex-shrink:0;">error</span>
          <div><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <?= csrfInput() ?>
          <h2 style="font-size:18px;font-weight:700;color:#1a1d1f;margin-bottom:24px;display:flex;align-items:center;gap:10px;padding-bottom:16px;border-bottom:1px solid #eaecf0;">
            <span class="material-symbols-outlined" style="color:#f59e0b;background:#fffbeb;padding:8px;border-radius:12px;">lock_reset</span>
            Password Baru
          </h2>
          <div class="form-group">
            <label class="label">Password Baru * (min. 8 karakter)</label>
            <input type="password" name="new_password" id="password-new" class="input-card" placeholder="Password baru..." required/>
            <div class="progress-bar" style="margin-top:8px;height:6px;"><div id="pass-strength-bar" class="progress-fill" style="width:0%;background:#ef4444;"></div></div>
            <p id="pass-strength-label" style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:500;margin-top:6px;"></p>
          </div>
          <div class="form-group" style="margin-top:16px;">
            <label class="label">Konfirmasi Password *</label>
            <input type="password" name="new_password2" class="input-card" placeholder="Ulangi password baru..." required/>
          </div>
          <div style="background:#f8fafc;border:1px solid #eaecf0;border-radius:8px;padding:16px;font-family:'JetBrains Mono',monospace;font-size:13px;color:#475569;margin-top:20px;margin-bottom:24px;line-height:1.6;font-weight:500;">
            <span class="material-symbols-outlined" style="font-size:16px;color:#f59e0b;vertical-align:middle;margin-right:6px;">code</span>
            password_hash($new_pass, PASSWORD_BCRYPT, ['cost' =&gt; 12])<br>
            <span style="color:#64748b;display:block;margin-top:4px;">+ login_attempts reset ke 0, locked_until = NULL</span>
          </div>
          <div style="display:flex;gap:16px;padding-top:24px;border-top:1px solid #eaecf0;">
            <button type="submit" class="btn-warning" style="padding:12px 24px;">
              <span class="material-symbols-outlined" style="font-size:20px;">lock_reset</span> Reset Password
            </button>
            <a href="/simpekabjmk/pegawai.php" class="btn-ghost" style="padding:12px 24px;background:#ffffff;border:1px solid #eaecf0;">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
