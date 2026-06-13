<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';

requireLogin();
requireRole(['super_admin', 'admin_bkpsdm']);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=template_import_pegawai.csv');

$output = fopen('php://output', 'w');

// Header
fputcsv($output, ['Nama', 'Email', 'Role', 'NIP', 'Jenis_ASN', 'Golongan', 'Divisi', 'Posisi', 'Pendidikan', 'No_Telp']);

// Contoh data yang valid untuk dihapus oleh user sebelum upload
fputcsv($output, ['Andi Susanto', 'andi@simpeg.test', 'pegawai', '198001012005011001', 'PNS', 'III/a', 'IT & Development', 'Staf IT', 'S1', '081234567890']);
fputcsv($output, ['Budi Pratama', 'budi@simpeg.test', 'atasan', '198202022010011002', 'PNS', 'IV/a', 'IT & Development', 'Kepala Bidang IT', 'S2', '081298765432']);

fclose($output);
exit;
