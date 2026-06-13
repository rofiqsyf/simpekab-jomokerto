<?php
// ============================================================
// config/database.php
// Koneksi PDO ke MySQL
// ============================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'simpekabjmk');
define('DB_USER', 'root');
define('DB_PASS', '');       // Laragon default: '' | XAMPP default: ''
define('DB_CHARSET', 'utf8mb4');

function getPDO(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // Prepared statement native
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Jangan tampilkan detail error di production
            error_log('[SIMPEG DB Error] ' . $e->getMessage());
            die(renderDbError());
        }
    }

    return $pdo;
}

function renderDbError(): string {
    return '<!DOCTYPE html><html><head><meta charset="utf-8">
    <title>Koneksi Gagal — SIMPEKAB JOMOKERTO</title>
    <style>body{background:#0a0a0c;color:#ff3b3b;font-family:monospace;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
    .box{background:rgba(255,59,59,0.05);border:1px solid rgba(255,59,59,0.3);padding:32px;border-radius:8px;max-width:480px;}
    h2{margin:0 0 12px;color:#ff3b3b;}p{color:#bbc9cf;margin:0 0 8px;}</style>
    </head><body><div class="box">
    <h2>⚠ Koneksi Database Gagal</h2>
    <p>Tidak dapat terhubung ke MySQL. Pastikan:</p>
    <ul style="color:#bbc9cf;">
      <li>XAMPP/Laragon sudah berjalan (Apache + MySQL)</li>
      <li>Database <code>simpekabjmk</code> sudah dibuat</li>
      <li>Kredensial di <code>config/database.php</code> sudah benar</li>
    </ul>
    <p>Lihat <code>README.md</code> untuk petunjuk instalasi.</p>
    </div></body></html>';
}

// Shortcut global
$pdo = getPDO();
