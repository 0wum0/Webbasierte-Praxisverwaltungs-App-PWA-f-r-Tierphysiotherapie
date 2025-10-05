<?php
declare(strict_types=1);

/**
 * CSRF Protection Helper
 * Generiert und validiert CSRF-Tokens für Formulare
 */

/**
 * Generiert ein neues CSRF-Token und speichert es in der Session
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Gibt ein verstecktes Input-Feld mit CSRF-Token zurück
 */
function csrf_field(): string
{
    $token = csrf_token();
    
    return sprintf('<input type="hidden" name="csrf_token" value="%s">', htmlspecialchars($token, ENT_QUOTES, 'UTF-8'));
}

/**
 * Validiert das CSRF-Token aus der Anfrage
 * 
 * @throws RuntimeException wenn Token ungültig oder fehlend
 */
function csrf_validate(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $sessionToken = $_SESSION['csrf_token'] ?? null;
    $requestToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    
    if ($sessionToken === null || $requestToken === null) {
        logWarning('CSRF validation failed: Token missing', [
            'session_token_exists' => $sessionToken !== null,
            'request_token_exists' => $requestToken !== null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
        
        throw new RuntimeException('CSRF-Token fehlt');
    }
    
    if (!hash_equals($sessionToken, $requestToken)) {
        logWarning('CSRF validation failed: Token mismatch', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        ]);
        
        throw new RuntimeException('Ungültiges CSRF-Token');
    }
}

/**
 * Middleware-Funktion: Validiert CSRF bei POST/PUT/DELETE/PATCH
 */
function csrf_middleware(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
        try {
            csrf_validate();
        } catch (RuntimeException $e) {
            http_response_code(403);
            die('403 Forbidden: ' . htmlspecialchars($e->getMessage()));
        }
    }
}

/**
 * Regeneriert das CSRF-Token (z.B. nach Login)
 */
function csrf_regenerate(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    return $_SESSION['csrf_token'];
}

/**
 * Initialisiert CSRF-Schutz
 * Stellt sicher, dass ein Token existiert
 */
function csrf_init(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
