-- ============================================================
-- SIMPEKAB JOMOKERTO — Database Schema + Seed Data
-- Mata Kuliah: Pemrograman Web — FASTIKOM UNSIQ
-- Pertemuan 9: Session, Cookie, Autentikasi & RBAC
-- Dosen: M. Alif Muwafiq Baihaqy, M.Kom
-- ============================================================

-- Buat dan pilih database
CREATE DATABASE IF NOT EXISTS simpeg_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE simpeg_db;

-- ============================================================
-- TABEL: users
-- Menyimpan akun login + field keamanan
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nama            VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,       -- Bcrypt hash (60+ char)
    role            ENUM('admin','manager','karyawan') NOT NULL DEFAULT 'karyawan',
    remember_token  VARCHAR(100) NULL,           -- SHA256 hash token remember me
    login_attempts  INT NOT NULL DEFAULT 0,      -- Untuk rate limiting
    locked_until    DATETIME NULL,               -- NULL = tidak dikunci
    last_login      DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: pegawai
-- Data kepegawaian (one-to-one dengan users)
-- ============================================================
CREATE TABLE IF NOT EXISTS pegawai (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL UNIQUE,
    nip         VARCHAR(20) NOT NULL UNIQUE,
    divisi      VARCHAR(100) NOT NULL,
    posisi      VARCHAR(100) NOT NULL,
    no_telp     VARCHAR(20) NULL,
    tgl_masuk   DATE NOT NULL,
    status      ENUM('aktif','nonaktif','cuti') NOT NULL DEFAULT 'aktif',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: absensi
-- Record kehadiran harian pegawai
-- ============================================================
CREATE TABLE IF NOT EXISTS absensi (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    tanggal     DATE NOT NULL,
    check_in    TIME NULL,
    check_out   TIME NULL,
    status      ENUM('hadir','terlambat','alpha','izin','sakit') NOT NULL DEFAULT 'hadir',
    keterangan  VARCHAR(255) NULL,
    durasi_mnt  INT NULL,                         -- Durasi kerja dalam menit
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_absensi_user_tanggal (user_id, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: activity_log
-- Audit trail semua aksi penting di sistem
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NULL,                          -- NULL untuk aksi sistem/anonim
    aksi        VARCHAR(200) NOT NULL,
    detail      TEXT NULL,
    ip_address  VARCHAR(45) NULL,
    user_agent  VARCHAR(255) NULL,
    level       ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA: users
-- Password di-hash menggunakan Bcrypt cost=12
-- Gunakan seed_users.php untuk generate hash jika diperlukan
-- Berikut hash untuk password masing-masing:
--   admin123    → $2y$12$... (Bcrypt)
--   manager123  → $2y$12$...
--   karyawan123 → $2y$12$...
-- ============================================================
INSERT INTO users (nama, email, password, role) VALUES
-- Password: admin123
('Drs. Ahmad Hidayat, M.M.', 'admin@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
-- Password: manager123
('Siti Rahayu, S.T.', 'manager.it@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('Bima Prakoso, S.E.', 'manager.fin@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('Rina Agustina, S.Sos.', 'manager.ops@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
-- Password: karyawan123
('Dedi Kurniawan', 'dedi@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'karyawan'),
('Fitria Wulandari', 'fitria@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'karyawan'),
('Rizky Pratama', 'rizky@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'karyawan'),
('Novita Sari', 'novita@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'karyawan'),
('Hendra Wijaya', 'hendra@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'karyawan'),
('Mega Putri', 'mega@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'karyawan'),
('Andi Saputra', 'andi@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'karyawan'),
('Yunita Dewi', 'yunita@simpeg.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'karyawan');

-- ⚠️ CATATAN: Hash di atas adalah hash dari password='password' (bcrypt default).
-- Jalankan seed_users.php untuk generate hash yang benar.
-- Atau import simpeg.sql LALU jalankan seed_users.php

-- ============================================================
-- SEED DATA: pegawai
-- ============================================================
INSERT INTO pegawai (user_id, nip, divisi, posisi, no_telp, tgl_masuk, status) VALUES
(1,  'NIP2020001', 'HRD & Administrasi',   'Kepala HRD',            '081234567890', '2020-01-15', 'aktif'),
(2,  'NIP2021002', 'IT & Development',      'Manajer IT',            '081234567891', '2021-03-22', 'aktif'),
(3,  'NIP2021003', 'Finance & Akuntansi',   'Manajer Finance',       '081234567892', '2021-06-10', 'aktif'),
(4,  'NIP2022004', 'Operations',            'Manajer Operasional',   '081234567893', '2022-01-05', 'aktif'),
(5,  'NIP2022005', 'IT & Development',      'Developer Backend',     '081234567894', '2022-08-17', 'aktif'),
(6,  'NIP2023006', 'IT & Development',      'Developer Frontend',    '081234567895', '2023-02-01', 'aktif'),
(7,  'NIP2023007', 'Finance & Akuntansi',   'Staff Keuangan',        '081234567896', '2023-04-15', 'aktif'),
(8,  'NIP2023008', 'Finance & Akuntansi',   'Staf Akuntansi',        '081234567897', '2023-07-01', 'aktif'),
(9,  'NIP2023009', 'Operations',            'Staf Operasional',      '081234567898', '2023-09-12', 'aktif'),
(10, 'NIP2024010', 'Operations',            'Koordinator Lapangan',  '081234567899', '2024-01-15', 'aktif'),
(11, 'NIP2024011', 'Marketing',             'Staf Marketing',        '081234567800', '2024-03-01', 'aktif'),
(12, 'NIP2024012', 'Marketing',             'Desainer Grafis',       '081234567801', '2024-06-01', 'aktif');

-- ============================================================
-- SEED DATA: absensi (contoh data bulan ini)
-- ============================================================
INSERT INTO absensi (user_id, tanggal, check_in, check_out, status, keterangan, durasi_mnt) VALUES
-- Admin (user_id=1)
(1, DATE_FORMAT(CURDATE() - INTERVAL 5 DAY, '%Y-%m-%d'), '07:48:00', '17:02:00', 'hadir', 'Tepat waktu', 554),
(1, DATE_FORMAT(CURDATE() - INTERVAL 4 DAY, '%Y-%m-%d'), '08:23:00', '17:15:00', 'terlambat', 'Terlambat 23 menit', 532),
(1, DATE_FORMAT(CURDATE() - INTERVAL 3 DAY, '%Y-%m-%d'), '07:55:00', '17:00:00', 'hadir', 'Tepat waktu', 545),
(1, DATE_FORMAT(CURDATE() - INTERVAL 2 DAY, '%Y-%m-%d'), NULL, NULL, 'alpha', 'Tanpa keterangan', NULL),
(1, DATE_FORMAT(CURDATE() - INTERVAL 1 DAY, '%Y-%m-%d'), '08:00:00', '17:00:00', 'hadir', 'Tepat waktu', 540),
-- Manager IT (user_id=2)
(2, DATE_FORMAT(CURDATE() - INTERVAL 5 DAY, '%Y-%m-%d'), '07:50:00', '17:00:00', 'hadir', 'Tepat waktu', 550),
(2, DATE_FORMAT(CURDATE() - INTERVAL 4 DAY, '%Y-%m-%d'), '07:58:00', '17:10:00', 'hadir', 'Tepat waktu', 552),
(2, DATE_FORMAT(CURDATE() - INTERVAL 3 DAY, '%Y-%m-%d'), '08:30:00', '17:00:00', 'terlambat', 'Terlambat 30 menit', 510),
(2, DATE_FORMAT(CURDATE() - INTERVAL 2 DAY, '%Y-%m-%d'), '07:55:00', '17:05:00', 'hadir', 'Tepat waktu', 550),
(2, DATE_FORMAT(CURDATE() - INTERVAL 1 DAY, '%Y-%m-%d'), '07:45:00', '17:00:00', 'hadir', 'Tepat waktu', 555),
-- Karyawan IT (user_id=5)
(5, DATE_FORMAT(CURDATE() - INTERVAL 5 DAY, '%Y-%m-%d'), '07:52:00', '17:02:00', 'hadir', 'Tepat waktu', 550),
(5, DATE_FORMAT(CURDATE() - INTERVAL 4 DAY, '%Y-%m-%d'), '09:15:00', '17:30:00', 'terlambat', 'Terlambat 75 menit', 495),
(5, DATE_FORMAT(CURDATE() - INTERVAL 3 DAY, '%Y-%m-%d'), '07:58:00', '17:00:00', 'hadir', 'Tepat waktu', 542),
(5, DATE_FORMAT(CURDATE() - INTERVAL 2 DAY, '%Y-%m-%d'), '08:00:00', '17:00:00', 'hadir', 'Tepat waktu', 540),
(5, DATE_FORMAT(CURDATE() - INTERVAL 1 DAY, '%Y-%m-%d'), NULL, NULL, 'izin', 'Izin keperluan keluarga', NULL);

-- ============================================================
-- SEED DATA: activity_log
-- ============================================================
INSERT INTO activity_log (user_id, aksi, detail, ip_address, level) VALUES
(1, 'LOGIN_SUCCESS', 'Login berhasil sebagai admin', '127.0.0.1', 'info'),
(2, 'LOGIN_SUCCESS', 'Login berhasil sebagai manager', '127.0.0.1', 'info'),
(5, 'ABSENSI_CHECKIN', 'Check-in pukul 07:52', '127.0.0.1', 'info'),
(NULL, 'LOGIN_FAILED', 'Percobaan login dengan email: attacker@evil.com (5x)', '45.33.12.98', 'critical'),
(1, 'PEGAWAI_TAMBAH', 'Menambahkan pegawai baru: Yunita Dewi', '127.0.0.1', 'info');

--
-- Table structure for table password_reset_requests
--

CREATE TABLE IF NOT EXISTS password_reset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    jenis_layanan VARCHAR(100) DEFAULT 'Lupa Sandi',
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
