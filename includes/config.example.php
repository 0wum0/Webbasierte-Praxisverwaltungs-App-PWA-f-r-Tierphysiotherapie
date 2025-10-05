<?php
declare(strict_types=1);

/**
 * Konfigurationsdatei - Beispiel
 * 
 * Kopiere diese Datei zu 'config.php' und passe die Werte an.
 * Die config.php sollte NICHT ins Git committed werden!
 */

return [
    'app' => [
        'name' => 'Tierphysio Praxis PWA',
        'env' => 'development', // production, development, testing
        'debug' => true,
        'timezone' => 'Europe/Berlin',
    ],
    
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'tierphysio',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
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
        'from_name' => 'Tierphysio Praxis',
        'encryption' => 'tls', // tls, ssl
    ],
    
    'session' => [
        'lifetime' => 1800, // 30 Minuten in Sekunden
        'secure' => false, // true für HTTPS
        'httponly' => true,
        'samesite' => 'Strict', // Strict, Lax, None
    ],
    
    'uploads' => [
        'max_size' => 2 * 1024 * 1024, // 2 MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
        'path' => __DIR__ . '/../uploads/',
    ],
    
    'logging' => [
        'level' => 'debug', // debug, info, warning, error, critical
        'channel' => 'daily', // daily, single
        'path' => __DIR__ . '/../logs/',
    ],
    
    'security' => [
        'csrf_enabled' => true,
        'password_min_length' => 8,
        'password_require_special' => true,
    ],
];
