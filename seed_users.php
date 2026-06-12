<?php
// ============================================================
// seed_users.php — Generate Hash Bcrypt & Update Password DB
// Jalankan SEKALI setelah import simpeg.sql
// HAPUS file ini setelah dijalankan!
// ============================================================

// Akses langsung via browser: http://localhost/simpeg_mini/seed_users.php
// Atau via CLI: php seed_users.php

require_once __DIR__ . '/config/database.php';

echo '<pre style="background:#0a0a0c;color:#a4e6ff;font-family:JetBrains Mono,monospace;padding:24px;font-size:13px;">';
echo "SIMPEKAB JOMOKERTO — Bcrypt Password Seeder\n";
echo "======================================\n\n";

// Daftar user + password plain text
$userPasswords = [
    'admin@simpeg.test'      => 'admin123',
    'manager.it@simpeg.test' => 'manager123',
    'manager.fin@simpeg.test'=> 'manager123',
    'manager.ops@simpeg.test'=> 'manager123',
    'dedi@simpeg.test'       => 'karyawan123',
    'fitria@simpeg.test'     => 'karyawan123',
    'rizky@simpeg.test'      => 'karyawan123',
    'novita@simpeg.test'     => 'karyawan123',
    'hendra@simpeg.test'     => 'karyawan123',
    'mega@simpeg.test'       => 'karyawan123',
    'andi@simpeg.test'       => 'karyawan123',
    'yunita@simpeg.test'     => 'karyawan123',
];

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
$hashStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");

foreach ($userPasswords as $email => $plainPassword) {
    // Cek user ada
    $hashStmt->execute([$email]);
    $user = $hashStmt->fetch();

    if (!$user) {
        echo "❌ User tidak ditemukan: {$email}\n";
        continue;
    }

    // Generate hash Bcrypt dengan cost=12
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt->execute([$hash, $email]);

    echo "✅ {$email}\n";
    echo "   Password   : {$plainPassword}\n";
    echo "   Bcrypt Hash: " . substr($hash, 0, 30) . "...\n";
    echo "   Cost Factor: 12 (OWASP Compliant)\n\n";
}

echo "======================================\n";
echo "✅ Seeder selesai! Semua password telah di-hash.\n\n";
echo "⚠️  HAPUS file ini sekarang!\n";
echo "   Jalankan: del seed_users.php\n";
echo "   Atau hapus manual dari File Explorer.\n";
echo '</pre>';

// Verifikasi salah satu
$stmt2 = $pdo->prepare("SELECT password FROM users WHERE email='admin@simpeg.test'");
$stmt2->execute();
$row = $stmt2->fetch();
echo '<pre style="background:#0a0a0c;color:#00ffaa;font-family:JetBrains Mono,monospace;padding:0 24px 24px;font-size:13px;">';
echo "Verifikasi Admin:\n";
echo "  password_verify('admin123', hash) = " . (password_verify('admin123', $row['password']) ? '✅ TRUE' : '❌ FALSE') . "\n";
echo '</pre>';
