<?php
/**
 * Health Check Endpoint
 * Returns JSON status to verify API is working
 */

require_once __DIR__ . '/bootstrap.php';

json_ok([
    'ok' => true,
    'time' => gmdate('c'),
    'version' => '1.0.0'
]);