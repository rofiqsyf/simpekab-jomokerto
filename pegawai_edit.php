<?php
// ============================================================
// pegawai_edit.php — Edit Data Pegawai (Admin Only)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireRole(['super_admin', 'admin_bkpsdm']);

$currentPage = 'pegawai';
$pageTitle   = 'Edit Pegawai';
$errors      = [];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('/simpekabjmk/pegawai.php'); }

// Fetch data pegawai yang akan diedit
$stmt = $pdo->prepare("
    SELECT u.id, u.nama, u.email, u.role,
           p.nip, p.nik, p.npwp, p.divisi, p.posisi, p.golongan, p.pendidikan, p.jenis_asn, p.no_telp, p.tgl_masuk, p.status
    FROM users u
    LEFT JOIN pegawai p ON p.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$id]);
$pegawai = $stmt->fetch();
if (!$pegawai) { setFlash('error','Pegawai tidak ditemukan.'); redirect('/simpekabjmk/pegawai.php'); }

$old = $pegawai; // Default form values dari DB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();

    $old = [
        'nama'      => trim($_POST['nama']     ?? ''),
        'email'     => trim($_POST['email']    ?? ''),
        'role'      => $_POST['role']          ?? 'pegawai',
        'nip'       => trim($_POST['nip']      ?? ''),
        'divisi'    => trim($_POST['divisi']   ?? ''),
        'posisi'    => trim($_POST['posisi']   ?? ''),
        'golongan'  => trim($_POST['golongan'] ?? ''),
        'pendidikan'=> trim($_POST['pendidikan']?? ''),
        'jenis_asn' => trim($_POST['jenis_asn']?? 'PNS'),
        'nik'       => trim($_POST['nik']      ?? ''),
        'npwp'      => trim($_POST['npwp']     ?? ''),
        'no_telp'   => trim($_POST['no_telp']  ?? ''),
        'tgl_masuk' => $_POST['tgl_masuk']     ?? '',
        'status'    => $_POST['status']        ?? 'aktif',
    ];

    // Validasi
    if (isEmpty($old['nama']))  $errors[] = 'Nama wajib diisi';
    if (isEmpty($old['email'])) $errors[] = 'Email wajib diisi';
    elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid';
    if (isEmpty($old['nip']))   $errors[] = 'NIP wajib diisi';
    if (!in_array($old['role'], ['super_admin','eksekutif','admin_bkpsdm','atasan','pegawai'])) $errors[] = 'Role tidak valid';
    // Jangan boleh ubah role sendiri
    if ($id === (int)currentUser()['id'] && $old['role'] !== currentUser()['role']) {
        $errors[] = 'Anda tidak bisa mengubah role akun sendiri';
    }

    // Cek email unik (exclude diri sendiri)
    if (empty($errors)) {
        $s = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $s->execute([$old['email'], $id]);
        if ($s->fetch()) $errors[] = 'Email sudah digunakan oleh pegawai lain';
    }

    // Cek NIP unik (exclude diri sendiri)
    if (empty($errors)) {
        $s = $pdo->prepare("SELECT id FROM pegawai WHERE nip = ? AND user_id != ?");
        $s->execute([$old['nip'], $id]);
        if ($s->fetch()) $errors[] = 'NIP sudah digunakan oleh pegawai lain';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $pdo->prepare("UPDATE users SET nama=?, email=?, role=? WHERE id=?")
                ->execute([$old['nama'], $old['email'], $old['role'], $id]);

            // Cek apakah sudah ada data pegawai
            $cek = $pdo->prepare("SELECT id FROM pegawai WHERE user_id=?");
            $cek->execute([$id]);

            if ($cek->fetch()) {
                $pdo->prepare("UPDATE pegawai SET nip=?,nik=?,npwp=?,divisi=?,posisi=?,golongan=?,pendidikan=?,jenis_asn=?,no_telp=?,tgl_masuk=?,status=? WHERE user_id=?")
                    ->execute([$old['nip'],$old['nik']?:null,$old['npwp']?:null,$old['divisi'],$old['posisi'],$old['golongan']?:null,$old['pendidikan']?:null,$old['jenis_asn'],$old['no_telp']?:null,$old['tgl_masuk'],$old['status'],$id]);
            } else {
                $pdo->prepare("INSERT INTO pegawai (user_id,nip,nik,npwp,divisi,posisi,golongan,pendidikan,jenis_asn,no_telp,tgl_masuk,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$id,$old['nip'],$old['nik']?:null,$old['npwp']?:null,$old['divisi'],$old['posisi'],$old['golongan']?:null,$old['pendidikan']?:null,$old['jenis_asn'],$old['no_telp']?:null,$old['tgl_masuk'],$old['status']]);
            }

            $pdo->commit();
            logActivity(currentUser()['id'], 'PEGAWAI_EDIT', "Edit pegawai ID={$id}: {$old['email']} ({$old['role']})", 'info');
            setFlash('success', "Data {$old['nama']} berhasil diperbarui.");
            redirect('/simpekabjmk/pegawai.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan perubahan.';
            error_log('[SIMPEG Edit Pegawai] ' . $e->getMessage());
        }
    }
}

$daftarDivisi = ['IT & Development','HRD & Administrasi','Finance & Akuntansi','Operations','Marketing','Legal'];
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
        <div>
          <h1 class="section-title">Edit Pegawai</h1>
          <p class="section-subtitle">ID: <?= e($id) ?> — <?= e($pegawai['email']) ?></p>
        </div>
        <a href="/simpekabjmk/pegawai.php" class="btn-ghost" style="background:#ffffff;border:1px solid #eaecf0;box-shadow:0 2px 5px rgba(0,0,0,0.02);">
          <span class="material-symbols-outlined">arrow_back</span> Kembali
        </a>
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
          <span class="material-symbols-outlined" style="color:#0ea5e9;background:#e0f2fe;padding:8px;border-radius:12px;">edit</span>
          Data Profil Pegawai
        </h2>
        <div class="form-row cols-2">
          <div class="form-group">
            <label class="label">Nama Lengkap *</label>
            <input type="text" name="nama" value="<?= e($old['nama']) ?>" class="input-card" required/>
          </div>
          <div class="form-group">
            <label class="label">Email *</label>
            <input type="email" name="email" value="<?= e($old['email']) ?>" class="input-card" required/>
          </div>
          <div class="form-group">
            <label class="label">NIP *</label>
            <input type="text" name="nip" value="<?= e($old['nip'] ?? '') ?>" class="input-card" required/>
          </div>
          <div class="form-group">
            <label class="label">NIK</label>
            <input type="text" name="nik" value="<?= e($old['nik'] ?? '') ?>" class="input-card"/>
          </div>
          <div class="form-group">
            <label class="label">NPWP</label>
            <input type="text" name="npwp" value="<?= e($old['npwp'] ?? '') ?>" class="input-card"/>
          </div>
          <div class="form-group">
            <label class="label">No. Telepon</label>
            <input type="text" name="no_telp" value="<?= e($old['no_telp'] ?? '') ?>" class="input-card"/>
          </div>
          <div class="form-group">
            <label class="label">Role *</label>
            <select name="role" class="input-card" <?= $id===(int)currentUser()['id']?'disabled':'' ?>>
              <?php foreach (['pegawai','atasan','admin_bkpsdm','eksekutif','super_admin'] as $r): ?>
              <option value="<?= $r ?>" <?= $old['role']===$r?'selected':'' ?>><?= ucfirst(str_replace('_', ' ', $r)) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($id===(int)currentUser()['id']): ?>
            <input type="hidden" name="role" value="<?= e($old['role']) ?>"/>
            <p style="color:#f59e0b;font-size:12px;font-weight:500;margin-top:6px;display:flex;align-items:center;gap:4px;">
              <span class="material-symbols-outlined" style="font-size:14px;">warning</span> Tidak bisa mengubah role akun sendiri
            </p>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="label">Status Pegawai</label>
            <select name="status" class="input-card">
              <?php foreach (['aktif','nonaktif','cuti'] as $s): ?>
              <option value="<?= $s ?>" <?= ($old['status']??'aktif')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="label">Divisi *</label>
            <select name="divisi" class="input-card">
              <?php foreach ($daftarDivisi as $div): ?>
              <option value="<?= e($div) ?>" <?= ($old['divisi']??'')===$div?'selected':'' ?>><?= e($div) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="label">Posisi/Jabatan *</label>
            <input type="text" name="posisi" value="<?= e($old['posisi'] ?? '') ?>" class="input-card" required/>
          </div>
          <div class="form-group">
            <label class="label">Status ASN *</label>
            <select name="jenis_asn" class="input-card" required>
              <option value="PNS" <?= ($old['jenis_asn']??'PNS')==='PNS'?'selected':'' ?>>PNS</option>
              <option value="PPPK" <?= ($old['jenis_asn']??'')==='PPPK'?'selected':'' ?>>PPPK</option>
              <option value="Non-ASN" <?= ($old['jenis_asn']??'')==='Non-ASN'?'selected':'' ?>>Honorer / Non-ASN</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label">Golongan Ruang</label>
            <select name="golongan" class="input-card">
              <option value="">— Pilih —</option>
              <?php foreach (['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d','IV/e'] as $g): ?>
              <option value="<?= $g ?>" <?= ($old['golongan']??'')===$g?'selected':'' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="label">Pendidikan Terakhir</label>
            <select name="pendidikan" class="input-card">
              <option value="">— Pilih —</option>
              <?php foreach (['SMA/SMK','D3','D4','S1','S2','S3'] as $p): ?>
              <option value="<?= $p ?>" <?= ($old['pendidikan']??'')===$p?'selected':'' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="label">Tanggal Masuk</label>
            <input type="date" name="tgl_masuk" value="<?= e($old['tgl_masuk'] ?? '') ?>" class="input-card"/>
          </div>
        </div>
        <div style="display:flex;gap:16px;margin-top:24px;padding-top:24px;border-top:1px solid #eaecf0;">
          <button type="submit" class="btn-primary" style="padding:12px 24px;">
            <span class="material-symbols-outlined" style="font-size:20px;">save</span> Simpan Perubahan
          </button>
          <a href="/simpekabjmk/pegawai.php" class="btn-ghost" style="padding:12px 24px;background:#ffffff;border:1px solid #eaecf0;">Batal</a>
        </div>
      </form>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
