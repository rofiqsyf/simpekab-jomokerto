<?php
// ============================================================
// api_search_pegawai.php — API Endpoint untuk Autocomplete Search
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';

header('Content-Type: application/json');

// Pastikan user sudah login
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.nama, u.email, p.nip, p.posisi 
        FROM users u
        LEFT JOIN pegawai p ON u.id = p.user_id
        WHERE u.nama LIKE ? OR u.email LIKE ? OR p.nip LIKE ?
        ORDER BY u.nama ASC
        LIMIT 5
    ");
    $searchTerm = "%{$q}%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode(['error' => 'Server Error']);
}
