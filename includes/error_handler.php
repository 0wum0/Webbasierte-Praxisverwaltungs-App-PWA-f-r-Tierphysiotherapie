<?php
declare(strict_types=1);

require_once __DIR__ . '/logger.php';

/**
 * Zentraler Error Handler
 */
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    // Respektiere error_reporting Einstellung
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $logger = getLogger();
    
    $message = sprintf(
        'PHP Error [%s]: %s in %s on line %d',
        $errno,
        $errstr,
        $errfile,
        $errline
    );
    
    switch ($errno) {
        case E_ERROR:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            $logger->error($message);
            break;
            
        case E_WARNING:
        case E_CORE_WARNING:
        case E_COMPILE_WARNING:
        case E_USER_WARNING:
            $logger->warning($message);
            break;
            
        case E_NOTICE:
        case E_USER_NOTICE:
        case E_STRICT:
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            $logger->info($message);
            break;
            
        default:
            $logger->info($message);
            break;
    }
    
    // PHP internen Error Handler nicht ausführen
    return true;
});

/**
 * Zentraler Exception Handler
 */
set_exception_handler(function (Throwable $exception): void {
    $logger = getLogger();
    
    $message = sprintf(
        'Uncaught Exception [%s]: %s in %s:%d',
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    );
    
    $logger->error($message, [
        'trace' => $exception->getTraceAsString(),
        'previous' => $exception->getPrevious() ? $exception->getPrevious()->getMessage() : null,
    ]);
    
    // In Produktion: Generische Fehlerseite anzeigen
    // In Entwicklung: Detaillierte Fehlerausgabe
    if (getenv('APP_ENV') === 'production') {
        http_response_code(500);
        echo '<h1>Ein Fehler ist aufgetreten</h1>';
        echo '<p>Bitte kontaktieren Sie den Administrator.</p>';
    } else {
        // Entwicklungsmodus: Exception ausgeben
        throw $exception;
    }
});

/**
 * Shutdown Handler für Fatal Errors
 */
register_shutdown_function(function (): void {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
        $logger = getLogger();
        
        $message = sprintf(
            'Fatal Error [%s]: %s in %s on line %d',
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );
        
        $logger->error($message);
    }
});
