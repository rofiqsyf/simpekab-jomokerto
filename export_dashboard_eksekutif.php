<?php
// export_dashboard_eksekutif.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';

requireLogin();
if (!hasRole('eksekutif') && !hasRole('super_admin')) {
    die('Akses Ditolak');
}

// Ambil Data
$totalPegawai = (int) $pdo->query("SELECT COUNT(*) FROM pegawai WHERE status='aktif'")->fetchColumn();
$anggaranBulanIni = $totalPegawai * 5500000;
$anggaranTahunIni = $anggaranBulanIni * 12;

$bulanIni = date('m');
$tahunIni = date('Y');
$stmtDisiplin = $pdo->prepare("
    SELECT COUNT(*) as total_hari, SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as total_hadir
    FROM absensi WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?
");
$stmtDisiplin->execute([$bulanIni, $tahunIni]);
$kpi = $stmtDisiplin->fetch();
$persenDisiplin = $kpi['total_hari'] > 0 ? round(($kpi['total_hadir'] / $kpi['total_hari']) * 100, 1) : 0;

$stmtJenisASN = $pdo->query("SELECT jenis_asn, COUNT(id) as total FROM pegawai WHERE status='aktif' GROUP BY jenis_asn");
$komposisiJenis = $stmtJenisASN->fetchAll(PDO::FETCH_KEY_PAIR);

$stmtGol = $pdo->query("SELECT golongan, COUNT(id) as total FROM pegawai WHERE status='aktif' AND golongan IS NOT NULL GROUP BY golongan ORDER BY golongan");
$komposisiGolongan = $stmtGol->fetchAll(PDO::FETCH_KEY_PAIR);

// Export CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=laporan_makro_eksekutif_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Title
fputcsv($output, ['Laporan Makro Eksekutif SIMPEKAB JOMOKERTO']);
fputcsv($output, ['Tanggal Unduh:', date('d-m-Y H:i:s')]);
fputcsv($output, []); // Baris kosong

// KPI
fputcsv($output, ['INDIKATOR UTAMA', 'NILAI']);
fputcsv($output, ['Total Pegawai Aktif', $totalPegawai]);
fputcsv($output, ['Estimasi Anggaran Gaji (Bulan Ini)', 'Rp ' . number_format($anggaranBulanIni, 0, ',', '.')]);
fputcsv($output, ['Estimasi Anggaran Gaji (Tahun Ini)', 'Rp ' . number_format($anggaranTahunIni, 0, ',', '.')]);
fputcsv($output, ['Indeks Disiplin Bulan Ini', $persenDisiplin . '%']);
fputcsv($output, []);

// Jenis ASN
fputcsv($output, ['KOMPOSISI ASN', 'JUMLAH']);
foreach ($komposisiJenis as $jenis => $total) {
    fputcsv($output, [$jenis ? $jenis : 'Tidak Diketahui', $total]);
}
fputcsv($output, []);

// Golongan
fputcsv($output, ['KOMPOSISI GOLONGAN', 'JUMLAH']);
foreach ($komposisiGolongan as $gol => $total) {
    fputcsv($output, [$gol, $total]);
}

fclose($output);
exit;
