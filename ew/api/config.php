<?php
/**
 * API Configuration
 * Controls authentication and environment settings
 */

// Environment detection
$is_development = (
    $_SERVER['HTTP_HOST'] === 'localhost' ||
    $_SERVER['HTTP_HOST'] === '127.0.0.1' ||
    strpos($_SERVER['HTTP_HOST'], '.local') !== false ||
    isset($_GET['dev_mode']) // Allow dev mode via URL parameter
);

// API Settings
define('API_REQUIRE_AUTH', !$is_development);
define('API_ALLOW_CORS', true);
define('API_RATE_LIMIT', 100); // Requests per minute
define('API_VERSION', '2.1.0');

// CORS Headers if enabled
if (API_ALLOW_CORS) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Development mode indicator
if ($is_development) {
    header('X-API-Mode: development');
} else {
    header('X-API-Mode: production');
}