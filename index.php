<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

// Redirect entry point
if (isLoggedIn()) {
    redirect('/simpekabjmk/dashboard.php');
} else {
    redirect('/simpekabjmk/login.php');
}
