<?php
// ============================================================
// login.php — Halaman Login SIMPEKAB JOMOKERTO
// Implementasi: Materi Pertemuan 9 — Bagian 2.5 Sistem Autentikasi
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/helpers/auth_guard.php';

// Konfigurasi session AMAN (wajib sebelum session_start)
require_once __DIR__ . '/config/session.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    redirect('/simpekabjmk/dashboard.php');
}

// Cek remember me cookie — auto login jika ada
checkRememberMe();
if (isLoggedIn()) {
    redirect('/simpekabjmk/dashboard.php');
}

$errors  = [];
$email   = ''; // Untuk sticky form
$timeout = isset($_GET['timeout']);

// ============================================================
// PROSES FORM LOGIN (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Validasi CSRF Token
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postToken    = $_POST['csrf_token']    ?? '';
    if (!hash_equals($sessionToken, $postToken)) {
        logActivity(null, 'CSRF_VIOLATION', 'CSRF mismatch di login.php', 'critical');
        die('⚠ CSRF token tidak valid. Silakan muat ulang halaman.');
    }
    // Regenerate CSRF setelah validasi
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // 2. Sanitasi & Validasi Input
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email)) {
        $errors[] = 'Email wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }
    if (empty($password)) {
        $errors[] = 'Password wajib diisi';
    }

    // 3. Proses autentikasi jika input valid
    if (empty($errors)) {

        // Query user berdasarkan email (Prepared Statement!)
        $stmt = $pdo->prepare("
            SELECT id, nama, email, password, role,
                   login_attempts, locked_until
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 4. Cek apakah akun terkunci (Rate Limiting)
        if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $sisaMenit = ceil((strtotime($user['locked_until']) - time()) / 60);
            $errors[] = "Akun terkunci sementara. Coba lagi dalam {$sisaMenit} menit.";
            logActivity($user['id'], 'LOGIN_BLOCKED', 'Akun terkunci', 'warning');
        }

        // 5. Verifikasi password dengan password_verify() (Bcrypt)
        if (empty($errors)) {
            if ($user && password_verify($password, $user['password'])) {

                // ✅ LOGIN SUKSES!

                // Cegah Session Fixation — WAJIB!
                session_regenerate_id(true);

                // Set data session
                $_SESSION['user_id']           = $user['id'];
                $_SESSION['nama']              = $user['nama'];
                $_SESSION['email']             = $user['email'];
                $_SESSION['role']              = $user['role'];
                $_SESSION['login_time']        = time();
                $_SESSION['ip_address']        = getClientIp();
                $_SESSION['user_agent']        = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $_SESSION['last_regeneration'] = time();

                // Reset login attempts
                $pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?")
                    ->execute([$user['id']]);

                // Cek apakah hash perlu di-upgrade (cost factor meningkat)
                if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
                    $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                        ->execute([$newHash, $user['id']]);
                }

                // Remember Me feature
                if ($remember) {
                    setRememberMeToken($user['id']);
                }

                // Log aktivitas
                logActivity($user['id'], 'LOGIN_SUCCESS', "Login berhasil sebagai {$user['role']}", 'info');

                // Flash welcome message
                setFlash('success', "Selamat datang, " . explode(',', $user['nama'])[0] . "!");

                // Redirect ke halaman sebelumnya atau dashboard
                $redirectTo = $_SESSION['redirect_after_login'] ?? '/simpekabjmk/dashboard.php';
                unset($_SESSION['redirect_after_login']);
                redirect($redirectTo);

            } else {
                // ❌ LOGIN GAGAL

                // ⚠️ PENTING: Pesan error GENERIK!
                // Tidak boleh bedakan "email tidak terdaftar" vs "password salah"
                $errors[] = 'Email atau password salah';

                // Increment login attempts + lockout jika >= 5 kali
                if ($user) {
                    $newAttempts = $user['login_attempts'] + 1;
                    $lockUntil   = null;

                    if ($newAttempts >= 5) {
                        // Kunci akun selama 15 menit
                        $lockUntil = date('Y-m-d H:i:s', time() + (15 * 60));
                        $errors    = ['Akun dikunci 15 menit karena terlalu banyak percobaan login gagal.'];
                        logActivity($user['id'], 'ACCOUNT_LOCKED', "Login gagal {$newAttempts}x — dikunci 15 menit", 'critical');
                    } else {
                        logActivity($user['id'], 'LOGIN_FAILED', "Login gagal — percobaan ke-{$newAttempts}", 'warning');
                    }

                    $pdo->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?")
                        ->execute([$newAttempts, $lockUntil, $user['id']]);

                    // Sisa percobaan
                    if ($newAttempts < 5) {
                        $sisa = 5 - $newAttempts;
                        $errors[] = "Sisa percobaan: {$sisa} kali sebelum akun dikunci.";
                    }
                } else {
                    logActivity(null, 'LOGIN_FAILED_UNKNOWN', "Email tidak ditemukan: {$email}", 'warning');
                }

                // Delay artifisial untuk menyulitkan brute force (1 detik)
                sleep(1);
            }
        }
    }
}

// Generate CSRF token untuk form
$csrfToken = generateCsrfToken();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;position:relative;overflow:hidden;background:#f8fafc;">
  <!-- Decorative background elements -->
  <div style="position:absolute;top:-10%;left:-5%;width:500px;height:500px;background:radial-gradient(circle, rgba(255,184,0,0.05) 0%, transparent 70%);border-radius:50%;pointer-events:none;"></div>
  <div style="position:absolute;bottom:-10%;right:-5%;width:600px;height:600px;background:radial-gradient(circle, rgba(16,185,129,0.03) 0%, transparent 70%);border-radius:50%;pointer-events:none;"></div>

  <div style="width:100%;max-width:440px;position:relative;z-index:10;">
    <!-- Brand Header -->
    <div style="text-align:center;margin-bottom:32px;">
      <div style="display:inline-flex;align-items:center;justify-content:center;width:80px;height:80px;margin-bottom:16px;">
        <img src="/simpekabjmk/assets/logo_jomokerto.png" alt="Logo" style="width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 4px 12px rgba(0,0,0,0.05));" />
      </div>
      <h1 class="section-title" style="font-size:32px;margin-bottom:8px;">SIMPEKAB JOMOKERTO</h1>
      <p style="color:#64748b;font-weight:500;font-size:14px;letter-spacing:0.02em;">Sistem Informasi Manajemen Kepegawaian Kabupaten Jomokerto</p>
    </div>

    <!-- Login Card -->
    <div class="card" style="padding:40px 32px;">
      <!-- Timeout alert -->
      <?php if ($timeout): ?>
      <div class="alert alert-warning" style="margin-bottom:24px;">
        <span class="material-symbols-outlined" style="font-size:20px;">timer_off</span>
        Sesi Anda telah habis. Silakan login kembali.
      </div>
      <?php endif; ?>

      <!-- Error alerts -->
      <?php if (!empty($errors)): ?>
      <div class="alert alert-error" style="margin-bottom:24px;">
        <span class="material-symbols-outlined" style="font-size:20px;">error</span>
        <div>
          <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <form method="POST" action="/simpekabjmk/login.php" id="login-form" novalidate>
        <?= csrfInput() ?>

        <div style="margin-bottom:20px;">
          <label class="label" for="email">Email</label>
          <div style="position:relative;">
            <span class="material-symbols-outlined" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:20px;">mail</span>
            <input class="input-field" type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="nama@simpeg.test" required autocomplete="email" autofocus style="padding-left:44px;"/>
          </div>
        </div>

        <div style="margin-bottom:24px;">
          <label class="label" for="password">Kata Sandi</label>
          <div style="position:relative;">
            <span class="material-symbols-outlined" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:20px;">lock</span>
            <input class="input-field" type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password" style="padding-left:44px;padding-right:44px;"/>
            <button type="button" onclick="togglePassword()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;display:flex;">
              <span class="material-symbols-outlined" id="pass-icon" style="font-size:20px;">visibility</span>
            </button>
          </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;user-select:none;">
            <input type="checkbox" name="remember" id="remember" style="width:16px;height:16px;border-radius:4px;border:1px solid #cbd5e1;accent-color:#ffb800;"/>
            <span style="color:#64748b;font-size:13px;font-weight:500;">Ingat Saya</span>
          </label>
          <a href="/simpekabjmk/lupa_password.php" style="color:#ffb800;font-size:13px;font-weight:600;text-decoration:none;">Lupa Sandi?</a>
        </div>

        <button type="submit" id="login-btn" class="btn-primary" style="width:100%;padding:14px;">
          <span id="btn-text">Masuk Sekarang</span>
          <span class="material-symbols-outlined" style="font-size:20px;">arrow_forward</span>
        </button>
      </form>


      
      <!-- Security Badges & Footer -->
      <div style="margin-top:24px;display:flex;flex-direction:column;align-items:center;gap:12px;">
        <div style="display:flex;justify-content:center;gap:16px;">
          <div style="display:flex;align-items:center;gap:4px;color:#94a3b8;font-size:11px;font-weight:500;"><span class="material-symbols-outlined" style="font-size:14px;color:#10b981;">verified_user</span> CSRF Aman</div>
          <div style="display:flex;align-items:center;gap:4px;color:#94a3b8;font-size:11px;font-weight:500;"><span class="material-symbols-outlined" style="font-size:14px;color:#10b981;">key</span> Bcrypt Hash</div>
        </div>
        <div style="color:#94a3b8;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Pemerintah Kabupaten Jomokerto</div>
      </div>
    </div>
  </div>
</div>

<script>
  function togglePassword() {
    const inp = document.getElementById('password');
    const icon = document.getElementById('pass-icon');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    icon.textContent = inp.type === 'password' ? 'visibility' : 'visibility_off';
  }
  document.getElementById('login-form').addEventListener('submit', function() {
    const btn = document.getElementById('login-btn');
    btn.disabled = true;
    document.getElementById('btn-text').textContent = 'Memverifikasi...';
  });
</script>
</body>
</html>
