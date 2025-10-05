<?php
declare(strict_types=1);

/**
 * Authentifizierungs-System
 * Session-basierte Authentifizierung für Admin und Benutzer
 */

/**
 * Startet eine Session (falls noch nicht gestartet)
 */
function auth_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => ($_SERVER['HTTPS'] ?? '') === 'on',
            'cookie_samesite' => 'Strict',
        ]);
    }
}

/**
 * Prüft, ob ein Benutzer eingeloggt ist
 */
function auth_check(): bool
{
    auth_session_start();
    
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Prüft, ob ein Admin eingeloggt ist
 */
function auth_check_admin(): bool
{
    auth_session_start();
    
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_email']);
}

/**
 * Gibt die ID des eingeloggten Benutzers zurück
 */
function auth_user_id(): ?int
{
    auth_session_start();
    
    return $_SESSION['user_id'] ?? null;
}

/**
 * Gibt die ID des eingeloggten Admins zurück
 */
function auth_admin_id(): ?int
{
    auth_session_start();
    
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Loggt einen Benutzer ein
 */
function auth_login(int $userId, string $email, string $name, string $role = 'user'): void
{
    auth_session_start();
    
    // Session-Fixation verhindern
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = $role;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Neues CSRF-Token nach Login
    csrf_regenerate();
    
    logInfo('User logged in', [
        'user_id' => $userId,
        'email' => $email,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);
}

/**
 * Loggt einen Admin ein
 */
function auth_admin_login(int $adminId, string $email, string $name, array $roles = []): void
{
    auth_session_start();
    
    // Session-Fixation verhindern
    session_regenerate_id(true);
    
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_email'] = $email;
    $_SESSION['admin_name'] = $name;
    $_SESSION['admin_roles'] = $roles;
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_last_activity'] = time();
    
    // Neues CSRF-Token nach Login
    csrf_regenerate();
    
    logInfo('Admin logged in', [
        'admin_id' => $adminId,
        'email' => $email,
        'roles' => $roles,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);
}

/**
 * Loggt den aktuellen Benutzer aus
 */
function auth_logout(): void
{
    auth_session_start();
    
    $userId = $_SESSION['user_id'] ?? null;
    
    if ($userId !== null) {
        logInfo('User logged out', [
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    }
    
    // Session komplett zerstören
    $_SESSION = [];
    
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    session_destroy();
}

/**
 * Loggt den aktuellen Admin aus
 */
function auth_admin_logout(): void
{
    auth_session_start();
    
    $adminId = $_SESSION['admin_id'] ?? null;
    
    if ($adminId !== null) {
        logInfo('Admin logged out', [
            'admin_id' => $adminId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    }
    
    // Nur Admin-Session-Daten löschen
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_email'],
        $_SESSION['admin_name'],
        $_SESSION['admin_roles'],
        $_SESSION['admin_login_time'],
        $_SESSION['admin_last_activity']
    );
}

/**
 * Route Guard: Leitet nicht-authentifizierte Benutzer um
 */
function auth_require_user(string $redirectTo = '/login.php'): void
{
    if (!auth_check()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . $redirectTo);
        exit;
    }
    
    // Session-Timeout prüfen (30 Minuten)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        auth_logout();
        header('Location: ' . $redirectTo . '?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Route Guard für Admin-Bereich
 */
function auth_require_admin(string $redirectTo = '/admin/login.php'): void
{
    if (!auth_check_admin()) {
        $_SESSION['admin_redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/admin/';
        header('Location: ' . $redirectTo);
        exit;
    }
    
    // Session-Timeout prüfen (30 Minuten)
    if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > 1800)) {
        auth_admin_logout();
        header('Location: ' . $redirectTo . '?timeout=1');
        exit;
    }
    
    $_SESSION['admin_last_activity'] = time();
}

/**
 * Prüft, ob Admin eine bestimmte Rolle hat
 */
function auth_admin_has_role(string $role): bool
{
    auth_session_start();
    
    $roles = $_SESSION['admin_roles'] ?? [];
    
    return in_array($role, $roles, true);
}

/**
 * Prüft, ob Admin eine bestimmte Permission hat
 */
function auth_admin_has_permission(string $permission, PDO $pdo): bool
{
    if (!auth_check_admin()) {
        return false;
    }
    
    $adminId = auth_admin_id();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM admin_permissions ap
        JOIN admin_role_permissions arp ON ap.id = arp.permission_id
        JOIN admin_user_roles aur ON arp.role_id = aur.role_id
        WHERE aur.user_id = :admin_id 
          AND ap.permission_key = :permission
    ");
    $stmt->execute([
        ':admin_id' => $adminId,
        ':permission' => $permission,
    ]);
    
    return (bool)$stmt->fetchColumn();
}

/**
 * Hasht ein Passwort mit Argon2id
 */
function auth_hash_password(string $password): string
{
    return password_hash($password, PASSWORD_ARGON2ID);
}

/**
 * Verifiziert ein Passwort
 */
function auth_verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Admin Login function with proper validation
 * Returns true on success, false on failure
 * @param string $email Admin email
 * @param string $password Plain text password
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function admin_login(string $email, string $password, PDO $pdo): bool
{
    try {
        // Admin-User aus DB laden mit case-insensitive email comparison
        $stmt = $pdo->prepare("
            SELECT id, email, password_hash, name, is_active, is_super_admin,
                   failed_login_attempts, locked_until
            FROM admin_users 
            WHERE LOWER(email) = LOWER(:email)
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Account gesperrt?
        if ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
            return false;
        }
        
        // Account aktiv?
        if (!$user['is_active']) {
            return false;
        }
        
        // Passwort prüfen mit password_verify
        if (!password_verify($password, $user['password_hash'])) {
            // Failed Login zählen
            $attempts = (int)$user['failed_login_attempts'] + 1;
            $lockUntil = null;
            
            // Nach 5 Versuchen: 15 Minuten sperren
            if ($attempts >= 5) {
                $lockUntil = date('Y-m-d H:i:s', time() + 900);
            }
            
            $stmt = $pdo->prepare("
                UPDATE admin_users 
                SET failed_login_attempts = :attempts,
                    locked_until = :locked_until
                WHERE id = :id
            ");
            $stmt->execute([
                ':attempts' => $attempts,
                ':locked_until' => $lockUntil,
                ':id' => $user['id'],
            ]);
            
            return false;
        }
        
        // Rollen laden
        $stmt = $pdo->prepare("
            SELECT r.name 
            FROM admin_roles r
            JOIN admin_user_roles aur ON r.id = aur.role_id
            WHERE aur.user_id = :user_id
        ");
        $stmt->execute([':user_id' => $user['id']]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Login erfolgreich -> Session setzen
        auth_admin_login((int)$user['id'], $user['email'], $user['name'], $roles);
        
        // Login-Info aktualisieren
        $stmt = $pdo->prepare("
            UPDATE admin_users 
            SET last_login = NOW(),
                last_login_ip = :ip,
                failed_login_attempts = 0,
                locked_until = NULL
            WHERE id = :id
        ");
        $stmt->execute([
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':id' => $user['id'],
        ]);
        
        // Audit Log
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (admin_user_id, action, description, ip_address, user_agent)
            VALUES (:user_id, 'login', 'Admin login successful', :ip, :user_agent)
        ");
        $stmt->execute([
            ':user_id' => $user['id'],
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
        
        return true;
    } catch (Exception $e) {
        logError('Admin login error', [
            'email' => $email,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}
