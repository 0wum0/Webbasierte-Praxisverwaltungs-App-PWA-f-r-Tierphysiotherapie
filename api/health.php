<?php
/**
 * API Health Check Endpoint
 * Returns strict JSON response to verify API is working
 */

// Prevent any output before headers
ob_clean();
ob_start();

// Set strict error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Set JSON headers immediately
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

// Simple health check response
$response = [
    'ok' => true,
    'status' => 'healthy',
    'time' => gmdate('c'),
    'timezone' => date_default_timezone_get(),
    'php_version' => PHP_VERSION,
    'api_version' => '2.0.0'
];

// Clean any accidental output
ob_clean();

// Output JSON and exit immediately
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;