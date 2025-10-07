<?php
/**
 * API Bootstrap - Strict JSON Response Handler
 * Ensures all API responses are valid JSON with proper headers
 */

declare(strict_types=1);

// Error reporting - hide from output
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Clean any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set JSON headers BEFORE any output
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

// Include config and database
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// JSON response helpers
function json_ok(array $data = [], int $code = 200): never {
    http_response_code($code);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function json_fail(string $msg = 'Unbekannter Fehler', int $code = 400, array $extra = []): never {
    http_response_code($code);
    $response = [
        'success' => false,
        'error' => array_merge([
            'code' => $code,
            'message' => $msg
        ], $extra)
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

// Exception handler
set_exception_handler(function($e) {
    error_log('API Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    json_fail($e->getMessage(), 500);
});

// Shutdown handler for fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('API Fatal: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        // Try to send JSON response if possible
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 500,
                    'message' => 'Fatal: ' . $error['message']
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    }
});

// JSON body reader
function read_json(): array {
    $raw = file_get_contents('php://input') ?: '';
    
    if ($raw === '') {
        return [];
    }
    
    $data = json_decode($raw, true);
    
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        json_fail('Ungültiges JSON: ' . json_last_error_msg(), 400, ['raw' => substr($raw, 0, 100)]);
    }
    
    return is_array($data) ? $data : [];
}

// Helper functions
function req(array $arr, string $key): string {
    return trim((string)($arr[$key] ?? ''));
}

function nullIfEmpty($value): ?string {
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

// Ensure database connection is available
if (!isset($pdo)) {
    try {
        $pdo = db();
    } catch (Exception $e) {
        json_fail('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage(), 500);
    }
}

// Ensure PDO is available globally
if (!isset($pdo) || !($pdo instanceof PDO)) {
    json_fail('Datenbankverbindung fehlgeschlagen', 500);
}