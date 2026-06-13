<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();
requireRole(['super_admin', 'admin_bkpsdm']);

$currentPage = 'pegawai';
$pageTitle   = 'Import Pegawai (CSV)';

$errors = [];
$successCount = 0;
$failCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    
    if (!isset($_FILES['file_csv']) || $_FILES['file_csv']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Silakan unggah file CSV yang valid.';
    } else {
        $fileExt = strtolower(pathinfo($_FILES['file_csv']['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            $errors[] = 'Format file harus .csv';
        } else {
            $handle = fopen($_FILES['file_csv']['tmp_name'], 'r');
            if ($handle !== FALSE) {
                // Skip header row
                $header = fgetcsv($handle, 1000, ",");
                
                $rowNum = 1;
                $defaultPassword = password_hash('Simpeg123!', PASSWORD_BCRYPT, ['cost' => 12]);
                
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $rowNum++;
                    
                    // Skip empty rows
                    if (empty(array_filter($data))) continue;
                    
                    if (count($data) < 8) {
                        $failCount++;
                        $errors[] = "Baris $rowNum: Format kolom tidak lengkap (minimal 8 kolom wajib).";
                        continue;
                    }
                    
                    $nama       = trim($data[0]);
                    $email      = trim($data[1]);
                    $role       = strtolower(trim($data[2]));
                    $nip        = trim($data[3]);
                    $jenis_asn  = trim($data[4] ?? 'PNS');
                    $golongan   = trim($data[5] ?? '');
                    $divisi     = trim($data[6]);
                    $posisi     = trim($data[7]);
                    $pendidikan = trim($data[8] ?? '');
                    $no_telp    = trim($data[9] ?? '');
                    
                    if (!$nama || !$email || !$role || !$nip || !$divisi || !$posisi) {
                        $failCount++;
                        $errors[] = "Baris $rowNum ($nama): Kolom wajib (Nama/Email/Role/NIP/Divisi/Posisi) tidak boleh kosong.";
                        continue;
                    }
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $failCount++;
                        $errors[] = "Baris $rowNum ($nama): Format email ($email) tidak valid.";
                        continue;
                    }
                    if (!in_array($role, ['super_admin','eksekutif','admin_bkpsdm','atasan','pegawai'])) {
                        $failCount++;
                        $errors[] = "Baris $rowNum ($nama): Role ($role) tidak valid.";
                        continue;
                    }
                    
                    // Cek email unique
                    $stmtCek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmtCek->execute([$email]);
                    if ($stmtCek->fetch()) {
                        $failCount++;
                        $errors[] = "Baris $rowNum ($nama): Email ($email) sudah terdaftar.";
                        continue;
                    }
                    
                    // Cek NIP unique
                    $stmtCekNip = $pdo->prepare("SELECT id FROM pegawai WHERE nip = ?");
                    $stmtCekNip->execute([$nip]);
                    if ($stmtCekNip->fetch()) {
                        $failCount++;
                        $errors[] = "Baris $rowNum ($nama): NIP ($nip) sudah digunakan.";
                        continue;
                    }
                    
                    // Insert
                    try {
                        $pdo->beginTransaction();
                        
                        $stmtUser = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
                        $stmtUser->execute([$nama, $email, $defaultPassword, $role]);
                        $newUserId = (int) $pdo->lastInsertId();
                        
                        $stmtPegawai = $pdo->prepare("INSERT INTO pegawai (user_id, nip, jenis_asn, golongan, divisi, posisi, pendidikan, no_telp, tgl_masuk, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'aktif')");
                        $stmtPegawai->execute([$newUserId, $nip, $jenis_asn, $golongan, $divisi, $posisi, $pendidikan, $no_telp]);
                        
                        $pdo->commit();
                        $successCount++;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $failCount++;
                        $errors[] = "Baris $rowNum ($nama): Gagal menyimpan ke database.";
                    }
                }
                fclose($handle);
                
                if ($successCount > 0) {
                    logActivity(currentUser()['id'], 'PEGAWAI_IMPORT', "Import $successCount pegawai via CSV", 'info');
                    setFlash('success', "Berhasil mengimpor $successCount pegawai baru. Password default: Simpeg123!");
                }
                
                if ($failCount === 0 && $successCount > 0) {
                    redirect('/simpekabjmk/pegawai.php');
                }
            } else {
                $errors[] = "Gagal membaca file CSV.";
            }
        }
    }
}

$csrfToken = generateCsrfToken();
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
          <h1 class="section-title">Import Pegawai (CSV)</h1>
          <p class="section-subtitle">Unggah data pegawai secara massal. Password default: <strong style="color:#1a1d1f;">Simpeg123!</strong></p>
        </div>
        <a href="/simpekabjmk/pegawai.php" class="btn-ghost" style="background:#ffffff;border:1px solid #eaecf0;box-shadow:0 2px 5px rgba(0,0,0,0.02);">
          <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span>
          Kembali
        </a>
      </div>

      <?php if ($successCount > 0): ?>
      <div class="alert alert-success" style="margin-bottom:24px;">
        <span class="material-symbols-outlined" style="font-size:20px;flex-shrink:0;">check_circle</span>
        <div><?= $successCount ?> data pegawai berhasil diimpor dan akun login telah aktif!</div>
      </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
      <div class="alert alert-error" style="margin-bottom:24px;">
        <span class="material-symbols-outlined" style="font-size:20px;flex-shrink:0;">error</span>
        <div>
          <div style="font-weight:700;margin-bottom:8px;">Terdapat <?= $failCount ?> data yang gagal diimpor:</div>
          <ul style="list-style:disc;padding-left:20px;max-height:200px;overflow-y:auto;">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endif; ?>

      <div style="display:grid;gap:24px;grid-template-columns:1fr;@media(min-width:1024px){grid-template-columns:2fr 1fr;}">
        <!-- Form Utama -->
        <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
          <h2 style="font-size:18px;font-weight:700;color:#1a1d1f;margin-bottom:24px;display:flex;align-items:center;gap:10px;padding-bottom:16px;border-bottom:1px solid #eaecf0;">
            <span class="material-symbols-outlined" style="color:#8b5cf6;background:#f5f3ff;padding:8px;border-radius:12px;">upload_file</span>
            Unggah File CSV
          </h2>

          <form method="POST" enctype="multipart/form-data">
            <?= csrfInput() ?>
            <div style="margin-bottom:24px;">
              <label class="label">Pilih File CSV *</label>
              <div style="border:2px dashed #cbd5e1;border-radius:12px;padding:40px 20px;text-align:center;background:#f8fafc;transition:all 0.2s;cursor:pointer;" onmouseover="this.style.borderColor='#3b82f6';this.style.background='#eff6ff'" onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc'" onclick="document.getElementById('file_csv').click()">
                <span class="material-symbols-outlined" style="font-size:40px;color:#94a3b8;margin-bottom:12px;">cloud_upload</span>
                <div style="color:#1a1d1f;font-weight:600;font-size:15px;margin-bottom:4px;">Klik untuk memilih file</div>
                <div style="color:#64748b;font-size:13px;margin-bottom:16px;">Maksimal ukuran file: 2MB</div>
                <input type="file" id="file_csv" name="file_csv" accept=".csv" required style="display:none;" onchange="document.getElementById('file-name').textContent = this.files[0] ? this.files[0].name : ''"/>
                <div id="file-name" style="font-weight:700;color:#3b82f6;font-size:14px;"></div>
              </div>
            </div>

            <div style="display:flex;gap:16px;border-top:1px solid #eaecf0;padding-top:24px;">
              <button type="submit" class="btn-primary" style="padding:12px 24px;">
                <span class="material-symbols-outlined" style="font-size:20px;">publish</span>
                Mulai Import
              </button>
            </div>
          </form>
        </div>

        <!-- Panduan -->
        <div style="display:flex;flex-direction:column;gap:24px;">
          <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
            <h3 style="font-size:16px;font-weight:700;color:#1a1d1f;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
              <span class="material-symbols-outlined" style="color:#3b82f6;background:#eff6ff;padding:8px;border-radius:12px;">help</span>
              Panduan Import
            </h3>
            <p style="color:#64748b;font-size:13px;line-height:1.6;margin-bottom:16px;">
              Gunakan template CSV standar agar proses import berjalan lancar. Baris pertama (header) akan diabaikan oleh sistem secara otomatis.
            </p>
            <a href="/simpekabjmk/pegawai_import_template.php" class="btn-ghost" style="width:100%;justify-content:center;border:1px solid #cbd5e1;background:#ffffff;color:#1a1d1f;">
              <span class="material-symbols-outlined" style="font-size:18px;">download</span>
              Unduh Template CSV
            </a>
          </div>

          <div class="card" style="border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);background:#fffbeb;">
            <h3 style="font-size:14px;font-weight:700;color:#b45309;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
              <span class="material-symbols-outlined" style="font-size:18px;">warning</span>
              Perhatian
            </h3>
            <ul style="list-style:disc;padding-left:16px;color:#d97706;font-size:13px;display:flex;flex-direction:column;gap:8px;line-height:1.5;">
              <li>Password akun baru otomatis diset: <strong>Simpeg123!</strong></li>
              <li>Kolom Email dan NIP harus <strong>unik</strong> dan tidak boleh sama dengan yang sudah ada.</li>
              <li>Penulisan Role yang valid: <br><code style="background:rgba(255,255,255,0.5);padding:2px 4px;border-radius:4px;">pegawai</code>, <code style="background:rgba(255,255,255,0.5);padding:2px 4px;border-radius:4px;">atasan</code>, <code style="background:rgba(255,255,255,0.5);padding:2px 4px;border-radius:4px;">admin_bkpsdm</code></li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
