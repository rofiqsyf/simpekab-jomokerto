<?php
// export_pegawai.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';

requireLogin();
requireRole(['super_admin', 'admin_bkpsdm']);

$search      = trim($_GET['q'] ?? '');
$filterRole  = $_GET['role'] ?? '';
$filterDiv   = $_GET['divisi'] ?? '';

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
    SELECT u.nama, u.email, u.role, u.last_login, u.locked_until,
           p.nip, p.divisi, p.posisi, p.golongan, p.pendidikan, p.jenis_asn, p.tgl_masuk, p.status, p.no_telp
    FROM users u
    LEFT JOIN pegawai p ON p.user_id = u.id
    WHERE {$whereStr}
    ORDER BY u.role ASC, u.nama ASC
");
$stmtPegawai->execute($params);
$daftarPegawai = $stmtPegawai->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=data_pegawai_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Data Pegawai SIMPEKAB JOMOKERTO']);
fputcsv($output, ['Tanggal Unduh:', date('d-m-Y H:i:s')]);
if ($search || $filterRole || $filterDiv) {
    fputcsv($output, ['Filter Aktif:']);
    if ($search) fputcsv($output, ['- Pencarian:', $search]);
    if ($filterRole) fputcsv($output, ['- Role:', $filterRole]);
    if ($filterDiv) fputcsv($output, ['- Divisi:', $filterDiv]);
}
fputcsv($output, []);

fputcsv($output, ['Nama Lengkap', 'Email', 'Role', 'NIP', 'Jenis ASN', 'Golongan', 'Divisi', 'Posisi', 'Pendidikan', 'Tanggal Masuk', 'No. Telp', 'Status Kepegawaian', 'Login Terakhir', 'Status Akun']);

foreach ($daftarPegawai as $p) {
    $isLocked = $p['locked_until'] && strtotime($p['locked_until']) > time();
    $statusAkun = $isLocked ? 'Dikunci' : 'Aktif';

    fputcsv($output, [
        $p['nama'],
        $p['email'],
        $p['role'],
        $p['nip'] ? $p['nip'] : '-',
        $p['jenis_asn'] ? $p['jenis_asn'] : '-',
        $p['golongan'] ? $p['golongan'] : '-',
        $p['divisi'] ? $p['divisi'] : '-',
        $p['posisi'] ? $p['posisi'] : '-',
        $p['pendidikan'] ? $p['pendidikan'] : '-',
        $p['tgl_masuk'] ? date('d-m-Y', strtotime($p['tgl_masuk'])) : '-',
        $p['no_telp'] ? $p['no_telp'] : '-',
        ucfirst($p['status'] ?? 'aktif'),
        $p['last_login'] ? date('d-m-Y H:i:s', strtotime($p['last_login'])) : 'Belum Pernah',
        $statusAkun
    ]);
}

fclose($output);
exit;
