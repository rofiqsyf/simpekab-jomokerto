<?php
// export_absensi_tim.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';

requireLogin();
requireRole(['super_admin', 'admin_bkpsdm', 'atasan']);

$user  = currentUser();
$bulan = (int)($_GET['bulan'] ?? date('m'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$bulan = max(1, min(12, $bulan));
$tahun = max(2020, min(2030, $tahun));

if (hasRole('super_admin') || hasRole('admin_bkpsdm')) {
    $filterDivisi = $_GET['divisi'] ?? '';
} else {
    $stmtDiv = $pdo->prepare("SELECT divisi FROM pegawai WHERE user_id = ?");
    $stmtDiv->execute([$user['id']]);
    $filterDivisi = $stmtDiv->fetchColumn() ?? '';
}

$params  = [$bulan, $tahun];
$divWhere = '';
if ($filterDivisi) {
    $divWhere = 'AND p.divisi = ?';
    $params[] = $filterDivisi;
}

$stmtTim = $pdo->prepare("
    SELECT
        u.nama, u.email, u.role,
        p.divisi, p.posisi, p.nip,
        COUNT(a.id)                          AS total_hari,
        COALESCE(SUM(a.status='hadir'), 0)      AS jml_hadir,
        COALESCE(SUM(a.status='terlambat'), 0)  AS jml_terlambat,
        COALESCE(SUM(a.status='alpha'), 0)      AS jml_alpha,
        COALESCE(SUM(a.status='izin'), 0)       AS jml_izin,
        COALESCE(SUM(a.status='sakit'), 0)      AS jml_sakit
    FROM users u
    JOIN pegawai p ON p.user_id = u.id
    LEFT JOIN absensi a ON a.user_id = u.id
        AND MONTH(a.tanggal) = ?
        AND YEAR(a.tanggal) = ?
    WHERE 1=1 {$divWhere}
    GROUP BY u.id, u.nama, u.email, u.role, p.divisi, p.posisi, p.nip
    ORDER BY p.divisi, u.nama
");
$stmtTim->execute($params);
$dataTim = $stmtTim->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=rekap_absensi_' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '_' . $tahun . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['LAPORAN REKAP ABSENSI TIM']);
fputcsv($output, ['Periode:', $bulan.'/'.$tahun]);
if ($filterDivisi) {
    fputcsv($output, ['Divisi:', $filterDivisi]);
}
fputcsv($output, []);

fputcsv($output, ['Nama Lengkap', 'NIP', 'Divisi', 'Posisi', 'Hadir', 'Terlambat', 'Alpha', 'Izin', 'Sakit', 'Persentase Kehadiran (%)']);

foreach ($dataTim as $r) {
    $nama = explode(',', $r['nama'])[0];
    $totalBulan = $r['jml_hadir'] + $r['jml_terlambat'] + $r['jml_alpha'];
    $persen     = $totalBulan > 0 ? round(($r['jml_hadir'] + $r['jml_terlambat']) / max($totalBulan,1) * 100) : 0;
    
    fputcsv($output, [
        $nama,
        $r['nip'] ? $r['nip'] : '-',
        $r['divisi'],
        $r['posisi'],
        $r['jml_hadir'],
        $r['jml_terlambat'],
        $r['jml_alpha'],
        $r['jml_izin'],
        $r['jml_sakit'],
        $persen
    ]);
}

fclose($output);
exit;
