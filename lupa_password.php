<?php
// ============================================================
// lupa_password.php
// Halaman bagi pengguna untuk meminta reset password ke Admin
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/functions.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /simpeg_mini/dashboard.php');
    exit;
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();

    $email = trim($_POST['email'] ?? '');
    $jenis_layanan = mb_substr(trim($_POST['jenis_layanan'] ?? 'Lupa Sandi'), 0, 100);

    if (empty($email)) {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    } else {
        // Cek apakah email terdaftar di tabel users
        $stmt = $pdo->prepare("SELECT id, nama FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Cek apakah sudah ada request pending untuk email ini agar tidak spam
            $stmtCek = $pdo->prepare("SELECT id FROM password_reset_requests WHERE email = ? AND status = 'pending'");
            $stmtCek->execute([$email]);
            if ($stmtCek->fetch()) {
                $errors[] = 'Permintaan reset untuk email ini sedang diproses oleh admin. Harap menunggu.';
            } else {
                // Simpan request ke database
                $stmtInsert = $pdo->prepare("INSERT INTO password_reset_requests (email, jenis_layanan) VALUES (?, ?)");
                $stmtInsert->execute([$email, $jenis_layanan]);
                $successMessage = 'Permintaan (' . e($jenis_layanan) . ') berhasil dikirim ke Admin. Silakan hubungi Admin atau tunggu konfirmasi lebih lanjut.';
            }
        } else {
            // Sesuai standar keamanan, kita tidak boleh memberitahu apakah email terdaftar atau tidak secara spesifik
            // Tapi untuk internal simulasi ini, kita tampilkan error saja agar user tahu.
            $errors[] = 'Email tidak ditemukan dalam sistem kami.';
        }
    }
}

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
        <img src="/simpeg_mini/assets/logo_jomokerto.png" alt="Logo" style="width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 4px 12px rgba(0,0,0,0.05));" />
      </div>
      <h1 class="section-title" style="font-size:32px;margin-bottom:8px;">Lupa Sandi</h1>
      <p style="color:#64748b;font-weight:500;font-size:14px;letter-spacing:0.02em;">Minta akses ulang ke akun Anda</p>
    </div>

    <!-- Request Card -->
    <div class="card" style="padding:40px 32px;">
      
      <?php if ($successMessage): ?>
      <div class="alert alert-success" style="margin-bottom:24px;">
        <span class="material-symbols-outlined" style="font-size:20px;">check_circle</span>
        <div><?= e($successMessage) ?></div>
      </div>
      <div style="text-align:center;">
        <a href="/simpeg_mini/login.php" class="btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;text-decoration:none;">
          <span class="material-symbols-outlined" style="font-size:20px;">arrow_back</span>
          Kembali ke Login
        </a>
      </div>
      <?php else: ?>

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

      <form method="POST" action="/simpeg_mini/lupa_password.php" novalidate>
        <?= csrfInput() ?>

        <div style="margin-bottom:20px;">
          <label class="label" for="email">Email Pegawai</label>
          <div style="position:relative;">
            <span class="material-symbols-outlined" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:20px;">mail</span>
            <input class="input-field" type="email" id="email" name="email" placeholder="pegawai@simpeg.test" required autofocus style="padding-left:44px;"/>
          </div>
        </div>

        <div style="margin-bottom:24px;">
          <label class="label" for="jenis_layanan">Jenis Layanan</label>
          <div style="position:relative;">
            <span class="material-symbols-outlined" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:20px;">support_agent</span>
            <select class="input-field" id="jenis_layanan" name="jenis_layanan" required style="padding-left:44px;appearance:none;background-image:url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%2394a3b8%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E');background-repeat:no-repeat;background-position:right%2012px%20center;background-size:16px;">
              <option value="Lupa Sandi">Lupa Sandi / Reset Password</option>
              <option value="Permintaan Akses">Permintaan Akses / Role</option>
              <option value="Bantuan Teknis">Bantuan Teknis</option>
              <option value="Laporan Error">Laporan Error Aplikasi</option>
            </select>
          </div>
          <p style="color:#64748b;font-size:12px;margin-top:8px;">Permintaan Anda akan diteruskan ke tim Admin/IT untuk segera diproses.</p>
        </div>

        <button type="submit" class="btn-primary" style="width:100%;padding:14px;margin-bottom:20px;">
          <span class="material-symbols-outlined" style="font-size:20px;">send</span>
          Kirim Permintaan
        </button>

        <div style="text-align:center;">
          <a href="/simpeg_mini/login.php" style="color:#64748b;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
            <span class="material-symbols-outlined" style="font-size:16px;">arrow_back</span>
            Kembali ke Login
          </a>
        </div>
      </form>
      <?php endif; ?>

    </div>
    
    <!-- Footer -->
    <div style="margin-top:24px;text-align:center;color:#94a3b8;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">
      Pemerintah Kabupaten Jomokerto
    </div>
  </div>
</div>
</body>
</html>
