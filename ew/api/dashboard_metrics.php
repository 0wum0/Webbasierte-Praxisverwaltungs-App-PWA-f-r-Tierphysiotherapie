<?php
/**
 * Legacy dashboard metrics endpoint wrapper
 * Forwards to main API router
 */

$_GET['action'] = 'dashboard_metrics';
require_once __DIR__ . '/index.php';