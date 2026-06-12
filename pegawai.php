<?php
// ============================================================
// pegawai.php — Daftar & Manajemen Pegawai (Admin Only)
// CRUD: Lihat semua, Tambah, Edit, Hapus, Reset Password
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireRole(['admin']); // Admin Only!

$currentPage = 'pegawai';
$pageTitle   = 'Data Pegawai';
$user        = currentUser();

// Filter & Search
$search      = trim($_GET['q']      ?? '');
$filterRole  = $_GET['role']        ?? '';
$filterDiv   = $_GET['divisi']      ?? '';

// Build WHERE clause
$where   = ['1=1'];
$params  = [];

if ($search) {
    $where[]  = '(u.nama LIKE ? OR u.email LIKE ? OR p.nip LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($filterRole) {
    $where[]  = 'u.role = ?';
    $params[] = $filterRole;
}
if ($filterDiv) {
    $where[]  = 'p.divisi = ?';
    $params[] = $filterDiv;
}

$whereStr = implode(' AND ', $where);

$stmtPegawai = $pdo->prepare("
    SELECT u.id, u.nama, u.email, u.role, u.last_login,
           u.login_attempts, u.locked_until,
           p.nip, p.divisi, p.posisi, p.tgl_masuk, p.status, p.no_telp
    FROM users u
    LEFT JOIN pegawai p ON p.user_id = u.id
    WHERE {$whereStr}
    ORDER BY u.role ASC, u.nama ASC
");
$stmtPegawai->execute($params);
$daftarPegawai = $stmtPegawai->fetchAll();

// Daftar divisi untuk dropdown filter
$stmtDivisi = $pdo->query("SELECT DISTINCT divisi FROM pegawai ORDER BY divisi");
$daftarDivisi = $stmtDivisi->fetchAll(PDO::FETCH_COLUMN);

// Hitung total per role
$stmtCountRole = $pdo->query("SELECT role, COUNT(*) as c FROM users GROUP BY role");
$countRole = [];
foreach ($stmtCountRole->fetchAll() as $r) { $countRole[$r['role']] = $r['c']; }
?>
<?php include __DIR__ . '/partials/head.php'; ?>

<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="page-content">
      <?= renderFlash() ?>

      <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:32px;flex-wrap:wrap;gap:16px;">
        <div>
          <h1 class="section-title">Data Pegawai</h1>
          <p class="section-subtitle">CRUD pegawai, manajemen akun & reset password</p>
        </div>
        <a href="/simpeg_mini/pegawai_tambah.php" class="btn-primary" style="padding:10px 20px;">
          <span class="material-symbols-outlined" style="font-size:20px;">person_add</span>
          Tambah Pegawai
        </a>
      </div>

      <!-- Role summary cards -->
      <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:24px;">
        <?php foreach ([['admin','Admin','#ef4444','#fef2f2'],['manager','Manager','#f59e0b','#fffbeb'],['karyawan','Karyawan','#0ea5e9','#f0f9ff']] as [$r,$label,$c,$bg]): ?>
        <div class="card" style="padding:20px;display:flex;align-items:center;gap:16px;cursor:pointer;border:1px solid <?= $filterRole===$r?$c:'#eaecf0' ?>;transition:all 0.2s;" onclick="window.location='?role=<?= $r ?>'">
          <div style="width:48px;height:48px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;color:<?= $c ?>;flex-shrink:0;">
            <span class="material-symbols-outlined" style="font-size:24px;">badge</span>
          </div>
          <div>
            <div style="font-size:24px;font-weight:800;color:#1a1d1f;line-height:1.2;"><?= e($countRole[$r] ?? 0) ?></div>
            <div style="color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= $label ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Search & Filter -->
      <div class="card" style="padding:16px 20px;margin-bottom:24px;border:1px solid #eaecf0;box-shadow:0 4px 10px rgba(0,0,0,0.02);">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
          <div style="position:relative;flex:1;min-width:250px;">
            <span class="material-symbols-outlined" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:20px;pointer-events:none;">search</span>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari nama, email, atau NIP..."
              class="input-card" style="padding-left:40px;width:100%;"/>
          </div>
          <select name="role" class="input-card" style="min-width:140px;width:auto;">
            <option value="">Semua Role</option>
            <option value="admin" <?= $filterRole==='admin'?'selected':'' ?>>Admin</option>
            <option value="manager" <?= $filterRole==='manager'?'selected':'' ?>>Manager</option>
            <option value="karyawan" <?= $filterRole==='karyawan'?'selected':'' ?>>Karyawan</option>
          </select>
          <select name="divisi" class="input-card" style="min-width:160px;width:auto;">
            <option value="">Semua Divisi</option>
            <?php foreach ($daftarDivisi as $div): ?>
            <option value="<?= e($div) ?>" <?= $filterDiv===$div?'selected':'' ?>><?= e($div) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-primary" style="padding:10px 20px;">
            <span class="material-symbols-outlined" style="font-size:18px;">filter_list</span>
            Filter
          </button>
          <?php if ($search || $filterRole || $filterDiv): ?>
          <a href="/simpeg_mini/pegawai.php" class="btn-ghost" style="padding:10px 16px;">
            <span class="material-symbols-outlined" style="font-size:18px;">close</span>
            Reset
          </a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Pegawai Table -->
      <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
        <div style="padding:20px 24px;border-bottom:1px solid #eaecf0;background:#ffffff;display:flex;align-items:center;">
          <span style="color:#1a1d1f;font-weight:700;font-size:16px;"><?= count($daftarPegawai) ?> pegawai ditemukan</span>
          <?php if ($search || $filterRole || $filterDiv): ?>
          <span class="badge badge-secondary" style="margin-left:8px;font-size:12px;">(hasil filter)</span>
          <?php endif; ?>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Pegawai</th>
                <th>NIP</th>
                <th>Role</th>
                <th>Divisi & Posisi</th>
                <th>Status Akun</th>
                <th>Login Terakhir</th>
                <th style="text-align:right;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($daftarPegawai)): ?>
              <tr><td colspan="7" style="text-align:center;color:#64748b;padding:48px;font-weight:500;">Tidak ada data pegawai.</td></tr>
              <?php else: ?>
              <?php foreach ($daftarPegawai as $p):
                $isLocked = $p['locked_until'] && strtotime($p['locked_until']) > time();
              ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:14px;">
                    <div class="avatar avatar-sm" style="background:#e0f2fe;color:#0ea5e9;font-weight:700;"><?= e(getInisial($p['nama'])) ?></div>
                    <div>
                      <div style="font-weight:600;color:#1a1d1f;font-size:14px;"><?= e($p['nama']) ?></div>
                      <div style="color:#64748b;font-size:12px;font-weight:500;"><?= e($p['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;"><?= e($p['nip'] ?? '—') ?></td>
                <td><?= roleBadge($p['role']) ?></td>
                <td>
                  <div style="color:#1a1d1f;font-weight:600;font-size:14px;"><?= e($p['divisi'] ?? '—') ?></div>
                  <div style="color:#64748b;font-size:13px;"><?= e($p['posisi'] ?? '') ?></div>
                </td>
                <td>
                  <?php if ($isLocked): ?>
                    <span style="display:flex;align-items:center;gap:6px;color:#ef4444;font-size:13px;font-weight:600;">
                      <span class="dot-red"></span>
                      Dikunci
                      <span style="font-size:12px;color:#94a3b8;font-weight:500;">(<?= e($p['login_attempts']) ?>x gagal)</span>
                    </span>
                  <?php else: ?>
                    <span style="display:flex;align-items:center;gap:6px;">
                      <span class="dot-green"></span>
                      <span style="color:#475569;font-size:13px;font-weight:600;"><?= e(ucfirst($p['status'] ?? 'aktif')) ?></span>
                    </span>
                  <?php endif; ?>
                </td>
                <td style="color:#64748b;font-size:13px;font-weight:500;">
                  <?= $p['last_login'] ? e(date('d/m/Y H:i', strtotime($p['last_login']))) : '—' ?>
                </td>
                <td style="text-align:right;">
                  <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <a href="/simpeg_mini/pegawai_edit.php?id=<?= $p['id'] ?>" class="btn-ghost" style="padding:8px;border:1px solid #e2e8f0;background:#ffffff;" title="Edit">
                      <span class="material-symbols-outlined" style="font-size:18px;">edit</span>
                    </a>
                    <a href="/simpeg_mini/reset_password.php?id=<?= $p['id'] ?>" class="btn-warning" style="padding:8px;" title="Reset Password"
                       onclick="return confirm('Reset password <?= e(addslashes(explode(',', $p['nama'])[0])) ?>?')">
                      <span class="material-symbols-outlined" style="font-size:18px;">lock_reset</span>
                    </a>
                    <?php if ($p['id'] != $user['id']): ?>
                    <a href="/simpeg_mini/pegawai_hapus.php?id=<?= $p['id'] ?>" class="btn-danger" style="padding:8px;" title="Hapus"
                       onclick="return confirm('HAPUS permanen pegawai <?= e(addslashes(explode(',', $p['nama'])[0])) ?>? Tidak bisa dibatalkan!')">
                      <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
                    </a>
                    <?php else: ?>
                    <button class="btn-danger" style="padding:8px;opacity:0.4;cursor:not-allowed;" disabled title="Tidak bisa hapus akun sendiri">
                      <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
                    </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
