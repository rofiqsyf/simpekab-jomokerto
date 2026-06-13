<?php
// export_absensi_saya.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';

requireLogin();

$user = currentUser();

$stmtRiwayat = $pdo->prepare("
    SELECT tanggal, check_in, check_out, status, keterangan, durasi_mnt
    FROM absensi
    WHERE user_id = ?
      AND MONTH(tanggal) = MONTH(CURDATE())
      AND YEAR(tanggal)  = YEAR(CURDATE())
    ORDER BY tanggal DESC
");
$stmtRiwayat->execute([$user['id']]);
$riwayat = $stmtRiwayat->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=absensi_saya_' . date('Y-m') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Riwayat Absensi Pribadi SIMPEKAB JOMOKERTO']);
fputcsv($output, ['Nama:', $user['nama']]);
fputcsv($output, ['Periode:', date('F Y')]);
fputcsv($output, []); // empty line

fputcsv($output, ['Tanggal', 'Check-In', 'Check-Out', 'Durasi Kerja', 'Status Kehadiran', 'Keterangan']);

foreach ($riwayat as $a) {
    // Format durasi
    $durasi = '-';
    if ($a['durasi_mnt'] > 0) {
        $jam = floor($a['durasi_mnt'] / 60);
        $mnt = $a['durasi_mnt'] % 60;
        $durasi = "{$jam}j {$mnt}m";
    }

    fputcsv($output, [
        date('d-m-Y', strtotime($a['tanggal'])),
        $a['check_in'] ?? '-',
        $a['check_out'] ?? '-',
        $durasi,
        strtoupper(str_replace('_', ' ', $a['status'])),
        $a['keterangan'] ?? '-'
    ]);
}

fclose($output);
exit;
