<?php
// ============================================================
// pegawai_tambah.php — Form Tambah Pegawai Baru
// Admin Only | Bcrypt password_hash
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireRole(['admin']);

$currentPage = 'pegawai';
$pageTitle   = 'Tambah Pegawai';
$errors      = [];
$old         = []; // Untuk sticky form

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();

    // Ambil & sanitasi input
    $old = [
        'nama'      => trim($_POST['nama']     ?? ''),
        'email'     => trim($_POST['email']    ?? ''),
        'role'      => $_POST['role']          ?? 'karyawan',
        'nip'       => trim($_POST['nip']      ?? ''),
        'divisi'    => trim($_POST['divisi']   ?? ''),
        'posisi'    => trim($_POST['posisi']   ?? ''),
        'no_telp'   => trim($_POST['no_telp']  ?? ''),
        'tgl_masuk' => $_POST['tgl_masuk']     ?? date('Y-m-d'),
        'status'    => $_POST['status']        ?? 'aktif',
    ];
    $password    = $_POST['password']  ?? '';
    $password2   = $_POST['password2'] ?? '';

    // Validasi
    if (isEmpty($old['nama']))     $errors[] = 'Nama wajib diisi';
    if (isEmpty($old['email']))    $errors[] = 'Email wajib diisi';
    elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid';
    if (isEmpty($old['nip']))      $errors[] = 'NIP wajib diisi';
    if (isEmpty($old['divisi']))   $errors[] = 'Divisi wajib diisi';
    if (isEmpty($old['posisi']))   $errors[] = 'Posisi wajib diisi';
    if (isEmpty($password))        $errors[] = 'Password wajib diisi';
    elseif (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter';
    if ($password !== $password2)  $errors[] = 'Konfirmasi password tidak cocok';
    if (!in_array($old['role'], ['admin','manager','karyawan'])) $errors[] = 'Role tidak valid';

    // Cek email unik
    if (empty($errors)) {
        $stmtCek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmtCek->execute([$old['email']]);
        if ($stmtCek->fetch()) $errors[] = 'Email sudah terdaftar';
    }

    // Cek NIP unik
    if (empty($errors)) {
        $stmtCek = $pdo->prepare("SELECT id FROM pegawai WHERE nip = ?");
        $stmtCek->execute([$old['nip']]);
        if ($stmtCek->fetch()) $errors[] = 'NIP sudah digunakan';
    }

    // Simpan ke DB jika tidak ada error
    if (empty($errors)) {
        // Hash password dengan Bcrypt cost=12
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $pdo->beginTransaction();

            // Insert ke tabel users
            $stmtUser = $pdo->prepare("
                INSERT INTO users (nama, email, password, role)
                VALUES (?, ?, ?, ?)
            ");
            $stmtUser->execute([$old['nama'], $old['email'], $hashedPassword, $old['role']]);
            $newUserId = (int) $pdo->lastInsertId();

            // Insert ke tabel pegawai
            $stmtPegawai = $pdo->prepare("
                INSERT INTO pegawai (user_id, nip, divisi, posisi, no_telp, tgl_masuk, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtPegawai->execute([
                $newUserId,
                $old['nip'],
                $old['divisi'],
                $old['posisi'],
                $old['no_telp'] ?: null,
                $old['tgl_masuk'],
                $old['status'],
            ]);

            $pdo->commit();

            logActivity(currentUser()['id'], 'PEGAWAI_TAMBAH', "Menambahkan pegawai: {$old['email']} sebagai {$old['role']}", 'info');
            setFlash('success', "Pegawai {$old['nama']} berhasil ditambahkan! Password di-hash dengan Bcrypt cost=12.");
            redirect('/simpeg_mini/pegawai.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            error_log('[SIMPEG Tambah Pegawai] ' . $e->getMessage());
        }
    }
}

$csrfToken = generateCsrfToken();
$daftarDivisi = ['IT & Development','HRD & Administrasi','Finance & Akuntansi','Operations','Marketing','Legal'];
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      <?= renderFlash() ?>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;">
        <div>
          <h1 class="section-title">Tambah Pegawai</h1>
          <p class="section-subtitle">Password akan di-hash menggunakan Bcrypt cost=12</p>
        </div>
        <a href="/simpeg_mini/pegawai.php" class="btn-ghost" style="background:#ffffff;border:1px solid #eaecf0;box-shadow:0 2px 5px rgba(0,0,0,0.02);">
          <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span>
          Kembali
        </a>
      </div>

      <?php if (!empty($errors)): ?>
      <div class="alert alert-error" style="margin-bottom:24px;">
        <span class="material-symbols-outlined" style="font-size:20px;flex-shrink:0;">error</span>
        <div><?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?></div>
      </div>
      <?php endif; ?>

      <div style="display:grid;gap:24px;grid-template-columns:1fr;@media(min-width:1024px){grid-template-columns:2fr 1fr;}">

        <!-- Form Utama -->
        <form method="POST" class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <?= csrfInput() ?>
          <h2 style="font-size:18px;font-weight:700;color:#1a1d1f;margin-bottom:24px;display:flex;align-items:center;gap:10px;padding-bottom:16px;border-bottom:1px solid #eaecf0;">
            <span class="material-symbols-outlined" style="color:#0ea5e9;background:#e0f2fe;padding:8px;border-radius:12px;">person_add</span>
            Data Pegawai
          </h2>

          <div class="form-row cols-2">
            <div class="form-group">
              <label class="label">Nama Lengkap *</label>
              <input type="text" name="nama" value="<?= e($old['nama'] ?? '') ?>" placeholder="Budi Santoso, S.Kom." class="input-card" required/>
            </div>
            <div class="form-group">
              <label class="label">Email *</label>
              <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="budi@simpeg.test" class="input-card" required/>
            </div>
            <div class="form-group">
              <label class="label">NIP *</label>
              <input type="text" name="nip" value="<?= e($old['nip'] ?? '') ?>" placeholder="NIP2026013" class="input-card" required/>
            </div>
            <div class="form-group">
              <label class="label">No. Telepon</label>
              <input type="text" name="no_telp" value="<?= e($old['no_telp'] ?? '') ?>" placeholder="08123456789" class="input-card"/>
            </div>
            <div class="form-group">
              <label class="label">Role *</label>
              <select name="role" class="input-card" required>
                <?php foreach (['karyawan','manager','admin'] as $r): ?>
                <option value="<?= $r ?>" <?= ($old['role']??'karyawan')===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="label">Divisi *</label>
              <select name="divisi" class="input-card" required>
                <option value="">— Pilih Divisi —</option>
                <?php foreach ($daftarDivisi as $div): ?>
                <option value="<?= e($div) ?>" <?= ($old['divisi']??'')===$div?'selected':'' ?>><?= e($div) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="label">Posisi/Jabatan *</label>
              <input type="text" name="posisi" value="<?= e($old['posisi'] ?? '') ?>" placeholder="Developer Backend" class="input-card" required/>
            </div>
            <div class="form-group">
              <label class="label">Tanggal Masuk *</label>
              <input type="date" name="tgl_masuk" value="<?= e($old['tgl_masuk'] ?? date('Y-m-d')) ?>" class="input-card" required/>
            </div>
          </div>

          <!-- Password Section -->
          <div style="border-top:1px solid #eaecf0;padding-top:24px;margin-top:16px;">
            <h3 style="font-size:16px;font-weight:700;color:#1a1d1f;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
              <span class="material-symbols-outlined" style="color:#f59e0b;background:#fffbeb;padding:8px;border-radius:12px;">key</span>
              Password Awal
              <span style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:500;">(Bcrypt cost=12)</span>
            </h3>
            <div class="form-row cols-2">
              <div class="form-group">
                <label class="label">Password *</label>
                <input type="password" name="password" id="password-new" placeholder="Min. 8 karakter" class="input-card" required/>
                <div class="progress-bar" style="margin-top:8px;height:6px;"><div id="pass-strength-bar" class="progress-fill" style="width:0%;background:#ef4444;"></div></div>
                <p id="pass-strength-label" style="color:#64748b;font-size:12px;font-weight:500;margin-top:6px;"></p>
              </div>
              <div class="form-group">
                <label class="label">Konfirmasi Password *</label>
                <input type="password" name="password2" placeholder="Ulangi password" class="input-card" required/>
              </div>
            </div>
            <div style="background:#f8fafc;border:1px solid #eaecf0;border-radius:8px;padding:16px;font-family:'JetBrains Mono',monospace;font-size:13px;color:#475569;margin-top:8px;font-weight:500;">
              <span class="material-symbols-outlined" style="font-size:16px;color:#3b82f6;vertical-align:middle;margin-right:4px;">code</span>
              password_hash($pass, PASSWORD_BCRYPT, ['cost' =&gt; 12])
            </div>
          </div>

          <div style="display:flex;gap:16px;margin-top:32px;padding-top:24px;border-top:1px solid #eaecf0;">
            <button type="submit" class="btn-primary" style="padding:12px 24px;">
              <span class="material-symbols-outlined" style="font-size:20px;">save</span>
              Simpan Pegawai
            </button>
            <a href="/simpeg_mini/pegawai.php" class="btn-ghost" style="padding:12px 24px;background:#ffffff;border:1px solid #eaecf0;">Batal</a>
          </div>
        </form>

        <!-- Info Panel -->
        <div style="display:flex;flex-direction:column;gap:24px;">
          <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
            <h3 style="font-size:16px;font-weight:700;color:#1a1d1f;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
              <span class="material-symbols-outlined" style="color:#3b82f6;background:#eff6ff;padding:8px;border-radius:12px;">info</span>
              Panduan
            </h3>
            <div style="display:flex;flex-direction:column;gap:16px;font-size:14px;color:#475569;font-weight:500;">
              <div style="display:flex;align-items:start;gap:10px;"><span class="material-symbols-outlined" style="color:#10b981;background:#f0fdf4;border-radius:50%;padding:4px;font-size:16px;flex-shrink:0;">check</span><span style="padding-top:2px;">NIP harus unik di seluruh sistem</span></div>
              <div style="display:flex;align-items:start;gap:10px;"><span class="material-symbols-outlined" style="color:#10b981;background:#f0fdf4;border-radius:50%;padding:4px;font-size:16px;flex-shrink:0;">check</span><span style="padding-top:2px;">Password di-hash Bcrypt sebelum disimpan ke DB</span></div>
              <div style="display:flex;align-items:start;gap:10px;"><span class="material-symbols-outlined" style="color:#10b981;background:#f0fdf4;border-radius:50%;padding:4px;font-size:16px;flex-shrink:0;">check</span><span style="padding-top:2px;">Email digunakan untuk login</span></div>
              <div style="display:flex;align-items:start;gap:10px;"><span class="material-symbols-outlined" style="color:#f59e0b;background:#fffbeb;border-radius:50%;padding:4px;font-size:16px;flex-shrink:0;">warning</span><span style="padding-top:2px;">Admin: akses penuh. Manager: lihat tim. Karyawan: akses terbatas.</span></div>
            </div>
          </div>
          <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
            <h3 style="font-size:16px;font-weight:700;color:#1a1d1f;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
              <span class="material-symbols-outlined" style="color:#8b5cf6;background:#f5f3ff;padding:8px;border-radius:12px;">policy</span>
              RBAC
            </h3>
            <?php foreach ([['admin','#ef4444','Full akses sistem'],['manager','#f59e0b','Lihat absensi tim'],['karyawan','#0ea5e9','Absensi pribadi saja']] as [$r,$c,$desc]): ?>
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;">
              <?= roleBadge($r) ?>
              <span style="color:#64748b;font-size:13px;font-weight:500;"><?= $desc ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
