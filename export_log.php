<?php
// export_log.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';

requireLogin();
requireRole(['super_admin']);

$filterLevel = $_GET['level'] ?? '';
$filterUser  = trim($_GET['user'] ?? '');

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

$stmtLogs = $pdo->prepare("
    SELECT al.aksi, al.detail, al.ip_address, al.level, al.created_at,
           u.email, u.nama, u.role
    FROM activity_log al
    LEFT JOIN users u ON u.id = al.user_id
    WHERE {$whereStr}
    ORDER BY al.created_at DESC
");
$stmtLogs->execute($params);
$logs = $stmtLogs->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=log_aktivitas_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Log Aktivitas Sistem SIMPEKAB JOMOKERTO']);
fputcsv($output, ['Tanggal Unduh:', date('d-m-Y H:i:s')]);
if ($filterLevel) {
    fputcsv($output, ['Filter Level:', strtoupper($filterLevel)]);
}
if ($filterUser) {
    fputcsv($output, ['Filter Pencarian:', $filterUser]);
}
fputcsv($output, []);

fputcsv($output, ['Waktu', 'Level', 'User Email', 'IP Address', 'Aksi', 'Detail']);

foreach ($logs as $log) {
    fputcsv($output, [
        date('Y-m-d H:i:s', strtotime($log['created_at'])),
        strtoupper($log['level']),
        $log['email'] ? $log['email'] : 'SYSTEM',
        $log['ip_address'],
        $log['aksi'],
        $log['detail']
    ]);
}

fclose($output);
exit;
