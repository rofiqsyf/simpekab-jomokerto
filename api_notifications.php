<?php
// ============================================================
// api_notifications.php — Endpoint JSON untuk Notifikasi Real-time
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0, 'items' => []]);
    exit;
}

$user = currentUser();
$role = $user['role'];
$userId = $user['id'];

$notifications = [];
$count = 0;

if ($role === 'admin') {
    // Admin: Ambil tiket bantuan IT yang masih pending
    $stmt = $pdo->query("SELECT id, email, jenis_layanan, created_at FROM password_reset_requests WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
    $requests = $stmt->fetchAll();
    
    foreach ($requests as $r) {
        $notifications[] = [
            'title' => 'Tiket Baru: ' . e($r['jenis_layanan']),
            'message' => 'Dari: ' . e($r['email']),
            'time' => date('H:i', strtotime($r['created_at'])),
            'link' => '/simpekabjmk/permintaan_reset.php',
            'icon' => 'support_agent',
            'color' => '#f59e0b',
            'bg' => '#fffbeb'
        ];
        $count++;
    }
    
    // Admin: Ambil log security level warning/danger terbaru
    $stmtLog = $pdo->query("SELECT action, description, created_at, level FROM activity_log WHERE level IN ('warning', 'danger') ORDER BY created_at DESC LIMIT 5");
    foreach ($stmtLog->fetchAll() as $l) {
        $color = $l['level'] === 'danger' ? '#ef4444' : '#f59e0b';
        $bg = $l['level'] === 'danger' ? '#fef2f2' : '#fffbeb';
        $notifications[] = [
            'title' => 'Security: ' . e($l['action']),
            'message' => e($l['description']),
            'time' => date('H:i', strtotime($l['created_at'])),
            'link' => '/simpekabjmk/log.php',
            'icon' => 'warning',
            'color' => $color,
            'bg' => $bg
        ];
        $count++;
    }
} else {
    // Pegawai biasa: Ambil aktivitas terkait mereka (misal: PASSWORD_RESET)
    $stmtLog = $pdo->prepare("SELECT action, description, created_at, level FROM activity_log WHERE description LIKE ? ORDER BY created_at DESC LIMIT 5");
    // Karena kita tidak menyimpan target_user_id secara spesifik di log, kita cari dari description email mereka
    $stmtLog->execute(['%' . $user['email'] . '%']);
    
    foreach ($stmtLog->fetchAll() as $l) {
        $color = '#0ea5e9';
        $bg = '#f0f9ff';
        if ($l['level'] === 'danger') { $color = '#ef4444'; $bg = '#fef2f2'; }
        elseif ($l['level'] === 'warning') { $color = '#f59e0b'; $bg = '#fffbeb'; }
        
        $notifications[] = [
            'title' => e($l['action']),
            'message' => e($l['description']),
            'time' => date('d M H:i', strtotime($l['created_at'])),
            'link' => '#',
            'icon' => 'info',
            'color' => $color,
            'bg' => $bg
        ];
        $count++;
    }
}

// Urutkan notifikasi gabungan (jika ada) berdasarkan waktu secara sederhana (disini batas array saja)
$notifications = array_slice($notifications, 0, 5);

echo json_encode([
    'count' => $count,
    'items' => $notifications
]);
