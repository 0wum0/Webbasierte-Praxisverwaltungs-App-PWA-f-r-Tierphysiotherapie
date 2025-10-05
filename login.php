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
    <title>Anmeldung - Tierphysio Praxis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 0 1rem;
        }
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 2.5rem;
        }
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .logo i {
            font-size: 2.5rem;
            color: white;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="bi bi-heart-pulse"></i>
            </div>
            
            <h3 class="text-center mb-4">Anmeldung</h3>
            
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
                
                <div class="mb-3">
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
                               required 
                               autofocus>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Passwort</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Angemeldet bleiben
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
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
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    © <?= date('Y') ?> Tierphysio Praxis
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>