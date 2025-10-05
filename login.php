<?php
declare(strict_types=1);

/**
 * Login-Seite für Tierphysio Praxis PWA
 */

// Bootstrap laden
require_once __DIR__ . '/includes/bootstrap.php';

// Wenn bereits eingeloggt, zum Dashboard weiterleiten
if (function_exists('auth_check') && auth_check()) {
    header('Location: /dashboard.php');
    exit;
}

if (function_exists('auth_check_admin') && auth_check_admin()) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Login-Verarbeitung
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Bitte geben Sie E-Mail und Passwort ein.';
    } else {
        // Login versuchen
        if (function_exists('admin_login')) {
            require_once __DIR__ . '/includes/db.php';
            
            if (admin_login($email, $password, $pdo)) {
                // Erfolgreicher Login - Weiterleitung
                $redirectTo = $_SESSION['redirect_after_login'] ?? '/dashboard.php';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirectTo);
                exit;
            } else {
                $error = 'Ungültige Anmeldedaten. Bitte versuchen Sie es erneut.';
            }
        } else {
            $error = 'Login-System nicht verfügbar. Bitte kontaktieren Sie den Administrator.';
        }
    }
}

// CSRF-Token generieren
$csrfToken = '';
if (function_exists('csrf_token')) {
    $csrfToken = csrf_token();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#ffffff">
    <title>Anmeldung - Tierphysio Manager 🐾</title>
    
    <!-- Modern Font Stack -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main Theme CSS -->
    <link href="assets/css/main.css" rel="stylesheet">
    
    <!-- Bootstrap for compatibility -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Theme Manager Script -->
    <script src="assets/js/theme.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #7B5BBE, #B9A9D0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            transition: background 0.5s ease;
        }
        
        [data-theme="dark"] body {
            background: linear-gradient(135deg, #3d2e5f, #1E1B24);
        }
        
        .login-container {
            max-width: 420px;
            width: 100%;
            padding: 0 1rem;
        }
        
        .login-card {
            background: var(--color-bg, #ffffff);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 3rem;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(123, 91, 190, 0.05), transparent);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #7B5BBE, #B9A9D0);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 40px;
            animation: pulse 2s infinite;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: 600;
            color: var(--color-text, #2d2d2d);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .logo-subtitle {
            font-size: 14px;
            color: var(--color-text-muted, #6b7280);
            margin-top: 4px;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            color: var(--color-text, #2d2d2d);
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 6px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group-text {
            background: var(--color-bg-secondary, #f8f8f9);
            border: 2px solid var(--color-border, #e4e4e7);
            border-right: none;
            color: var(--color-primary, #7B5BBE);
        }
        
        .form-control {
            background: var(--color-bg, #ffffff);
            border: 2px solid var(--color-border, #e4e4e7);
            border-left: none;
            color: var(--color-text, #2d2d2d);
            padding: 0.75rem 1rem;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #7B5BBE;
            box-shadow: 0 0 0 4px rgba(123, 91, 190, 0.1);
            outline: none;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: #7B5BBE;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #7B5BBE, #B9A9D0);
            border: none;
            color: white;
            padding: 0.875rem;
            font-weight: 500;
            font-size: 16px;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(123, 91, 190, 0.4);
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .theme-toggle-login {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10;
            background: var(--color-bg, #ffffff);
            border: 2px solid var(--color-primary, #7B5BBE);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
        }
        
        .theme-toggle-login:hover {
            background: var(--color-primary, #7B5BBE);
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(123, 91, 190, 0.3);
        }
        
        .alert {
            border-radius: 12px;
            padding: 1rem;
            font-size: 14px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #d97706;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #059669;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: var(--color-text-muted, #6b7280);
            font-size: 13px;
        }
        
        .form-check-input:checked {
            background-color: #7B5BBE;
            border-color: #7B5BBE;
        }
        
        .form-check-label {
            color: var(--color-text-muted, #6b7280);
            font-size: 14px;
        }
        
        hr {
            border-color: var(--color-border, #e4e4e7);
            opacity: 1;
        }
        
        a {
            color: var(--color-primary, #7B5BBE);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        a:hover {
            color: var(--color-primary-hover, #6B4BAE);
        }
    </style>
</head>
<body>
    <!-- Theme Toggle Button -->
    <button class="theme-toggle-login" data-theme-toggle aria-label="Toggle theme">🌙</button>
    
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo-icon">
                    <span>🐾</span>
                </div>
                <div class="logo-text">
                    Tierphysio Manager
                </div>
                <div class="logo-subtitle">Professionelle Praxisverwaltung</div>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-clock-history me-2"></i>
                Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                Sie wurden erfolgreich abgemeldet.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <form method="post" action="/login.php">
                <?php if (!empty($csrfToken)): ?>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="email" class="form-label">E-Mail-Adresse</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="ihre.email@beispiel.de"
                               required 
                               autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Passwort</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="••••••••"
                               required>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Angemeldet bleiben
                    </label>
                </div>
                
                <button type="submit" class="btn btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Anmelden
                </button>
            </form>
            
            <hr class="my-4">
            
            <div class="text-center">
                <small class="text-muted">
                    <a href="/forgot-password.php" class="text-decoration-none">Passwort vergessen?</a>
                </small>
            </div>
            
            <div class="footer-text">
                © <?= date('Y') ?> Tierphysio Manager - Alle Rechte vorbehalten
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>