<?php
/**
 * Legacy search endpoint wrapper
 * Forwards to main API router
 */

$_GET['action'] = 'search';
require_once __DIR__ . '/index.php';