<?php
declare(strict_types=1);

/**
 * Robustes Logging-System mit Monolog-Unterstützung und Fallback
 * 
 * Dieses Modul bietet ein zweistufiges Logging-System:
 * 1. Verwendet Monolog wenn verfügbar (professionelles Logging)
 * 2. Fällt zurück auf einfaches file_put_contents wenn Monolog fehlt
 * 
 * Autoload-Erkennung erfolgt automatisch für beide Pfade:
 * - /workspace/vendor/autoload.php (Standard)
 * - /workspace/../vendor/autoload.php (eine Ebene höher)
 */

// ============================================================================
// SCHRITT 1: Composer Autoloader automatisch einbinden (falls vorhanden)
// ============================================================================

/**
 * Versucht den Composer Autoloader zu finden und einzubinden
 * Prüft mehrere mögliche Pfade
 */
function tryLoadComposerAutoloader(): bool
{
    // Liste möglicher Autoloader-Pfade (relativ zu dieser Datei)
    $possiblePaths = [
        __DIR__ . '/../vendor/autoload.php',  // Standard: eine Ebene über includes/
        __DIR__ . '/../../vendor/autoload.php', // zwei Ebenen höher
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return true;
        }
    }
    
    return false;
}

// Autoloader laden (falls vorhanden)
$autoloaderLoaded = tryLoadComposerAutoloader();

// ============================================================================
// SCHRITT 2: Log-Verzeichnis sicherstellen
// ============================================================================

/**
 * Stellt sicher, dass das Log-Verzeichnis existiert
 * Erstellt es bei Bedarf mit korrekten Berechtigungen
 */
function ensureLogDirectory(): string
{
    $logDir = __DIR__ . '/../logs';
    
    if (!is_dir($logDir)) {
        // Verzeichnis erstellen mit Lese-/Schreibrechten
        mkdir($logDir, 0775, true);
    }
    
    return $logDir;
}

// ============================================================================
// SCHRITT 3: Monolog-Verfügbarkeit prüfen
// ============================================================================

/**
 * Prüft ob Monolog verfügbar ist
 * @return bool true wenn Monolog\Logger Klasse existiert
 */
function isMonologAvailable(): bool
{
    return class_exists('Monolog\Logger');
}

// ============================================================================
// SCHRITT 4: Monolog-Logger (wenn verfügbar)
// ============================================================================

if (isMonologAvailable()) {
    use Monolog\Logger;
    use Monolog\Handler\StreamHandler;
    use Monolog\Formatter\LineFormatter;

    /**
     * Erstellt und konfiguriert einen Monolog-Logger (Singleton-Pattern)
     * 
     * @param string $name Name des Loggers (z.B. 'app', 'db', 'auth')
     * @return Logger Monolog Logger Instanz
     */
    function getLogger(string $name = 'app'): Logger
    {
        static $loggers = [];
        
        // Bereits existierenden Logger zurückgeben
        if (isset($loggers[$name])) {
            return $loggers[$name];
        }
        
        $logger = new Logger($name);
        $logDir = ensureLogDirectory();
        
        // Handler für normale Logs (app.log)
        $appHandler = new StreamHandler($logDir . '/app.log', Logger::DEBUG);
        
        // Handler für Fehler (error.log)
        $errorHandler = new StreamHandler($logDir . '/error.log', Logger::ERROR);
        
        // Custom Formatter: Lesbares Format mit Zeitstempel
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,  // allowInlineLineBreaks
            true   // ignoreEmptyContextAndExtra
        );
        
        $appHandler->setFormatter($formatter);
        $errorHandler->setFormatter($formatter);
        
        // Handler zum Logger hinzufügen
        $logger->pushHandler($appHandler);
        $logger->pushHandler($errorHandler);
        
        // Logger cachen für spätere Verwendung
        $loggers[$name] = $logger;
        
        return $logger;
    }

    /**
     * Logging-Funktionen mit Monolog
     * Diese Funktionen sind die öffentliche API für die Anwendung
     */
    
    function logError(string $message, array $context = []): void
    {
        getLogger()->error($message, $context);
    }

    function logWarning(string $message, array $context = []): void
    {
        getLogger()->warning($message, $context);
    }

    function logInfo(string $message, array $context = []): void
    {
        getLogger()->info($message, $context);
    }

    function logDebug(string $message, array $context = []): void
    {
        getLogger()->debug($message, $context);
    }

} else {
    // ========================================================================
    // SCHRITT 5: Fallback-Logger (einfache Datei-basierte Lösung)
    // ========================================================================
    
    /**
     * Fallback: Einfacher Datei-Logger ohne Monolog
     * Schreibt Logs direkt mit file_put_contents
     * 
     * @param string $level Log-Level (ERROR, WARNING, INFO, DEBUG)
     * @param string $message Log-Nachricht
     * @param array $context Zusätzlicher Kontext (wird als JSON gespeichert)
     * @param string $filename Dateiname (app.log oder error.log)
     */
    function writeSimpleLog(string $level, string $message, array $context = [], string $filename = 'app.log'): void
    {
        $logDir = ensureLogDirectory();
        $logFile = $logDir . '/' . $filename;
        
        // Zeitstempel formatieren
        $timestamp = date('Y-m-d H:i:s');
        
        // Kontext-Daten als JSON formatieren (falls vorhanden)
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        
        // Log-Zeile zusammenbauen
        $logLine = "[$timestamp] app.$level: $message$contextStr\n";
        
        // In Datei schreiben (FILE_APPEND = anhängen statt überschreiben)
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Fallback-Logging-Funktionen (gleiche API wie Monolog-Version)
     */
    
    function logError(string $message, array $context = []): void
    {
        // Fehler gehen sowohl nach app.log als auch error.log
        writeSimpleLog('ERROR', $message, $context, 'app.log');
        writeSimpleLog('ERROR', $message, $context, 'error.log');
    }

    function logWarning(string $message, array $context = []): void
    {
        writeSimpleLog('WARNING', $message, $context);
    }

    function logInfo(string $message, array $context = []): void
    {
        writeSimpleLog('INFO', $message, $context);
    }

    function logDebug(string $message, array $context = []): void
    {
        writeSimpleLog('DEBUG', $message, $context);
    }
    
    /**
     * Dummy-Funktion für Kompatibilität
     * Im Fallback-Modus gibt es keine Logger-Instanzen
     */
    function getLogger(string $name = 'app'): object
    {
        return new class {
            public function error(string $message, array $context = []): void {
                logError($message, $context);
            }
            public function warning(string $message, array $context = []): void {
                logWarning($message, $context);
            }
            public function info(string $message, array $context = []): void {
                logInfo($message, $context);
            }
            public function debug(string $message, array $context = []): void {
                logDebug($message, $context);
            }
        };
    }
}

// ============================================================================
// INITIALISIERUNG: Logging-System ist bereit
// ============================================================================

// Log-Meldung zur Bestätigung der erfolgreichen Initialisierung
if (isMonologAvailable()) {
    logDebug('Logger initialized with Monolog support');
} else {
    logDebug('Logger initialized with fallback mode (Monolog not available)');
}
