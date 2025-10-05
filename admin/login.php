<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

auth_session_start();

// Bereits eingeloggt? -> Weiterleiten
if (auth_check_admin()) {
    header('Location: index.php');
    exit;
}

$error = null;
$timeout = isset($_GET['timeout']) && $_GET['timeout'] === '1';

// Login-Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_validate();
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            throw new RuntimeException('Bitte E-Mail und Passwort eingeben');
        }
        
        // Use the admin_login function from auth.php
        if (!admin_login($email, $password, $pdo)) {
            throw new RuntimeException('Ungültige Anmeldedaten');
        }
        
        // Redirect
        $redirectTo = $_SESSION['admin_redirect_after_login'] ?? 'index.php';
        unset($_SESSION['admin_redirect_after_login']);
        
        header('Location: ' . $redirectTo);
        exit;
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
        logWarning('Admin login failed', [
            'email' => $email ?? 'unknown',
            'error' => $error,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#ffffff">
    <title>Admin Login - Tierphysio Manager 🐾</title>
    
    <!-- Modern Font Stack -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main Theme CSS -->
    <link href="../assets/css/main.css" rel="stylesheet">
    
    <!-- Bootstrap for compatibility -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Theme Manager Script -->
    <script src="../assets/js/theme.js"></script>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #7B5BBE, #B9A9D0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.5s ease;
        }
        
        [data-theme="dark"] body {
            background: linear-gradient(135deg, #3d2e5f, #1E1B24);
        }
        
        .login-card {
            max-width: 420px;
            width: 100%;
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
            color: white;
            animation: pulse 2s infinite;
        }
        
        .logo-section h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-text, #2d2d2d);
            margin-bottom: 0.5rem;
        }
        
        .logo-section p {
            color: var(--color-text-muted, #6b7280);
            font-size: 0.875rem;
        }
        
        .form-control {
            background: var(--color-bg, #ffffff);
            border: 2px solid var(--color-border, #e4e4e7);
            color: var(--color-text, #2d2d2d);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #7B5BBE;
            box-shadow: 0 0 0 4px rgba(123, 91, 190, 0.1);
            outline: none;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #7B5BBE, #B9A9D0);
            border: none;
            padding: 0.875rem;
            font-weight: 500;
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
        
        .theme-toggle-admin {
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
        
        .theme-toggle-admin:hover {
            background: var(--color-primary, #7B5BBE);
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(123, 91, 190, 0.3);
        }
    </style>
</head>
<body>
    <!-- Theme Toggle Button -->
    <button class="theme-toggle-admin" data-theme-toggle aria-label="Toggle theme">🌙</button>
    
    <div class="login-card">
        <div class="logo-section">
            <div class="logo-icon">
                <span>🐾</span>
            </div>
            <h1>Admin Panel</h1>
            <p>Tierphysio Manager - Verwaltungsbereich</p>
        </div>
        
        <?php if ($timeout): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-clock me-2"></i>
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
        
        <form method="post" action="login.php">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label for="email" class="form-label">E-Mail-Adresse</label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       required 
                       autofocus
                       value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Passwort</label>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-login w-100 text-white">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Anmelden
            </button>
        </form>
        
        <hr class="my-4">
        
        <div class="text-center">
            <a href="../dashboard.php" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i>
                Zurück zur Hauptanwendung
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
