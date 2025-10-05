<?php
declare(strict_types=1);

/**
 * Datenbank-Verbindung
 * Nutzt zentrale Config-Verwaltung
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';

$host = config('database.host', 'localhost');
$port = config('database.port', 3306);
$db = config('database.name', '');
$user = config('database.user', '');
$pass = config('database.pass', '');
$charset = config('database.charset', 'utf8mb4');

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,    // Fehler als Exception
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Standard: assoziatives Array
    PDO::ATTR_EMULATE_PREPARES => false,             // echte Prepared Statements
    PDO::ATTR_STRINGIFY_FETCHES => false,            // Native Datentypen
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    logDebug('Database connection established');
} catch (PDOException $e) {
    logError('Database connection failed', [
        'error' => $e->getMessage(),
        'host' => $host,
        'database' => $db,
    ]);
    
    // In Produktion: Generische Fehlermeldung
    if (config('app.env') === 'production') {
        die("❌ Datenbankverbindung fehlgeschlagen. Bitte kontaktieren Sie den Administrator.");
    }
    
    die("❌ DB-Verbindung fehlgeschlagen: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
