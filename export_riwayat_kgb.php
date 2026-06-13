<?php
// export_riwayat_kgb.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';

requireLogin();
requireRole(['admin_bkpsdm', 'super_admin', 'atasan', 'eksekutif']);

$user = currentUser();
$role = $user['role'] ?? 'pegawai';

$query = "
    SELECT r.*, u.nama, p.nip, p.divisi
    FROM riwayat_kgb r
    JOIN users u ON u.id = r.user_id
    JOIN pegawai p ON p.user_id = u.id
";
$params = [];

if ($role === 'atasan') {
    $stmtDiv = $pdo->prepare("SELECT divisi FROM pegawai WHERE user_id = ?");
    $stmtDiv->execute([$user['id']]);
    $myDiv = $stmtDiv->fetchColumn();
    $query .= " WHERE p.divisi = ?";
    $params[] = $myDiv;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$riwayatList = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=riwayat_kgb_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Laporan Riwayat Kenaikan Gaji Berkala (KGB)']);
fputcsv($output, ['Tanggal Unduh:', date('d-m-Y H:i:s')]);
fputcsv($output, []);

fputcsv($output, ['Nama Pegawai', 'NIP', 'Divisi', 'TMT KGB Lama', 'TMT KGB Baru', 'Waktu Diproses']);

foreach ($riwayatList as $r) {
    fputcsv($output, [
        $r['nama'],
        $r['nip'] ? $r['nip'] : '-',
        $r['divisi'],
        date('d M Y', strtotime($r['tmt_lama'])),
        date('d M Y', strtotime($r['tmt_baru'])),
        date('d M Y H:i:s', strtotime($r['created_at']))
    ]);
}

fclose($output);
exit;
