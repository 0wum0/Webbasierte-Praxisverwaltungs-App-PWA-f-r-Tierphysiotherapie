<?php
declare(strict_types=1);

/**
 * Multi-Step Installer für Tierphysio Praxis PWA
 * 
 * Dieser Installer führt durch die Ersteinrichtung der Anwendung
 * mit Sicherheitshärtung und Schritt-für-Schritt Prozess
 */

// Session starten für Step-Tracking und CSRF
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => ($_SERVER['HTTPS'] ?? '') === 'on',
    'cookie_samesite' => 'Strict',
]);

// Prüfe ob bereits installiert
$installedLockFile = __DIR__ . '/installed.lock';
if (file_exists($installedLockFile)) {
    header('Location: ../login.php');
    exit('System ist bereits installiert. Bitte verwenden Sie <a href="../login.php">Login</a>.');
}

// Helper-Funktionen
function generateCsrfToken(): string
{
    if (!isset($_SESSION['installer_csrf_token'])) {
        $_SESSION['installer_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['installer_csrf_token'];
}

function validateCsrfToken(): bool
{
    $sessionToken = $_SESSION['installer_csrf_token'] ?? null;
    $requestToken = $_POST['csrf_token'] ?? null;
    
    if ($sessionToken === null || $requestToken === null) {
        return false;
    }
    
    return hash_equals($sessionToken, $requestToken);
}

function getCsrfField(): string
{
    $token = generateCsrfToken();
    return sprintf('<input type="hidden" name="csrf_token" value="%s">', htmlspecialchars($token, ENT_QUOTES, 'UTF-8'));
}

// Aktueller Schritt
$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$currentStep = max(1, min(8, $currentStep)); // Schritt zwischen 1 und 8

// POST-Verarbeitung
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Validierung für alle POST-Anfragen
    if (!validateCsrfToken()) {
        $error = 'Ungültiges CSRF-Token. Bitte versuchen Sie es erneut.';
    } else {
        // Schritt-spezifische Verarbeitung
        switch ($currentStep) {
            case 3: // Database Connection
                $dbHost = trim($_POST['db_host'] ?? '');
                $dbPort = trim($_POST['db_port'] ?? '3306');
                $dbName = trim($_POST['db_name'] ?? '');
                $dbUser = trim($_POST['db_user'] ?? '');
                $dbPass = $_POST['db_pass'] ?? '';
                
                // Validierung
                if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
                    $error = 'Bitte füllen Sie alle Pflichtfelder aus.';
                } else {
                    // Verbindung testen
                    try {
                        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
                        $testPdo = new PDO($dsn, $dbUser, $dbPass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 5,
                        ]);
                        
                        // Erfolgreich - in Session speichern
                        $_SESSION['installer_db'] = [
                            'host' => $dbHost,
                            'port' => $dbPort,
                            'name' => $dbName,
                            'user' => $dbUser,
                            'pass' => $dbPass,
                        ];
                        
                        $success = 'Datenbankverbindung erfolgreich!';
                        header('Location: ?step=4');
                        exit;
                    } catch (PDOException $e) {
                        $error = 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage();
                    }
                }
                break;
                
            case 4: // Write config.php
                if (!isset($_SESSION['installer_db'])) {
                    header('Location: ?step=3');
                    exit;
                }
                
                // Sample config laden
                $sampleConfigPath = dirname(__DIR__) . '/includes/sample.config.php';
                if (!file_exists($sampleConfigPath)) {
                    $error = 'sample.config.php nicht gefunden.';
                } else {
                    $configTemplate = file_get_contents($sampleConfigPath);
                    
                    // Platzhalter ersetzen
                    $db = $_SESSION['installer_db'];
                    $replacements = [
                        '{{DB_HOST}}' => $db['host'],
                        '{{DB_PORT}}' => $db['port'],
                        '{{DB_NAME}}' => $db['name'],
                        '{{DB_USER}}' => $db['user'],
                        '{{DB_PASS}}' => $db['pass'],
                        '{{APP_ENV}}' => 'production',
                        '{{APP_DEBUG}}' => 'false',
                        '{{APP_NAME}}' => 'Tierphysio Praxis PWA',
                    ];
                    
                    $configContent = strtr($configTemplate, $replacements);
                    
                    // Config-Datei schreiben
                    $configPath = dirname(__DIR__) . '/includes/config.php';
                    
                    // Backup erstellen falls vorhanden
                    if (file_exists($configPath)) {
                        $backupPath = $configPath . '.bak.' . date('YmdHis');
                        copy($configPath, $backupPath);
                    }
                    
                    if (file_put_contents($configPath, $configContent) !== false) {
                        $_SESSION['installer_config_written'] = true;
                        $success = 'Konfigurationsdatei erfolgreich geschrieben!';
                        header('Location: ?step=5');
                        exit;
                    } else {
                        $error = 'Fehler beim Schreiben der Konfigurationsdatei. Bitte prüfen Sie die Dateiberechtigungen.';
                    }
                }
                break;
                
            case 5: // Database Schema
                if (!isset($_SESSION['installer_config_written'])) {
                    header('Location: ?step=4');
                    exit;
                }
                
                // Migration ausführen
                try {
                    require_once dirname(__DIR__) . '/includes/config.php';
                    require_once dirname(__DIR__) . '/includes/db.php';
                    
                    // Migrations-Datei einbinden und ausführen
                    $migrationFile = dirname(__DIR__) . '/includes/migrations/20251006_installer_core.php';
                    if (file_exists($migrationFile)) {
                        require_once $migrationFile;
                        // Die Migration-Funktion aufrufen
                        if (function_exists('runInstallerCoreMigration')) {
                            runInstallerCoreMigration($pdo);
                        }
                    }
                    
                    $_SESSION['installer_schema_created'] = true;
                    $success = 'Datenbankschema erfolgreich initialisiert!';
                    header('Location: ?step=6');
                    exit;
                } catch (Exception $e) {
                    $error = 'Fehler bei der Datenbankinitialisierung: ' . $e->getMessage();
                }
                break;
                
            case 6: // Site Settings
                if (!isset($_SESSION['installer_schema_created'])) {
                    header('Location: ?step=5');
                    exit;
                }
                
                $settings = [
                    'site_name' => trim($_POST['site_name'] ?? ''),
                    'practice_name' => trim($_POST['practice_name'] ?? ''),
                    'practice_email' => trim($_POST['practice_email'] ?? ''),
                    'practice_phone' => trim($_POST['practice_phone'] ?? ''),
                    'practice_address' => trim($_POST['practice_address'] ?? ''),
                    'practice_vat' => trim($_POST['practice_vat'] ?? ''),
                    'currency' => $_POST['currency'] ?? 'EUR',
                    'timezone' => $_POST['timezone'] ?? 'Europe/Berlin',
                ];
                
                // Validierung
                if (empty($settings['site_name']) || empty($settings['practice_name'])) {
                    $error = 'Bitte füllen Sie mindestens Site-Name und Praxis-Name aus.';
                } else {
                    try {
                        require_once dirname(__DIR__) . '/includes/config.php';
                        require_once dirname(__DIR__) . '/includes/db.php';
                        
                        // Settings in app_settings Tabelle speichern
                        $stmt = $pdo->prepare("
                            INSERT INTO app_settings (skey, svalue) 
                            VALUES (:key, :value)
                            ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)
                        ");
                        
                        foreach ($settings as $key => $value) {
                            $stmt->execute([':key' => $key, ':value' => $value]);
                        }
                        
                        $_SESSION['installer_settings_saved'] = true;
                        $success = 'Einstellungen erfolgreich gespeichert!';
                        header('Location: ?step=7');
                        exit;
                    } catch (Exception $e) {
                        $error = 'Fehler beim Speichern der Einstellungen: ' . $e->getMessage();
                    }
                }
                break;
                
            case 7: // Create Admin
                if (!isset($_SESSION['installer_settings_saved'])) {
                    header('Location: ?step=6');
                    exit;
                }
                
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $passwordConfirm = $_POST['password_confirm'] ?? '';
                
                // Validierung
                $errors = [];
                if (empty($firstName) || empty($lastName)) {
                    $errors[] = 'Vor- und Nachname sind erforderlich.';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
                }
                if (strlen($password) < 8) {
                    $errors[] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
                }
                if ($password !== $passwordConfirm) {
                    $errors[] = 'Die Passwörter stimmen nicht überein.';
                }
                
                if (!empty($errors)) {
                    $error = implode(' ', $errors);
                } else {
                    try {
                        require_once dirname(__DIR__) . '/includes/config.php';
                        require_once dirname(__DIR__) . '/includes/db.php';
                        
                        // Prüfe ob E-Mail bereits existiert (case-insensitive)
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) FROM admin_users 
                            WHERE LOWER(email) = LOWER(:email)
                        ");
                        $stmt->execute([':email' => $email]);
                        
                        if ($stmt->fetchColumn() > 0) {
                            $error = 'Ein Admin mit dieser E-Mail-Adresse existiert bereits.';
                        } else {
                            // Admin erstellen
                            $displayName = $firstName . ' ' . $lastName;
                            $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO admin_users (email, password_hash, display_name, is_active, created_at)
                                VALUES (:email, :password_hash, :display_name, 1, NOW())
                            ");
                            
                            $stmt->execute([
                                ':email' => $email,
                                ':password_hash' => $passwordHash,
                                ':display_name' => $displayName,
                            ]);
                            
                            $_SESSION['installer_admin_created'] = true;
                            $_SESSION['installer_admin_email'] = $email;
                            $success = 'Admin-Benutzer erfolgreich erstellt!';
                            header('Location: ?step=8');
                            exit;
                        }
                    } catch (Exception $e) {
                        $error = 'Fehler beim Erstellen des Admin-Benutzers: ' . $e->getMessage();
                    }
                }
                break;
                
            case 8: // Finish
                if (!isset($_SESSION['installer_admin_created'])) {
                    header('Location: ?step=7');
                    exit;
                }
                
                // installed.lock erstellen
                if (file_put_contents($installedLockFile, date('Y-m-d H:i:s') . PHP_EOL) !== false) {
                    // Session aufräumen
                    unset(
                        $_SESSION['installer_csrf_token'],
                        $_SESSION['installer_db'],
                        $_SESSION['installer_config_written'],
                        $_SESSION['installer_schema_created'],
                        $_SESSION['installer_settings_saved'],
                        $_SESSION['installer_admin_created'],
                        $_SESSION['installer_admin_email']
                    );
                    
                    $success = 'Installation erfolgreich abgeschlossen!';
                } else {
                    $error = 'Fehler beim Erstellen der Lock-Datei. Bitte prüfen Sie die Dateiberechtigungen.';
                }
                break;
        }
    }
}

// Dependency Checks für Schritt 2
$phpVersion = phpversion();
$phpVersionOk = version_compare($phpVersion, '8.2.0', '>=');

$requiredExtensions = [
    'pdo_mysql' => 'PDO MySQL',
    'mbstring' => 'Multibyte String',
    'json' => 'JSON',
    'openssl' => 'OpenSSL',
    'curl' => 'cURL',
    'fileinfo' => 'File Information',
];

$optionalExtensions = [
    'gd' => 'GD Image Library',
    'intl' => 'Internationalization',
];

$extensionStatus = [];
foreach ($requiredExtensions as $ext => $name) {
    $extensionStatus[$ext] = [
        'name' => $name,
        'installed' => extension_loaded($ext),
        'required' => true,
    ];
}

foreach ($optionalExtensions as $ext => $name) {
    $extensionStatus[$ext] = [
        'name' => $name,
        'installed' => extension_loaded($ext),
        'required' => false,
    ];
}

// Dateiberechtigungen prüfen
$writableDirs = [
    dirname(__DIR__) . '/includes' => 'includes/',
    dirname(__DIR__) . '/install' => 'install/',
    dirname(__DIR__) . '/logs' => 'logs/',
];

$permissionStatus = [];
foreach ($writableDirs as $path => $name) {
    // Erstelle Verzeichnis wenn es nicht existiert
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
    $permissionStatus[$name] = is_writable($path);
}

// Composer Autoload prüfen
$composerPaths = [
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(dirname(__DIR__)) . '/vendor/autoload.php',
];

$composerAvailable = false;
foreach ($composerPaths as $path) {
    if (file_exists($path)) {
        $composerAvailable = true;
        break;
    }
}

// Alle Checks bestanden?
$allChecksPassed = $phpVersionOk;
foreach ($extensionStatus as $ext) {
    if ($ext['required'] && !$ext['installed']) {
        $allChecksPassed = false;
        break;
    }
}
foreach ($permissionStatus as $status) {
    if (!$status) {
        $allChecksPassed = false;
        break;
    }
}

?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation - Tierphysio Praxis PWA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .installer-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .installer-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 2rem;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e5e7eb;
            z-index: 0;
        }
        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            margin: 0 auto 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #6b7280;
        }
        .step-item.active .step-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .step-item.completed .step-circle {
            background: #10b981;
            color: white;
        }
        .step-label {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .step-item.active .step-label {
            color: #111827;
            font-weight: 600;
        }
        .check-item {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        .check-item.success {
            background: #d1fae5;
            color: #065f46;
        }
        .check-item.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .check-item.warning {
            background: #fed7aa;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-card">
            <!-- Progress Indicator -->
            <div class="step-indicator">
                <?php
                $steps = [
                    1 => 'Willkommen',
                    2 => 'Prüfungen',
                    3 => 'Datenbank',
                    4 => 'Konfiguration',
                    5 => 'Schema',
                    6 => 'Einstellungen',
                    7 => 'Admin',
                    8 => 'Fertig'
                ];
                foreach ($steps as $num => $label):
                    $class = '';
                    if ($num < $currentStep) {
                        $class = 'completed';
                    } elseif ($num === $currentStep) {
                        $class = 'active';
                    }
                ?>
                <div class="step-item <?= $class ?>">
                    <div class="step-circle">
                        <?php if ($num < $currentStep): ?>
                            <i class="bi bi-check"></i>
                        <?php else: ?>
                            <?= $num ?>
                        <?php endif; ?>
                    </div>
                    <div class="step-label"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Error/Success Messages -->
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

            <!-- Step Content -->
            <?php if ($currentStep === 1): ?>
            <!-- Step 1: Welcome -->
            <h3 class="mb-3">Willkommen zur Installation</h3>
            <p class="text-muted mb-4">
                Dieser Installationsassistent führt Sie durch die Einrichtung Ihrer 
                Tierphysio Praxis PWA. Die Installation dauert nur wenige Minuten.
            </p>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Vor der Installation benötigen Sie:</strong>
                <ul class="mb-0 mt-2">
                    <li>MySQL/MariaDB Datenbankzugangsdaten</li>
                    <li>Informationen zu Ihrer Praxis</li>
                    <li>E-Mail-Adresse für den Admin-Zugang</li>
                </ul>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <div></div>
                <a href="?step=2" class="btn btn-primary">
                    Weiter <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>

            <?php elseif ($currentStep === 2): ?>
            <!-- Step 2: Dependency Checks -->
            <h3 class="mb-3">Systemprüfung</h3>
            <p class="text-muted mb-4">
                Überprüfung der Systemanforderungen und Abhängigkeiten.
            </p>

            <h5 class="mb-3">PHP Version</h5>
            <div class="check-item <?= $phpVersionOk ? 'success' : 'error' ?>">
                <i class="bi <?= $phpVersionOk ? 'bi-check-circle' : 'bi-x-circle' ?> me-2"></i>
                PHP <?= $phpVersion ?> 
                <?= $phpVersionOk ? '(OK - mindestens 8.2 erforderlich)' : '(Fehler - mindestens PHP 8.2 erforderlich)' ?>
            </div>

            <h5 class="mb-3 mt-4">PHP Erweiterungen</h5>
            <?php foreach ($extensionStatus as $ext): ?>
            <div class="check-item <?= $ext['installed'] ? 'success' : ($ext['required'] ? 'error' : 'warning') ?>">
                <i class="bi <?= $ext['installed'] ? 'bi-check-circle' : 'bi-x-circle' ?> me-2"></i>
                <?= $ext['name'] ?>
                <?php if (!$ext['installed']): ?>
                    <?= $ext['required'] ? '(Erforderlich)' : '(Optional)' ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <h5 class="mb-3 mt-4">Dateiberechtigungen</h5>
            <?php foreach ($permissionStatus as $dir => $writable): ?>
            <div class="check-item <?= $writable ? 'success' : 'error' ?>">
                <i class="bi <?= $writable ? 'bi-check-circle' : 'bi-x-circle' ?> me-2"></i>
                <?= $dir ?> <?= $writable ? 'beschreibbar' : 'nicht beschreibbar' ?>
            </div>
            <?php endforeach; ?>

            <h5 class="mb-3 mt-4">Composer Autoload</h5>
            <div class="check-item <?= $composerAvailable ? 'success' : 'warning' ?>">
                <i class="bi <?= $composerAvailable ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
                Composer Autoload <?= $composerAvailable ? 'verfügbar' : 'nicht gefunden (optional)' ?>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="?step=1" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i> Zurück
                </a>
                <?php if ($allChecksPassed): ?>
                <a href="?step=3" class="btn btn-primary">
                    Weiter <i class="bi bi-arrow-right ms-2"></i>
                </a>
                <?php else: ?>
                <button class="btn btn-danger" disabled>
                    <i class="bi bi-x-circle me-2"></i> Anforderungen nicht erfüllt
                </button>
                <?php endif; ?>
            </div>

            <?php elseif ($currentStep === 3): ?>
            <!-- Step 3: Database Connection -->
            <h3 class="mb-3">Datenbankverbindung</h3>
            <p class="text-muted mb-4">
                Geben Sie die Zugangsdaten für Ihre MySQL/MariaDB Datenbank ein.
            </p>

            <form method="post" action="?step=3">
                <?= getCsrfField() ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="db_host" class="form-label">Datenbank-Host <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="db_host" name="db_host" 
                                   value="<?= htmlspecialchars($_POST['db_host'] ?? $_SESSION['installer_db']['host'] ?? 'localhost', ENT_QUOTES, 'UTF-8') ?>" required>
                            <small class="form-text text-muted">Normalerweise localhost</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="db_port" class="form-label">Port</label>
                            <input type="text" class="form-control" id="db_port" name="db_port" 
                                   value="<?= htmlspecialchars($_POST['db_port'] ?? $_SESSION['installer_db']['port'] ?? '3306', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="db_name" class="form-label">Datenbankname <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="db_name" name="db_name" 
                           value="<?= htmlspecialchars($_POST['db_name'] ?? $_SESSION['installer_db']['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    <small class="form-text text-muted">Die Datenbank muss bereits existieren</small>
                </div>

                <div class="mb-3">
                    <label for="db_user" class="form-label">Benutzername <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="db_user" name="db_user" 
                           value="<?= htmlspecialchars($_POST['db_user'] ?? $_SESSION['installer_db']['user'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="db_pass" class="form-label">Passwort</label>
                    <input type="password" class="form-control" id="db_pass" name="db_pass">
                    <small class="form-text text-muted">Lassen Sie dies leer, wenn kein Passwort erforderlich ist</small>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?step=2" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i> Zurück
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Verbindung testen <i class="bi bi-database ms-2"></i>
                    </button>
                </div>
            </form>

            <?php elseif ($currentStep === 4): ?>
            <!-- Step 4: Write Config -->
            <h3 class="mb-3">Konfiguration erstellen</h3>
            <p class="text-muted mb-4">
                Die Konfigurationsdatei wird jetzt basierend auf Ihren Angaben erstellt.
            </p>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Die Datei <code>includes/config.php</code> wird aus der Vorlage 
                <code>includes/sample.config.php</code> generiert.
            </div>

            <form method="post" action="?step=4">
                <?= getCsrfField() ?>
                
                <p>Klicken Sie auf "Konfiguration schreiben" um fortzufahren.</p>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?step=3" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i> Zurück
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Konfiguration schreiben <i class="bi bi-file-earmark-code ms-2"></i>
                    </button>
                </div>
            </form>

            <?php elseif ($currentStep === 5): ?>
            <!-- Step 5: Database Schema -->
            <h3 class="mb-3">Datenbankschema initialisieren</h3>
            <p class="text-muted mb-4">
                Die erforderlichen Datenbanktabellen werden jetzt erstellt.
            </p>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Folgende Tabellen werden erstellt (falls nicht vorhanden):
                <ul class="mb-0 mt-2">
                    <li><code>admin_users</code> - Admin-Benutzer</li>
                    <li><code>app_settings</code> - Anwendungseinstellungen</li>
                    <li>Sowie alle anderen erforderlichen Tabellen</li>
                </ul>
            </div>

            <form method="post" action="?step=5">
                <?= getCsrfField() ?>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?step=4" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i> Zurück
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Schema erstellen <i class="bi bi-diagram-3 ms-2"></i>
                    </button>
                </div>
            </form>

            <?php elseif ($currentStep === 6): ?>
            <!-- Step 6: Site Settings -->
            <h3 class="mb-3">Praxis-Einstellungen</h3>
            <p class="text-muted mb-4">
                Konfigurieren Sie die grundlegenden Einstellungen für Ihre Praxis.
            </p>

            <form method="post" action="?step=6">
                <?= getCsrfField() ?>

                <div class="mb-3">
                    <label for="site_name" class="form-label">Site-Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="site_name" name="site_name" 
                           value="<?= htmlspecialchars($_POST['site_name'] ?? 'Tierphysio Praxis PWA', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="practice_name" class="form-label">Praxis-Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="practice_name" name="practice_name" 
                           value="<?= htmlspecialchars($_POST['practice_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="practice_email" class="form-label">E-Mail-Adresse</label>
                            <input type="email" class="form-control" id="practice_email" name="practice_email" 
                                   value="<?= htmlspecialchars($_POST['practice_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="practice_phone" class="form-label">Telefonnummer</label>
                            <input type="text" class="form-control" id="practice_phone" name="practice_phone" 
                                   value="<?= htmlspecialchars($_POST['practice_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="practice_address" class="form-label">Adresse</label>
                    <textarea class="form-control" id="practice_address" name="practice_address" rows="2"><?= htmlspecialchars($_POST['practice_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="practice_vat" class="form-label">USt-IdNr.</label>
                    <input type="text" class="form-control" id="practice_vat" name="practice_vat" 
                           value="<?= htmlspecialchars($_POST['practice_vat'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="currency" class="form-label">Währung</label>
                            <select class="form-select" id="currency" name="currency">
                                <option value="EUR" <?= ($_POST['currency'] ?? 'EUR') === 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
                                <option value="CHF" <?= ($_POST['currency'] ?? '') === 'CHF' ? 'selected' : '' ?>>CHF</option>
                                <option value="USD" <?= ($_POST['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Zeitzone</label>
                            <select class="form-select" id="timezone" name="timezone">
                                <option value="Europe/Berlin" <?= ($_POST['timezone'] ?? 'Europe/Berlin') === 'Europe/Berlin' ? 'selected' : '' ?>>Europe/Berlin</option>
                                <option value="Europe/Vienna" <?= ($_POST['timezone'] ?? '') === 'Europe/Vienna' ? 'selected' : '' ?>>Europe/Vienna</option>
                                <option value="Europe/Zurich" <?= ($_POST['timezone'] ?? '') === 'Europe/Zurich' ? 'selected' : '' ?>>Europe/Zurich</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?step=5" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i> Zurück
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Einstellungen speichern <i class="bi bi-save ms-2"></i>
                    </button>
                </div>
            </form>

            <?php elseif ($currentStep === 7): ?>
            <!-- Step 7: Create Admin -->
            <h3 class="mb-3">Admin-Benutzer erstellen</h3>
            <p class="text-muted mb-4">
                Erstellen Sie den ersten Administrator-Account für die Anwendung.
            </p>

            <form method="post" action="?step=7">
                <?= getCsrfField() ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Vorname <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nachname <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail-Adresse <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    <small class="form-text text-muted">Diese E-Mail-Adresse wird für die Anmeldung verwendet</small>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Passwort <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <small class="form-text text-muted">Mindestens 8 Zeichen</small>
                </div>

                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Passwort bestätigen <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?step=6" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i> Zurück
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Admin erstellen <i class="bi bi-person-plus ms-2"></i>
                    </button>
                </div>
            </form>

            <?php elseif ($currentStep === 8): ?>
            <!-- Step 8: Finish -->
            <h3 class="mb-3">
                <i class="bi bi-check-circle text-success me-2"></i>
                Installation abgeschlossen!
            </h3>
            <p class="text-muted mb-4">
                Die Installation wurde erfolgreich abgeschlossen. Sie können sich jetzt anmelden.
            </p>

            <div class="alert alert-success">
                <h5 class="alert-heading">Ihre Zugangsdaten:</h5>
                <p class="mb-0">
                    <strong>E-Mail:</strong> <?= htmlspecialchars($_SESSION['installer_admin_email'] ?? 'admin@example.com', ENT_QUOTES, 'UTF-8') ?><br>
                    <strong>Passwort:</strong> Das von Ihnen festgelegte Passwort
                </p>
            </div>

            <div class="alert alert-warning">
                <i class="bi bi-shield-lock me-2"></i>
                <strong>Sicherheitshinweis:</strong> Der Installer ist jetzt gesperrt. 
                Um eine Neuinstallation durchzuführen, müssen Sie die Datei 
                <code>install/installed.lock</code> manuell löschen.
            </div>

            <form method="post" action="?step=8">
                <?= getCsrfField() ?>

                <div class="d-flex justify-content-center mt-4">
                    <a href="../login.php" class="btn btn-success btn-lg">
                        Zur Anmeldung <i class="bi bi-box-arrow-in-right ms-2"></i>
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>