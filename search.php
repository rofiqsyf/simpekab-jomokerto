<?php
// ============================================================
// search.php — Global Search untuk pencarian Pegawai
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireLogin();

$currentPage = 'search';
$pageTitle   = 'Hasil Pencarian';
$user        = currentUser();

$q = trim($_GET['q'] ?? '');
$results = [];

if ($q !== '') {
    $stmt = $pdo->prepare("
        SELECT u.id, u.nama, u.email, u.role, p.nip, p.posisi, p.divisi
        FROM users u
        LEFT JOIN pegawai p ON u.id = p.user_id
        WHERE u.nama LIKE ? OR u.email LIKE ? OR p.nip LIKE ? OR p.posisi LIKE ?
        ORDER BY u.nama ASC
        LIMIT 20
    ");
    $searchTerm = "%{$q}%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $results = $stmt->fetchAll();
    
    // Log pencarian
    logActivity($user['id'], 'SEARCH', "Mencari data dengan kata kunci: {$q}");
}

?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="page-content">
      
      <div style="margin-bottom:32px;">
        <h1 class="section-title">Hasil Pencarian</h1>
        <p class="section-subtitle">Menampilkan hasil pencarian untuk kata kunci: <strong>"<?= e($q) ?>"</strong></p>
      </div>

      <?php if ($q === ''): ?>
        <div class="card" style="text-align:center;padding:48px;border:1px dashed #cbd5e1;color:#64748b;">
          <span class="material-symbols-outlined" style="font-size:48px;margin-bottom:16px;">search</span>
          <p>Silakan masukkan kata kunci pencarian pada kotak di atas.</p>
        </div>
      <?php elseif (empty($results)): ?>
        <div class="card" style="text-align:center;padding:48px;border:1px dashed #cbd5e1;color:#64748b;">
          <span class="material-symbols-outlined" style="font-size:48px;margin-bottom:16px;">person_off</span>
          <p>Tidak ada pegawai yang cocok dengan "<strong><?= e($q) ?></strong>".</p>
        </div>
      <?php else: ?>
        <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));">
          <?php foreach ($results as $res): ?>
            <div class="card" style="display:flex;align-items:center;gap:16px;border:1px solid #eaecf0;box-shadow:0 4px 10px rgba(0,0,0,0.02);">
              <div style="width:48px;height:48px;border-radius:50%;background:#e0f2fe;color:#0ea5e9;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;">
                <?= getInisial($res['nama']) ?>
              </div>
              <div style="flex:1;">
                <div style="font-weight:700;color:#1a1d1f;font-size:15px;"><?= e($res['nama']) ?></div>
                <div style="color:#64748b;font-size:13px;font-family:'JetBrains Mono',monospace;"><?= e($res['nip'] ?? '-') ?></div>
                <div style="margin-top:4px;color:#94a3b8;font-size:12px;"><?= e($res['posisi'] ?? $res['role']) ?></div>
              </div>
              <div>
                <?= roleBadge($res['role']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
