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

// ============================================================================
// Installation und Login Guards
// ============================================================================

/**
 * Check installation status and redirect if needed
 */
function checkInstallStatus() {
    $installLock = __DIR__ . '/install.lock';
    $configFile = __DIR__ . '/config.php';
    
    // System not installed - redirect to installer
    if (!file_exists($installLock)) {
        header('Location: /install/install.php');
        exit;
    }
    
    // Check for updates
    if (file_exists($configFile)) {
        try {
            require_once __DIR__ . '/version.php';
            require_once $configFile;
            require_once __DIR__ . '/db.php';
            
            global $pdo;
            
            // Check database version
            $stmt = $pdo->prepare("SELECT key_value FROM system_info WHERE key_name = 'db_version'");
            $stmt->execute();
            $dbVersion = $stmt->fetchColumn() ?: '0.0.0';
            
            // If update available, suggest update
            if (isUpdateAvailable($dbVersion)) {
                // Set session flag for update notification
                $_SESSION['update_available'] = true;
                $_SESSION['update_from_version'] = $dbVersion;
                $_SESSION['update_to_version'] = APP_VERSION;
            }
        } catch (Exception $e) {
            // Silently ignore version check errors
        }
    }
}

// WICHTIG: Lock-Datei jetzt im includes-Ordner (install-Ordner kann gelöscht werden)
$installLock = __DIR__ . '/install.lock';

// Aktuelle Request-Informationen ermitteln
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// Prüfen, ob wir uns im Installer befinden (verbesserte Erkennung)
$isInstaller = str_contains($currentScript, '/install/');

// Weitere Pfad-Checks für spezielle Bereiche
$isLoginPage = str_contains($currentScript, '/login.php');
$isAdminLogin = str_contains($currentScript, '/admin/login.php');
$isAdminArea = str_contains($currentScript, '/admin/');
$isStaticAsset = str_contains($requestUri, '/assets/') || str_contains($requestUri, '/uploads/');
$isApiEndpoint = str_contains($currentScript, '/api/');

// ============================================================================
// SCHRITT 1: Installer-Check (Lock-Datei prüfen)
// ============================================================================

if (!file_exists($installLock)) {
    // System ist NICHT installiert
    
    // Prüfe ob Install-Ordner noch existiert
    $installDir = dirname(__DIR__) . '/install';
    
    // Wenn wir nicht bereits im Installer sind und es kein Static Asset ist
    if (!$isInstaller && !$isStaticAsset) {
        // NUR weiterleiten wenn der Install-Ordner existiert
        if (is_dir($installDir)) {
            header('Location: /install/installer.php');
            exit('System ist nicht installiert. Bitte führen Sie die <a href="/install/installer.php">Installation</a> durch.');
        }
        // Kein Installer-Ordner = Installation wurde gelöscht, weiter ohne Redirect
    }
    // Installer darf ausgeführt werden, wenn Lock-Datei fehlt
    
} else {
    // System ist INSTALLIERT
    
    // ============================================================================
    // SCHRITT 2: Installer blockieren, wenn bereits installiert
    // ============================================================================
    
    if ($isInstaller) {
        // Installer-Zugriff blockieren und zum Login weiterleiten
        header('Location: /login.php');
        exit('System ist bereits installiert. <a href="/login.php">Zum Login</a>');
    }
    
    // ============================================================================
    // SCHRITT 3: Config-Datei prüfen (Safety Net)
    // ============================================================================
    
    $configFile = __DIR__ . '/config.php';
    if (!file_exists($configFile) && !$isStaticAsset) {
        // Konfiguration fehlt trotz Installation - Fehlerseite anzeigen
        http_response_code(503);
        echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfigurationsfehler</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .error-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            text-align: center;
        }
        h1 { color: #dc2626; margin-bottom: 1rem; }
        p { color: #4b5563; line-height: 1.6; }
        code { 
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            color: #dc2626;
        }
        .actions { margin-top: 2rem; }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 0.5rem;
        }
        .btn:hover { background: #4f46e5; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>⚠️ Konfigurationsfehler</h1>
        <p>
            <strong>Konfigurationsdatei fehlt.</strong><br>
            Bitte prüfen Sie Ihre Installation oder führen Sie eine Neuinstallation durch.
        </p>
        <p>
            Die Datei <code>includes/config.php</code> konnte nicht gefunden werden,
            obwohl das System als installiert markiert ist.
        </p>
        <div class="actions">
            <p><strong>Optionen:</strong></p>
            <p>1. Stellen Sie die fehlende Konfigurationsdatei wieder her</p>
            <p>2. Löschen Sie <code>install/installed.lock</code> für eine Neuinstallation</p>
        </div>
    </div>
</body>
</html>';
        exit;
    }
    
    // ============================================================================
    // SCHRITT 4: Login-Enforcement (nur für geschützte Bereiche)
    // ============================================================================
    
    // Bereiche, die KEINE Authentifizierung benötigen
    $publicAreas = [
        $isLoginPage,
        $isAdminLogin,
        $isStaticAsset,
        $isApiEndpoint, // APIs haben eigene Auth-Logik
    ];
    
    // Wenn wir uns nicht in einem öffentlichen Bereich befinden
    if (!in_array(true, $publicAreas, true)) {
        // Auth-Funktionen verfügbar?
        if (function_exists('auth_check') && function_exists('auth_check_admin')) {
            // Prüfen ob Benutzer eingeloggt ist
            $userLoggedIn = auth_check();
            $adminLoggedIn = auth_check_admin();
            
            // Niemand eingeloggt?
            if (!$userLoggedIn && !$adminLoggedIn) {
                // Redirect-URL speichern für späteren Redirect nach Login
                $_SESSION['redirect_after_login'] = $requestUri;
                
                // Je nach Bereich zum passenden Login weiterleiten
                if ($isAdminArea) {
                    header('Location: /admin/login.php');
                    exit('Bitte melden Sie sich an. <a href="/admin/login.php">Zum Admin-Login</a>');
                } else {
                    header('Location: /login.php');
                    exit('Bitte melden Sie sich an. <a href="/login.php">Zum Login</a>');
                }
            }
        }
    }
}

// Bootstrap abgeschlossen
if (function_exists('logDebug')) {
    logDebug('Bootstrap completed', [
        'autoloader' => $autoloaderLoaded ? 'loaded' : 'not found',
        'environment' => APP_ENV,
        'debug' => APP_DEBUG,
        'installed' => file_exists($installLock),
    ]);
}