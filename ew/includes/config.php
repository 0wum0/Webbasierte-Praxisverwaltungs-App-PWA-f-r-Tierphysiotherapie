<?php
declare(strict_types=1);

/**
 * Configuration for Tierphysio Manager
 * Auto-generated for development environment
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'tierphysio');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App configuration
define('APP_NAME', 'Tierphysio Manager');
define('APP_ENV', 'development');
define('APP_DEBUG', true);
define('APP_TIMEZONE', 'Europe/Berlin');

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Error reporting for development
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Strict');

// Return array format for compatibility
return [
    'app' => [
        'name' => APP_NAME,
        'env' => APP_ENV,
        'debug' => APP_DEBUG,
        'timezone' => APP_TIMEZONE,
    ],
    
    'database' => [
        'host' => DB_HOST,
        'port' => 3306,
        'name' => DB_NAME,
        'user' => DB_USER,
        'pass' => DB_PASS,
        'charset' => DB_CHARSET,
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'user' => 'noreply@example.com',
        'pass' => '',
        'from' => 'noreply@example.com',
        'from_name' => APP_NAME,
        'encryption' => 'tls',
    ],
    
    'session' => [
        'lifetime' => 1800,
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Strict',
    ],
    
    'uploads' => [
        'max_size' => 2 * 1024 * 1024,
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
        'path' => __DIR__ . '/../uploads/',
    ],
    
    'logging' => [
        'level' => 'debug',
        'channel' => 'daily',
        'path' => __DIR__ . '/../logs/',
    ],
    
    'security' => [
        'csrf_enabled' => true,
        'password_min_length' => 8,
        'password_require_special' => true,
    ],
];