<?php
// ============================================================
// dashboard.php — Router Dashboard SIMPEKAB JOMOKERTO
// File ini bertugas sebagai router untuk mengarahkan pengguna
// ke dashboard spesifik berdasarkan role mereka.
// ============================================================

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/helpers/auth_guard.php';

// Pastikan user sudah login
if (!isLoggedIn()) {
    redirect('/simpekabjmk/login.php');
}

// Redirect ke dashboard masing-masing berdasarkan role
$role = $_SESSION['role'] ?? 'pegawai';

$redirectTo = match($role) {
    'super_admin'  => '/simpekabjmk/dashboard_super_admin.php',
    'eksekutif'    => '/simpekabjmk/dashboard_eksekutif.php',
    'admin_bkpsdm' => '/simpekabjmk/dashboard_admin_bkpsdm.php',
    'atasan'       => '/simpekabjmk/dashboard_atasan.php',
    default        => '/simpekabjmk/dashboard_pegawai.php',
};

redirect($redirectTo);
