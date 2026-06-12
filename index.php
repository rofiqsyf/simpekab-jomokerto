<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/auth_guard.php';
require_once __DIR__ . '/helpers/functions.php';

// Redirect entry point
if (isLoggedIn()) {
    redirect('/simpeg_mini/dashboard.php');
} else {
    redirect('/simpeg_mini/login.php');
}
