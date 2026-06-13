<?php
require 'config/database.php';
try {
    $pdo->exec("ALTER TABLE absensi MODIFY COLUMN status ENUM('hadir','terlambat','alpha','izin','sakit','menunggu_konfirmasi') NOT NULL DEFAULT 'hadir'");
    $pdo->exec("ALTER TABLE absensi ADD COLUMN bukti_foto VARCHAR(255) NULL AFTER keterangan");
    echo "OK\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
