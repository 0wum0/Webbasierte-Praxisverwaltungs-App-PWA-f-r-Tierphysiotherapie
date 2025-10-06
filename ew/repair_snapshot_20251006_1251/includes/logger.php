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
// SCHRITT 4: Logger-Implementierung basierend auf Verfügbarkeit
// ============================================================================

if (isMonologAvailable()) {
    // ========================================================================
    // Monolog ist verfügbar - professionelles Logging verwenden
    // ========================================================================
    
    /**
     * Erstellt und konfiguriert einen Monolog-Logger (Singleton-Pattern)
     * 
     * @param string $name Name des Loggers (z.B. 'app', 'db', 'auth')
     * @return \Monolog\Logger Monolog Logger Instanz
     */
    function getLogger(string $name = 'app'): \Monolog\Logger
    {
        static $loggers = [];
        
        // Bereits existierenden Logger zurückgeben
        if (isset($loggers[$name])) {
            return $loggers[$name];
        }
        
        $logger = new \Monolog\Logger($name);
        $logDir = ensureLogDirectory();
        
        // Handler für normale Logs (app.log) - Info-Level und höher
        $appHandler = new \Monolog\Handler\StreamHandler(
            $logDir . '/app.log', 
            \Monolog\Logger::DEBUG
        );
        
        // Handler für Fehler (error.log) - nur Error-Level und höher
        $errorHandler = new \Monolog\Handler\StreamHandler(
            $logDir . '/error.log', 
            \Monolog\Logger::ERROR
        );
        
        // Custom Formatter: Lesbares Format mit Zeitstempel
        $formatter = new \Monolog\Formatter\LineFormatter(
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
     * Schreibt eine Fehler-Nachricht ins Log
     * Verwendet Monolog ERROR Level
     * Schreibt sowohl in app.log als auch error.log
     */
    function logError(string $message, array $context = []): void
    {
        getLogger()->error($message, $context);
    }

    /**
     * Schreibt eine Warnung ins Log
     * Verwendet Monolog WARNING Level
     */
    function logWarning(string $message, array $context = []): void
    {
        getLogger()->warning($message, $context);
    }

    /**
     * Schreibt eine Info-Nachricht ins Log
     * Verwendet Monolog INFO Level
     * Für allgemeine informative Nachrichten
     */
    function logInfo(string $message, array $context = []): void
    {
        getLogger()->info($message, $context);
    }

    /**
     * Schreibt eine Debug-Nachricht ins Log
     * Verwendet Monolog DEBUG Level
     * Für detaillierte Debugging-Informationen
     */
    function logDebug(string $message, array $context = []): void
    {
        getLogger()->debug($message, $context);
    }

} else {
    // ========================================================================
    // FALLBACK: Einfacher Datei-Logger ohne Monolog
    // ========================================================================
    
    /**
     * Fallback: Schreibt Log-Nachricht direkt in Datei
     * Verwendet file_put_contents mit Timestamp-Format
     * 
     * @param string $level Log-Level (ERROR, WARNING, INFO, DEBUG)
     * @param string $message Log-Nachricht
     * @param array $context Zusätzlicher Kontext (wird als JSON gespeichert)
     * @param string $filename Dateiname für Log-Ausgabe
     */
    function writeSimpleLog(string $level, string $message, array $context = [], string $filename = 'info.log'): void
    {
        $logDir = ensureLogDirectory();
        $logFile = $logDir . '/' . $filename;
        
        // Zeitstempel formatieren (deutsches Format)
        $timestamp = date('Y-m-d H:i:s');
        
        // Kontext-Daten als JSON formatieren (falls vorhanden)
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        
        // Log-Zeile zusammenbauen
        $logLine = "[$timestamp] app.$level: $message$contextStr\n";
        
        // In Datei schreiben (FILE_APPEND = anhängen statt überschreiben)
        // LOCK_EX verhindert gleichzeitige Schreibzugriffe
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Schreibt eine Fehler-Nachricht ins Log (Fallback-Version)
     * Schreibt in error.log für Fehlertracking
     */
    function logError(string $message, array $context = []): void
    {
        // Fehler werden in error.log gespeichert
        writeSimpleLog('ERROR', $message, $context, 'error.log');
    }

    /**
     * Schreibt eine Warnung ins Log (Fallback-Version)
     * Schreibt in info.log
     */
    function logWarning(string $message, array $context = []): void
    {
        writeSimpleLog('WARNING', $message, $context, 'info.log');
    }

    /**
     * Schreibt eine Info-Nachricht ins Log (Fallback-Version)
     * Schreibt in info.log für allgemeine Informationen
     */
    function logInfo(string $message, array $context = []): void
    {
        writeSimpleLog('INFO', $message, $context, 'info.log');
    }

    /**
     * Schreibt eine Debug-Nachricht ins Log (Fallback-Version)
     * Schreibt in info.log
     */
    function logDebug(string $message, array $context = []): void
    {
        writeSimpleLog('DEBUG', $message, $context, 'info.log');
    }
    
    /**
     * Kompatibilitäts-Funktion für Code der getLogger() erwartet
     * Gibt ein Objekt mit den gleichen Methoden wie Monolog\Logger zurück
     * 
     * @param string $name Name des Loggers (wird im Fallback ignoriert)
     * @return object Logger-ähnliches Objekt mit error(), warning(), info(), debug() Methoden
     */
    function getLogger(string $name = 'app'): object
    {
        // Anonyme Klasse die Logger-Interface nachbildet
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
// INITIALISIERUNG: Logger-System bereit
// ============================================================================

// Initiale Log-Meldung zur Bestätigung der erfolgreichen Initialisierung
// Diese Meldung zeigt welcher Logger-Modus aktiv ist
if (isMonologAvailable()) {
    // Monolog verfügbar - professionelles Logging aktiv
    logDebug('Logger initialisiert mit Monolog-Unterstützung');
} else {
    // Fallback-Modus aktiv - einfaches File-Logging
    logDebug('Logger initialisiert im Fallback-Modus (Monolog nicht verfügbar)');
}