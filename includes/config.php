<?php
declare(strict_types=1);

/**
 * Zentrale Konfigurations-Loader
 * 
 * Lädt Konfiguration aus .env Datei (wenn vorhanden) 
 * oder aus config-Array als Fallback
 */

// Prüfe ob .env Datei existiert und lade sie
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

/**
 * Hilfsfunktion: Hole Umgebungsvariable oder Default-Wert
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    // Boolean-Konvertierung
    if (in_array(strtolower($value), ['true', '(true)'], true)) {
        return true;
    }
    if (in_array(strtolower($value), ['false', '(false)'], true)) {
        return false;
    }
    
    return $value;
}

/**
 * Gibt Konfigurationswert zurück
 */
function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    
    if ($config === null) {
        // Lade config aus Datei falls vorhanden
        $configFile = __DIR__ . '/config.local.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
        } else {
            // Fallback: Aus Umgebungsvariablen bauen
            $config = [
                'app' => [
                    'name' => env('APP_NAME', 'Tierphysio Praxis PWA'),
                    'env' => env('APP_ENV', 'development'),
                    'debug' => env('APP_DEBUG', true),
                    'timezone' => env('APP_TIMEZONE', 'Europe/Berlin'),
                ],
                'database' => [
                    'host' => env('DB_HOST', 'localhost'),
                    'port' => (int)env('DB_PORT', 3306),
                    'name' => env('DB_NAME', ''),
                    'user' => env('DB_USER', ''),
                    'pass' => env('DB_PASS', ''),
                    'charset' => env('DB_CHARSET', 'utf8mb4'),
                ],
                'smtp' => [
                    'host' => env('SMTP_HOST', ''),
                    'port' => (int)env('SMTP_PORT', 587),
                    'user' => env('SMTP_USER', ''),
                    'pass' => env('SMTP_PASS', ''),
                    'from' => env('SMTP_FROM', ''),
                    'from_name' => env('SMTP_FROM_NAME', 'Tierphysio'),
                    'encryption' => env('SMTP_ENCRYPTION', 'tls'),
                ],
                'session' => [
                    'lifetime' => (int)env('SESSION_LIFETIME', 1800),
                    'secure' => env('SESSION_SECURE', false),
                    'httponly' => env('SESSION_HTTPONLY', true),
                    'samesite' => env('SESSION_SAMESITE', 'Strict'),
                ],
                'uploads' => [
                    'max_size' => (int)env('UPLOAD_MAX_SIZE', 2097152),
                    'allowed_extensions' => explode(',', env('UPLOAD_ALLOWED_EXTENSIONS', 'jpg,jpeg,png,pdf')),
                ],
                'logging' => [
                    'level' => env('LOG_LEVEL', 'debug'),
                    'channel' => env('LOG_CHANNEL', 'daily'),
                ],
            ];
        }
    }
    
    // Navigiere durch verschachtelte Keys (z.B. 'database.host')
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}
