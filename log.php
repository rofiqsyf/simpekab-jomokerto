<?php
// ============================================================
// log.php — Log Aktivitas Sistem (Admin Only)
// Audit trail semua aksi pengguna & sistem
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireRole(['super_admin']);

$currentPage = 'log';
$pageTitle   = 'Log Aktivitas';

// Filter
$filterLevel = $_GET['level']    ?? '';
$filterUser  = trim($_GET['user'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 20;
$offset      = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($filterLevel && in_array($filterLevel, ['info','warning','critical'])) {
    $where[]  = 'al.level = ?';
    $params[] = $filterLevel;
}
if ($filterUser) {
    $where[]  = '(u.email LIKE ? OR al.ip_address LIKE ?)';
    $params[] = "%{$filterUser}%";
    $params[] = "%{$filterUser}%";
}
$whereStr = implode(' AND ', $where);

// Total count
$stmtCount = $pdo->prepare("
    SELECT COUNT(*) FROM activity_log al
    LEFT JOIN users u ON u.id = al.user_id
    WHERE {$whereStr}
");
$stmtCount->execute($params);
$totalLogs = (int) $stmtCount->fetchColumn();
$totalPages = (int) ceil($totalLogs / $perPage);

// Fetch logs
$stmtLogs = $pdo->prepare("
    SELECT al.id, al.aksi, al.detail, al.ip_address, al.level, al.created_at,
           u.email, u.nama, u.role
    FROM activity_log al
    LEFT JOIN users u ON u.id = al.user_id
    WHERE {$whereStr}
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmtLogs->execute($params);
$logs = $stmtLogs->fetchAll();

// Stats per level
$stmtLevelStats = $pdo->query("SELECT level, COUNT(*) as c FROM activity_log GROUP BY level");
$levelStats = [];
foreach ($stmtLevelStats->fetchAll() as $s) { $levelStats[$s['level']] = $s['c']; }
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
          <h1 class="section-title">Log Aktivitas Sistem</h1>
          <p class="section-subtitle">Audit trail seluruh aksi pengguna & sistem</p>
        </div>
        <div style="display:flex;align-items:center;gap:8px;background:#f0fdf4;padding:8px 16px;border-radius:20px;border:1px solid #bbf7d0;">
          <span class="dot-green animate-ping-slow"></span>
          <span style="color:#15803d;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;"><?= $totalLogs ?> total entri</span>
        </div>
      </div>

      <!-- Level stats -->
      <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:24px;">
        <?php foreach ([['info','Info','#10b981','#f0fdf4'],['warning','Warning','#f59e0b','#fffbeb'],['critical','Critical','#ef4444','#fef2f2']] as [$lvl,$label,$c,$bg]): ?>
        <a href="?level=<?= $lvl ?>" class="card" style="padding:20px;display:flex;align-items:center;gap:16px;text-decoration:none;cursor:pointer;border:1px solid <?= $filterLevel===$lvl?$c:'#eaecf0' ?>;transition:all 0.2s;">
          <div style="width:48px;height:48px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;color:<?= $c ?>;flex-shrink:0;">
            <span class="material-symbols-outlined" style="font-size:24px;">
              <?= $lvl==='info'?'info':($lvl==='warning'?'warning':'error') ?>
            </span>
          </div>
          <div>
            <div style="font-size:24px;font-weight:800;color:#1a1d1f;line-height:1.2;"><?= e($levelStats[$lvl] ?? 0) ?></div>
            <div style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= strtoupper($lvl) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- Filter -->
      <div class="card" style="padding:16px 20px;margin-bottom:24px;border:1px solid #eaecf0;box-shadow:0 4px 10px rgba(0,0,0,0.02);">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
          <input type="hidden" name="level" value="<?= e($filterLevel) ?>"/>
          <div style="position:relative;flex:1;min-width:250px;">
            <span class="material-symbols-outlined" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:20px;pointer-events:none;">search</span>
            <input type="text" name="user" value="<?= e($filterUser) ?>" placeholder="Cari email / IP..."
              class="input-card" style="padding-left:40px;width:100%;"/>
          </div>
          <select name="level" class="input-card" style="width:auto;min-width:160px;" onchange="this.form.submit()">
            <option value="">Semua Level</option>
            <?php foreach (['info','warning','critical'] as $lvl): ?>
            <option value="<?= $lvl ?>" <?= $filterLevel===$lvl?'selected':'' ?>><?= ucfirst($lvl) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-primary" style="padding:10px 20px;">
            <span class="material-symbols-outlined" style="font-size:18px;">search</span>
            Filter
          </button>
          <?php if ($filterLevel || $filterUser): ?>
          <a href="/simpekabjmk/log.php" class="btn-ghost" style="padding:10px 16px;">
            <span class="material-symbols-outlined" style="font-size:18px;">close</span>
            Reset
          </a>
          <?php endif; ?>
          <a href="/simpekabjmk/export_log.php?level=<?= urlencode($filterLevel) ?>&user=<?= urlencode($filterUser) ?>" class="btn-primary" style="padding:10px 16px;margin-left:auto;">
            <span class="material-symbols-outlined" style="font-size:18px;">download</span> CSV
          </a>
        </form>
      </div>

      <!-- Terminal-style log viewer (Light Theme) -->
      <div class="card" style="padding:0;overflow:hidden;margin-bottom:24px;border:1px solid #eaecf0;background:#ffffff;box-shadow:0 10px 25px rgba(0,0,0,0.02);">
        <div style="background:#f8fafc;padding:12px 20px;border-bottom:1px solid #eaecf0;display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="color:#3b82f6;font-size:18px;">terminal</span>
          <span style="color:#0f172a;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;">simpeg_audit.log — <?= date('Y-m-d') ?></span>
          <span class="dot-green animate-ping-slow" style="margin-left:auto;"></span>
        </div>
        <div style="padding:20px;height:240px;overflow-y:auto;font-family:'JetBrains Mono',monospace;font-size:13px;line-height:1.6;">
          <?php if (empty($logs)): ?>
          <span style="color:#94a3b8;">-- No log entries --</span>
          <?php else: ?>
          <?php foreach (array_slice($logs, 0, 15) as $log):
            $color = ['info'=>'#10b981','warning'=>'#f59e0b','critical'=>'#ef4444'][$log['level']] ?? '#64748b';
          ?>
          <div style="display:flex;gap:12px;align-items:start;margin-bottom:6px;">
            <span style="color:#94a3b8;">[<?= e(date('H:i:s', strtotime($log['created_at']))) ?>]</span>
            <span style="color:<?= $color ?>;font-weight:700;width:70px;">[<?= strtoupper(e($log['level'])) ?>]</span>
            <span style="color:#64748b;white-space:nowrap;"> <?= e($log['email'] ?? ($log['ip_address'] ?? 'SYSTEM')) ?></span>
            <span style="color:#1e293b;font-weight:500;"> — <?= e($log['aksi']) ?></span>
            <?php if ($log['ip_address']): ?>
            <span style="color:#94a3b8;"> (<?= e($log['ip_address']) ?>)</span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Table view -->
      <div class="card" style="padding:0;overflow:hidden;border:1px solid #eaecf0;box-shadow:0 4px 20px rgba(0,0,0,0.02);">
        <div style="padding:20px 24px;border-bottom:1px solid #eaecf0;background:#ffffff;display:flex;align-items:center;">
          <span style="color:#1a1d1f;font-weight:700;font-size:16px;">Halaman <?= $page ?> dari <?= max(1,$totalPages) ?> (<?= $totalLogs ?> entri)</span>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Waktu</th>
                <th>Pengguna</th>
                <th>Aksi</th>
                <th>Detail</th>
                <th>IP Address</th>
                <th>Level</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($logs)): ?>
              <tr><td colspan="6" style="text-align:center;color:#64748b;padding:48px;font-weight:500;">Tidak ada log ditemukan.</td></tr>
              <?php else: ?>
              <?php foreach ($logs as $log):
                $lvlBadge = [
                  'info'     => '<span class="badge badge-active">INFO</span>',
                  'warning'  => '<span class="badge badge-warning">WARN</span>',
                  'critical' => '<span class="badge badge-danger">KRITIS</span>',
                ][$log['level']] ?? '';
              ?>
              <tr>
                <td style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:13px;white-space:nowrap;font-weight:500;">
                  <?= e(date('d/m H:i:s', strtotime($log['created_at']))) ?>
                </td>
                <td>
                  <?php if ($log['email']): ?>
                  <div style="color:#3b82f6;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;margin-bottom:4px;"><?= e($log['email']) ?></div>
                  <?php if ($log['role']): ?><?= roleBadge($log['role']) ?><?php endif; ?>
                  <?php else: ?>
                  <span style="color:#94a3b8;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;">[SYSTEM/Anonim]</span>
                  <?php endif; ?>
                </td>
                <td style="color:#1a1d1f;font-size:14px;font-weight:600;"><?= e($log['aksi']) ?></td>
                <td style="color:#64748b;font-size:13px;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;" title="<?= e($log['detail'] ?? '') ?>"><?= e($log['detail'] ?? '—') ?></td>
                <td style="color:#64748b;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;"><?= e($log['ip_address'] ?? '—') ?></td>
                <td><?= $lvlBadge ?></td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div style="padding:20px 24px;border-top:1px solid #eaecf0;display:flex;gap:8px;justify-content:center;flex-wrap:wrap;background:#ffffff;">
          <?php for ($p=1;$p<=$totalPages;$p++): ?>
          <a href="?page=<?= $p ?>&level=<?= urlencode($filterLevel) ?>&user=<?= urlencode($filterUser) ?>"
            style="padding:8px 16px;border-radius:8px;font-family:'JetBrains Mono',monospace;font-size:14px;text-decoration:none;font-weight:600;transition:all 0.2s;
            <?= $p===$page ? 'background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;' : 'color:#64748b;border:1px solid #e2e8f0;background:#ffffff;' ?>">
            <?= $p ?>
          </a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
