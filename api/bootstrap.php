<?php
/**
 * API Bootstrap - Central initialization for all API endpoints
 * Ensures consistent JSON responses and error handling
 */

// Prevent any output before headers
ob_clean();
ob_start();

// Set strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set timezone
date_default_timezone_set('Europe/Berlin');

// Set JSON headers immediately
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

// Allow CORS for API access
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

// Handle OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

// Load configuration and database
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Send successful JSON response
 * 
 * @param mixed $data Response data
 * @param int $code HTTP status code
 */
function json_ok($data = [], $code = 200) {
    ob_clean();
    http_response_code($code);
    
    $response = [
        'success' => true,
        'timestamp' => time(),
        'data' => $data
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send error JSON response
 * 
 * @param string $message Error message
 * @param int $code HTTP status code
 * @param array $extra Additional error details
 */
function json_fail($message = 'Unbekannter Fehler', $code = 400, $extra = []) {
    ob_clean();
    http_response_code($code);
    
    $response = [
        'success' => false,
        'timestamp' => time(),
        'error' => array_merge([
            'code' => $code,
            'message' => $message
        ], $extra)
    ];
    
    // Add debug information in development mode
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $response['error']['debug'] = [
            'file' => debug_backtrace()[0]['file'] ?? '',
            'line' => debug_backtrace()[0]['line'] ?? ''
        ];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Validate required fields in request
 * 
 * @param array $fields Required field names
 * @param array $data Data to validate (usually $_POST or $_GET)
 * @return array Validated data
 */
function validate_required($fields, $data) {
    $validated = [];
    $missing = [];
    
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        } else {
            $validated[$field] = trim($data[$field]);
        }
    }
    
    if (!empty($missing)) {
        json_fail('Pflichtfelder fehlen', 422, [
            'missing_fields' => $missing
        ]);
    }
    
    return $validated;
}

/**
 * Get JSON input from request body
 * 
 * @return array|null Decoded JSON or null
 */
function get_json_input() {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return null;
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_fail('Ungültiges JSON-Format', 400, [
            'json_error' => json_last_error_msg()
        ]);
    }
    
    return $data;
}

/**
 * Check if user is authenticated
 * 
 * @param bool $require If true, sends 401 response if not authenticated
 * @return bool
 */
function check_auth($require = true) {
    $authenticated = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    
    if (!$authenticated && $require) {
        json_fail('Nicht autorisiert', 401);
    }
    
    return $authenticated;
}

/**
 * Log API requests for debugging
 * 
 * @param string $action Action being performed
 * @param mixed $data Request data
 */
function log_api_request($action, $data = null) {
    if (!defined('APP_DEBUG') || !APP_DEBUG) {
        return;
    }
    
    $log_file = __DIR__ . '/../logs/api_requests.log';
    $log_entry = [
        'timestamp' => gmdate('c'),
        'action' => $action,
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_id' => $_SESSION['user_id'] ?? null,
        'data' => $data
    ];
    
    @file_put_contents(
        $log_file,
        json_encode($log_entry) . "\n",
        FILE_APPEND | LOCK_EX
    );
}

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        ob_clean();
        http_response_code(500);
        
        $response = [
            'success' => false,
            'error' => [
                'code' => 500,
                'message' => 'Interner Serverfehler'
            ]
        ];
        
        // Add debug info in development
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $response['error']['debug'] = [
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ];
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
});

// Set exception handler for uncaught exceptions
set_exception_handler(function($exception) {
    ob_clean();
    
    $code = $exception->getCode() ?: 500;
    if ($code < 100 || $code > 599) {
        $code = 500;
    }
    
    $message = 'Interner Serverfehler';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $message = $exception->getMessage();
    }
    
    json_fail($message, $code, [
        'exception' => defined('APP_DEBUG') && APP_DEBUG ? [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ] : null
    ]);
});

// Ensure we have database connection
try {
    if (!isset($pdo)) {
        $pdo = db();
    }
} catch (Exception $e) {
    json_fail('Datenbankverbindung fehlgeschlagen', 503, [
        'details' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : null
    ]);
}