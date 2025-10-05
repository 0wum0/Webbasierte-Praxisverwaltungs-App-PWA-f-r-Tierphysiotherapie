<?php
declare(strict_types=1);

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Erstellt und konfiguriert den zentralen Logger
 */
function getLogger(string $name = 'app'): Logger
{
    static $loggers = [];
    
    if (isset($loggers[$name])) {
        return $loggers[$name];
    }
    
    $logger = new Logger($name);
    
    // Log-Verzeichnis erstellen falls nötig
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    
    // Rotating File Handler (max 7 Tage)
    $handler = new RotatingFileHandler($logDir . '/app.log', 7, Logger::DEBUG);
    
    // Custom Formatter
    $formatter = new LineFormatter(
        "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
        'Y-m-d H:i:s',
        true,
        true
    );
    $handler->setFormatter($formatter);
    
    $logger->pushHandler($handler);
    
    $loggers[$name] = $logger;
    
    return $logger;
}

/**
 * Schnellzugriff auf Standard-Logger
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
