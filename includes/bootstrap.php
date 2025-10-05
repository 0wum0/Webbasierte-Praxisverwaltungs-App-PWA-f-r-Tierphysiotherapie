<?php
declare(strict_types=1);

/**
 * Bootstrap-Datei für die Anwendung
 * Lädt alle notwendigen Konfigurationen und Module
 */

// Fehlerbehandlung konfigurieren
$env = $_ENV['APP_ENV'] ?? 'production';
ini_set('display_errors', $env === 'development' ? '1' : '0');
error_reporting(E_ALL);

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Composer Autoloader laden (falls vorhanden)
$autoloaderPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];

$autoloaderLoaded = false;
foreach ($autoloaderPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaderLoaded = true;
        break;
    }
}

// Core-Module laden
$requiredModules = [
    'logger.php',      // Logging-System
    'config.php',      // Konfiguration
    'error_handler.php', // Error-Handler
    'db.php',          // Datenbank-Verbindung
    'auth.php',        // Authentifizierung
    'csrf.php',        // CSRF-Schutz
];

foreach ($requiredModules as $module) {
    $modulePath = __DIR__ . '/' . $module;
    if (file_exists($modulePath)) {
        require_once $modulePath;
    } else {
        // Fallback für fehlende Module
        if ($module === 'logger.php') {
            // Minimales Fallback-Logging
            function logError($message, $context = []) {
                $logDir = __DIR__ . '/../logs';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0775, true);
                }
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
                $logLine = "[$timestamp] ERROR: $message$contextStr\n";
                @file_put_contents($logDir . '/error.log', $logLine, FILE_APPEND | LOCK_EX);
            }
            function logInfo($message, $context = []) {
                $logDir = __DIR__ . '/../logs';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0775, true);
                }
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
                $logLine = "[$timestamp] INFO: $message$contextStr\n";
                @file_put_contents($logDir . '/info.log', $logLine, FILE_APPEND | LOCK_EX);
            }
            function logDebug($message, $context = []) {
                if ($env === 'development') {
                    logInfo($message, $context);
                }
            }
            function logWarning($message, $context = []) {
                logInfo($message, $context);
            }
        }
        
        // Log fehlende Module
        if (function_exists('logError')) {
            logError("Required module not found: $module");
        }
    }
}

// Session-Konfiguration
if (session_status() === PHP_SESSION_NONE) {
    $sessionConfig = [
        'cookie_lifetime' => (int)config('session.lifetime', 1800),
        'cookie_httponly' => config('session.httponly', true),
        'cookie_samesite' => config('session.samesite', 'Strict'),
    ];
    
    // HTTPS Detection
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $sessionConfig['cookie_secure'] = true;
    }
    
    session_start($sessionConfig);
}

// CSRF-Token initialisieren (falls csrf.php geladen wurde)
if (function_exists('csrf_init')) {
    csrf_init();
}

// Globale Konstanten definieren
if (!defined('APP_ENV')) {
    define('APP_ENV', config('app.env', 'production'));
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', config('app.debug', false));
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Globale Error-Handler registrieren
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Respektiere error_reporting Level
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED',
    ];
    
    $errorType = $errorTypes[$errno] ?? 'UNKNOWN';
    
    if (function_exists('logError')) {
        logError("PHP $errorType: $errstr in $errfile:$errline", [
            'errno' => $errno,
            'file' => $errfile,
            'line' => $errline,
        ]);
    }
    
    // In Entwicklung: Fehler anzeigen
    if (APP_ENV === 'development') {
        return false; // PHP's internal error handler will also run
    }
    
    // In Produktion: Fehler unterdrücken
    return true;
});

// Exception-Handler
set_exception_handler(function($exception) {
    if (function_exists('logError')) {
        logError("Uncaught Exception: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
    
    // In Produktion: Generische Fehlerseite
    if (APP_ENV === 'production') {
        http_response_code(500);
        echo '<!DOCTYPE html><html><head><title>Fehler</title></head><body>';
        echo '<h1>Ein Fehler ist aufgetreten</h1>';
        echo '<p>Bitte kontaktieren Sie den Administrator.</p>';
        echo '</body></html>';
        exit;
    }
    
    // In Entwicklung: Exception Details anzeigen
    throw $exception;
});

// Shutdown-Handler für fatale Fehler
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
        if (function_exists('logError')) {
            logError("Fatal Error: " . $error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $error['type'],
            ]);
        }
        
        // In Produktion: Generische Fehlerseite
        if (APP_ENV === 'production') {
            http_response_code(500);
            echo '<!DOCTYPE html><html><head><title>Fehler</title></head><body>';
            echo '<h1>Ein schwerwiegender Fehler ist aufgetreten</h1>';
            echo '<p>Bitte kontaktieren Sie den Administrator.</p>';
            echo '</body></html>';
        }
    }
});

// Bootstrap abgeschlossen
if (function_exists('logDebug')) {
    logDebug('Bootstrap completed', [
        'autoloader' => $autoloaderLoaded ? 'loaded' : 'not found',
        'environment' => APP_ENV,
        'debug' => APP_DEBUG,
    ]);
}