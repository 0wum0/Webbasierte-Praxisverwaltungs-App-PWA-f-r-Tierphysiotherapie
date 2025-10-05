<?php
declare(strict_types=1);

/**
 * Logout-Seite
 */

// Bootstrap laden
require_once __DIR__ . '/includes/bootstrap.php';

// Logout durchführen
if (function_exists('auth_logout')) {
    auth_logout();
}

if (function_exists('auth_admin_logout')) {
    auth_admin_logout();
}

// Session komplett zerstören
session_start();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Zur Login-Seite weiterleiten
header('Location: /login.php?logout=1');
exit;