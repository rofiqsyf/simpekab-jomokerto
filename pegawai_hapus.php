<?php
// ============================================================
// pegawai_hapus.php — Hapus Pegawai (Admin Only)
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('/simpeg_mini/pegawai.php');

// Tidak bisa hapus diri sendiri
if ($id === (int)currentUser()['id']) {
    setFlash('error', 'Anda tidak bisa menghapus akun sendiri!');
    redirect('/simpeg_mini/pegawai.php');
}

// Ambil data pegawai
$stmt = $pdo->prepare("SELECT nama, email FROM users WHERE id = ?");
$stmt->execute([$id]);
$pegawai = $stmt->fetch();
if (!$pegawai) {
    setFlash('error', 'Pegawai tidak ditemukan.');
    redirect('/simpeg_mini/pegawai.php');
}

// Hapus (CASCADE ke tabel pegawai dan absensi via FK)
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);

logActivity(
    currentUser()['id'],
    'PEGAWAI_HAPUS',
    "Menghapus pegawai: {$pegawai['email']} (ID={$id})",
    'warning'
);
setFlash('success', "Pegawai {$pegawai['nama']} berhasil dihapus dari sistem.");
redirect('/simpeg_mini/pegawai.php');
