<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

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
        
        // Admin-User aus DB laden
        $stmt = $pdo->prepare("
            SELECT id, email, password, name, is_active, is_super_admin,
                   failed_login_attempts, locked_until
            FROM admin_users 
            WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new RuntimeException('Ungültige Anmeldedaten');
        }
        
        // Account gesperrt?
        if ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
            throw new RuntimeException('Account gesperrt bis ' . date('H:i:s', strtotime($user['locked_until'])));
        }
        
        // Account aktiv?
        if (!$user['is_active']) {
            throw new RuntimeException('Account ist deaktiviert');
        }
        
        // Passwort prüfen
        if (!auth_verify_password($password, $user['password'])) {
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
            
            throw new RuntimeException('Ungültige Anmeldedaten');
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
    <title>Admin Login - Tierphysio</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            max-width: 420px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 3rem;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-section img {
            height: 64px;
            margin-bottom: 1rem;
        }
        
        .logo-section h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .logo-section p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-section">
            <img src="../assets/images/logo-icon.png" alt="Tierphysio Logo">
            <h1>Admin Panel</h1>
            <p>Bitte melden Sie sich an</p>
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
