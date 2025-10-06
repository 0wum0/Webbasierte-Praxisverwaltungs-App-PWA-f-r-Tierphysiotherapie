<?php
declare(strict_types=1);

/**
 * Datenbank-Verbindung
 * Nutzt zentrale Config-Verwaltung
 */

// Config-Datei mit absolutem Pfad laden
require_once __DIR__ . '/config.php';

// Logger laden falls verfügbar
if (file_exists(__DIR__ . '/logger.php')) {
    require_once __DIR__ . '/logger.php';
}

// Datenbank-Konfiguration aus config.php lesen
$host = config('database.host', 'localhost');
$port = config('database.port', 3306);
$db = config('database.name', '');
$user = config('database.user', '');
$pass = config('database.pass', '');
$charset = config('database.charset', 'utf8mb4');

// DSN aufbauen
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

// PDO Optionen setzen
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,    // Fehler als Exception
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Standard: assoziatives Array
    PDO::ATTR_EMULATE_PREPARES => false,             // echte Prepared Statements
    PDO::ATTR_STRINGIFY_FETCHES => false,            // Native Datentypen
];

// Globale PDO-Instanz
$pdo = null;

/**
 * Hilfsfunktion zum Abrufen der Datenbankverbindung
 * 
 * @return PDO
 * @throws Exception wenn Verbindung fehlschlägt
 */
function db(): PDO {
    global $pdo, $dsn, $user, $pass, $options, $host, $db;
    
    // Wenn bereits verbunden, zurückgeben
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        // Debug-Logging wenn verfügbar
        if (function_exists('logDebug')) {
            logDebug('Database connection established');
        }
        
        return $pdo;
    } catch (PDOException $e) {
        // Error Logging wenn verfügbar
        if (function_exists('logError')) {
            logError('Database connection failed', [
                'error' => $e->getMessage(),
                'host' => $host,
                'database' => $db,
            ]);
        }
        
        // Benutzerfreundliche Fehlermeldung
        $errorMsg = "Datenbankverbindung konnte nicht hergestellt werden. ";
        $errorMsg .= "Bitte prüfen Sie die Konfiguration in includes/config.php.";
        
        // In Entwicklung: Detaillierte Fehler anzeigen
        if (config('app.env', 'production') === 'development' || config('app.debug', false)) {
            $errorMsg .= "\n\nDetails: " . $e->getMessage();
            $errorMsg .= "\nHost: $host, Database: $db";
        }
        
        throw new Exception($errorMsg, 0, $e);
    }
}

// Versuche Verbindung herzustellen (lazy loading)
// Die Verbindung wird erst beim ersten Aufruf von db() hergestellt
try {
    // Nur in kritischen Skripten vorab verbinden
    if (defined('REQUIRE_DB_CONNECTION') && REQUIRE_DB_CONNECTION) {
        $pdo = db();
    }
} catch (Exception $e) {
    // Fehler nur anzeigen wenn explizit angefordert
    if (defined('REQUIRE_DB_CONNECTION') && REQUIRE_DB_CONNECTION) {
        die("❌ " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
}
