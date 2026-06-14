# SIMPEKAB JOMOKERTO — Sistem Informasi Manajemen Kepegawaian Kabupaten Jomokerto
### Mini Project PHP + MySQL + RBAC
**Mata Kuliah:** Pemrograman Web | **Dosen:** M. Alif Muwafiq Baihaqy, M.Kom | **TEKNIK INFORMATIKA FASTIKOM UNSIQ**

---

## ⚙️ Persyaratan Sistem

| Komponen | Versi Minimum |
|----------|--------------|
| PHP | 8.0+ |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Apache | 2.4+ |
| XAMPP / Laragon | Terbaru |

---

## 🚀 Cara Instalasi

### Langkah 1 — Tempatkan Folder Proyek

**Laragon:**
```
C:\laragon\www\simpekabjmk\
```

**XAMPP:**
```
C:\xampp\htdocs\simpekabjmk\
```

### Langkah 2 — Import Database

1. Buka **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Klik **"Import"** → pilih file `simpeg.sql`
3. Klik **"Go"** / **"Kirim"**

Atau via CLI:
```bash
mysql -u root -p < simpeg.sql
```

### Langkah 3 — Konfigurasi Database

Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'simpekabjmk');
define('DB_USER', 'root');
define('DB_PASS', '');       // Kosong untuk Laragon/XAMPP default
```

### Langkah 4 — Generate Hash Password (Bcrypt)

Buka browser → `http://localhost/simpekabjmk/seed_users.php`

Script ini akan generate hash Bcrypt untuk semua akun demo.

> **⚠️ PENTING:** Hapus `seed_users.php` setelah dijalankan!

### Langkah 5 — Akses Aplikasi

```
http://localhost/simpekabjmk/
```

---

## 👥 Akun Demo

| Role | Email | Password |
|------|-------|----------|
| **Super Admin** | `admin@simpeg.test` | `admin123` |
| **Eksekutif** | `bupati@simpeg.test` | `admin123` |
| **Admin BKPSDM** | `bkpsdm@simpeg.test` | `admin123` |
| **Atasan** | `kadis.kominfo@simpeg.test` | `admin123` |
| **Pegawai** | `dedi@simpeg.test` | `admin123` |

---

## 🗂️ Struktur File

```
simpekabjmk/
├── config/
│   ├── database.php          ← Koneksi PDO (wajib dikonfigurasi)
│   └── session.php           ← Session security hardening
├── helpers/
│   ├── auth_guard.php        ← RBAC: requireLogin(), requireRole()
│   └── functions.php         ← CSRF, flash, activity log, helpers
├── partials/
│   ├── head.php              ← HTML <head> + Jomokerto Obsidian CSS
│   ├── sidebar.php           ← Navigasi sidebar (role-aware)
│   ├── topbar.php            ← Top navigation bar (dilengkapi Global Search Autocomplete)
│   ├── footer.php            ← JS helpers + closing tags
│   └── widget_absensi.php    ← Widget Absensi GPS Live dengan validasi luar radius & lampiran foto
├── index.php                 ← Entry point → redirect
├── login.php                 ← Login + rate limiting + remember me
├── logout.php                ← Secure logout (destroy session + cookie)
├── export_absensi_saya.php   ← Export CSV Absensi Pribadi
├── export_absensi_tim.php    ← Export CSV Rekap Kehadiran Tim
├── export_dashboard_eksekutif.php ← Export CSV Data Makro
├── export_log.php            ← Export CSV Log Aktivitas Sistem
├── export_pegawai.php        ← Export CSV Master Data Pegawai
├── export_riwayat_kgb.php    ← Export CSV Riwayat Rekam Jejak KGB
├── dashboard.php             ← Dashboard (semua role, data berbeda)
├── profil.php                ← Profil ASN + Digital Locker
├── absensi.php               ← Check-in/out GPS + riwayat + koreksi absen + upload foto luar radius
├── absensi_tim.php           ← Rekap tim (atasan + super_admin + admin_bkpsdm)
├── absensi_approval.php      ← Approval Absensi Luar Radius (admin_bkpsdm + super_admin)
├── pegawai.php               ← Data Pegawai (CRUD)
├── pegawai_tambah.php        ← Form Tambah (Bcrypt hash)
├── pegawai_edit.php          ← Form Edit
├── pegawai_hapus.php         ← Hapus (Nonaktifkan)
├── pegawai_import.php        ← Import Pegawai Massal via CSV
├── pegawai_import_template.php ← Unduh Template CSV Import
├── kinerja_skp.php           ← Input SKP & Realisasi Bulanan (Pegawai)
├── kinerja_evaluasi.php      ← Evaluasi SKP Bawahan (Atasan)
├── layanan_pengajuan.php     ← Pengajuan Cuti / Izin (Pegawai)
├── layanan_approval.php      ← Persetujuan Cuti / Izin (Atasan)
├── layanan_verifikasi.php    ← Verifikasi Akhir / Terbit SK (Admin BKPSDM)
├── reset_password.php        ← Reset + unlock akun (super_admin + admin_bkpsdm)
├── keamanan.php              ← Brankas: RBAC matrix + threat monitor (super_admin)
├── log.php                   ← Log aktivitas + pagination (super_admin)
├── seed_users.php            ← ⚠️ Jalankan sekali, lalu HAPUS!
├── simpeg.sql                ← SQL schema base
├── migrate.sql               ← SQL migration untuk kolom ASN
├── migrate2.sql              ← SQL migration untuk SKP
└── README.md                 ← Dokumentasi ini
```

---

## 🔒 Implementasi Keamanan (Materi Pertemuan 9)

### 1. Session Management Aman (`config/session.php`)
```php
ini_set('session.cookie_httponly', '1');     // JS tidak bisa baca cookie
ini_set('session.cookie_samesite', 'Strict');// Cegah CSRF via cookie
ini_set('session.use_strict_mode', '1');     // Tolak session ID palsu
ini_set('session.use_only_cookies', '1');    // Larang session via URL
ini_set('session.gc_maxlifetime', '3600');   // Timeout 1 jam
```

### 2. Session Fixation Prevention (`login.php`)
```php
// Wajib dipanggil SETELAH login sukses
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
```

### 3. Password Bcrypt (`pegawai_tambah.php`, `login.php`)
```php
// Saat simpan password
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Saat verifikasi login
if (password_verify($input, $hashDariDB)) { /* LOGIN SUKSES */ }

// Auto-upgrade jika cost factor meningkat
if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])) {
    $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
```

### 4. CSRF Protection (`helpers/functions.php`)
```php
// Generate token (sekali per session)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validasi dengan hash_equals() — cegah timing attack!
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token tidak valid!');
}

// Regenerate setelah validasi (one-time use)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
```

### 5. Rate Limiting & Account Lockout (`login.php`)
```php
// Cek percobaan login
if ($user['login_attempts'] >= 5) {
    $lockUntil = date('Y-m-d H:i:s', time() + 900); // 15 menit
    // UPDATE ... SET locked_until = ?
}

// Pesan error GENERIK — jangan bedakan email vs password
$errors[] = 'Email atau password salah'; // OWASP Best Practice
```

### 6. RBAC Guard (`helpers/auth_guard.php`)
```php
// Hanya admin yang boleh akses
requireRole(['super_admin']);

// Beberapa role
requireRole(['super_admin', 'admin_bkpsdm']);

// Semua yang sudah login
requireLogin();
```

### 7. Remember Me dengan Token Rotation (`helpers/functions.php`)
```php
// Generate token plain
$tokenPlain = bin2hex(random_bytes(32));
$tokenHash  = hash('sha256', $tokenPlain); // Simpan hash ke DB

// Set cookie
setcookie('simpeg_remember', "$userId:$tokenPlain", [
    'expires' => time() + (30 * 24 * 60 * 60),
    'httponly'=> true, 'samesite' => 'Strict'
]);

// Saat digunakan: regenerate token baru (token rotation)
setRememberMeToken($userId);
```

### 8. Prepared Statements (Semua halaman)
```php
// SELALU gunakan prepared statement
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// JANGAN pernah string concatenation:
// ❌ $pdo->query("SELECT * FROM users WHERE email='$email'")
```

---

## 📊 Tabel Database

### `users` — Akun login + keamanan
```sql
id, nama, email, password (Bcrypt),
role (super_admin|eksekutif|admin_bkpsdm|atasan|pegawai),
remember_token, login_attempts, locked_until, last_login
```

### `pegawai` — Data ASN / Kepegawaian
```sql
id, user_id (FK→users), nip, nik, npwp, divisi, posisi,
golongan, pendidikan, jenis_asn (PNS|PPPK|Non-ASN),
no_telp, tgl_masuk, status (aktif|nonaktif|cuti)
```

### `absensi` — Record kehadiran
```sql
id, user_id (FK→users), tanggal, check_in, check_out,
status (hadir|terlambat|alpha|izin|sakit), keterangan, durasi_mnt
UNIQUE KEY (user_id, tanggal)
```

### `kinerja_skp` — Sasaran Kinerja Pegawai (Baru)
```sql
id, user_id (FK→users), bulan, kegiatan, target, realisasi,
capaian, nilai_atasan, periode, nilai, catatan_atasan,
status (draft|submitted|reviewed)
```

### `pengajuan_layanan` — Layanan Mandiri (Baru)
```sql
id, user_id (FK→users), jenis, tanggal_mulai, tanggal_selesai,
keterangan, status (pending_atasan|approved_atasan|approved_bkpsdm|rejected)
```

### `activity_log` — Audit trail
```sql
id, user_id (FK→users, NULL=anonim), aksi, detail,
ip_address, user_agent, level (info|warning|critical)
```

---

## 📋 Kriteria Penilaian

| Aspek | Implementasi | Bobot |
|-------|-------------|-------|
| Fungsionalitas login/logout/session | ✅ Lengkap — `config/session.php` + `login.php` + `logout.php` | 20% |
| Password hashing Bcrypt | ✅ `PASSWORD_BCRYPT cost=12` + auto-rehash + `password_verify` | 15% |
| RBAC (5 role akses berbeda) | ✅ `requireRole()` guard + menu dinamis + dashboard adaptif | 25% |
| Keamanan (CSRF, session, rate limit) | ✅ Semua 4 aspek terimplementasi penuh | 20% |
| Remember Me *(bonus)* | ✅ Token rotation + SHA-256 + HttpOnly cookie | +10% |
| Kode bersih & terstruktur | ✅ Modular (config/helpers/partials), komentar lengkap | 10% |
| Dokumentasi README | ✅ Instalasi, schema, penjelasan keamanan | 10% |

---

## 🐛 Troubleshooting

| Masalah | Solusi |
|---------|--------|
| "Koneksi Database Gagal" | Pastikan MySQL berjalan, cek `config/database.php` |
| Login selalu gagal | Jalankan `seed_users.php` terlebih dahulu |
| "Session config error" | Pastikan tidak ada output sebelum `require session.php` |
| Error 403 Forbidden | User tidak punya izin akses halaman tersebut (normal) |
| Cookie tidak tersimpan | Cek konfigurasi `session.cookie_secure` (matikan jika tidak HTTPS) |

---

*Semester Genap 2025/2026 — Pemrograman Web, TEKNIK INFORMATIKA FASTIKOM UNSIQ*
