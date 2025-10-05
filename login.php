<?php
declare(strict_types=1);

/**
 * Haupt-Login-Seite für Admin-Benutzer
 * 
 * Diese Seite ermöglicht die Anmeldung für Administrator-Accounts
 * nach erfolgter Installation
 */

require_once __DIR__ . '/includes/bootstrap.php';

// Session starten falls noch nicht gestartet
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => ($_SERVER['HTTPS'] ?? '') === 'on',
        'cookie_samesite' => 'Strict',
    ]);
}

// Bereits eingeloggt? -> Weiterleiten
if (function_exists('auth_check_admin') && auth_check_admin()) {
    header('Location: /dashboard.php');
    exit;
}

// Variablen initialisieren
$error = null;
$success = null;
$timeout = isset($_GET['timeout']) && $_GET['timeout'] === '1';

// Login-Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF-Validierung
        if (function_exists('csrf_validate')) {
            csrf_validate();
        }
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validierung
        if (empty($email) || empty($password)) {
            throw new RuntimeException('Bitte E-Mail und Passwort eingeben.');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
        }
        
        // Login versuchen
        require_once __DIR__ . '/includes/config.php';
        require_once __DIR__ . '/includes/db.php';
        
        // Admin aus Datenbank laden (case-insensitive)
        $stmt = $pdo->prepare("
            SELECT id, email, password_hash, display_name, is_active
            FROM admin_users 
            WHERE LOWER(email) = LOWER(:email)
        ");
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            throw new RuntimeException('Ungültige Anmeldedaten.');
        }
        
        // Account aktiv?
        if (!$admin['is_active']) {
            throw new RuntimeException('Dieser Account ist deaktiviert.');
        }
        
        // Passwort prüfen
        if (!password_verify($password, $admin['password_hash'])) {
            throw new RuntimeException('Ungültige Anmeldedaten.');
        }
        
        // Login erfolgreich - Session setzen
        if (function_exists('auth_admin_login')) {
            auth_admin_login(
                (int)$admin['id'],
                $admin['email'],
                $admin['display_name'] ?? $admin['email'],
                [] // Rollen können später implementiert werden
            );
        } else {
            // Fallback wenn auth_admin_login nicht verfügbar
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['display_name'] ?? $admin['email'];
            $_SESSION['admin_login_time'] = time();
            $_SESSION['admin_last_activity'] = time();
        }
        
        // Login-Info aktualisieren
        $stmt = $pdo->prepare("
            UPDATE admin_users 
            SET last_login = NOW(),
                last_login_ip = :ip
            WHERE id = :id
        ");
        $stmt->execute([
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':id' => $admin['id'],
        ]);
        
        // Logging
        if (function_exists('logInfo')) {
            logInfo('Admin login successful', [
                'admin_id' => $admin['id'],
                'email' => $admin['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        }
        
        // Redirect
        $redirectTo = $_SESSION['admin_redirect_after_login'] ?? '/dashboard.php';
        unset($_SESSION['admin_redirect_after_login']);
        
        header('Location: ' . $redirectTo);
        exit;
        
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
        
        // Logging
        if (function_exists('logWarning')) {
            logWarning('Admin login failed', [
                'email' => $email ?? 'unknown',
                'error' => $error,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        }
    } catch (Exception $e) {
        $error = 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
        
        // Logging
        if (function_exists('logError')) {
            logError('Login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

// CSRF-Token generieren
$csrfToken = '';
if (function_exists('csrf_token')) {
    $csrfToken = csrf_token();
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Anmeldung - Tierphysio Praxis PWA</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            max-width: 420px;
            width: 100%;
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 3rem 2rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
        }
        
        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 8px;
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .forgot-link {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        
        .forgot-link:hover {
            color: #667eea;
        }
        
        .install-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .install-link a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .install-link a:hover {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <h1 class="login-title">Willkommen zurück</h1>
                <p class="login-subtitle">Melden Sie sich an, um fortzufahren</p>
            </div>
            
            <?php if ($timeout): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-clock-history me-2"></i>
                Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <form method="post" action="login.php">
                <?php if (function_exists('csrf_field')): ?>
                    <?= csrf_field() ?>
                <?php else: ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail-Adresse</label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           placeholder="admin@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           required 
                           autofocus>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Passwort</label>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password"
                           placeholder="••••••••"
                           required>
                </div>
                
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Angemeldet bleiben
                        </label>
                    </div>
                    <a href="#" class="forgot-link" onclick="alert('Passwort-Wiederherstellung: Bitte kontaktieren Sie den Administrator.'); return false;">
                        Passwort vergessen?
                    </a>
                </div>
                
                <button type="submit" class="btn btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Anmelden
                </button>
            </form>
            
            <div class="install-link">
                <a href="/admin/login.php">
                    <i class="bi bi-shield-lock me-1"></i>
                    Admin-Bereich
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>