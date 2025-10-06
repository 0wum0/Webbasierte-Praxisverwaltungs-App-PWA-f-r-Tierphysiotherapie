<?php
declare(strict_types=1);

/**
 * Unified Installer & Updater for Tierphysio Manager
 * 
 * This intelligent system handles both fresh installations and updates
 * by detecting the current database version and running necessary migrations
 */

// Error reporting for debugging
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Session configuration
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => ($_SERVER['HTTPS'] ?? '') === 'on',
    'cookie_samesite' => 'Strict',
]);

// Include version information
require_once dirname(__DIR__) . '/includes/version.php';

// Define installation lock file location (in includes folder for persistence)
$installLockFile = dirname(__DIR__) . '/includes/install.lock';
$configFile = dirname(__DIR__) . '/includes/config.php';

// Helper Functions
function generateCsrfToken(): string {
    if (!isset($_SESSION['installer_csrf_token'])) {
        $_SESSION['installer_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['installer_csrf_token'];
}

function validateCsrfToken(): bool {
    $sessionToken = $_SESSION['installer_csrf_token'] ?? null;
    $requestToken = $_POST['csrf_token'] ?? null;
    
    if ($sessionToken === null || $requestToken === null) {
        return false;
    }
    
    return hash_equals($sessionToken, $requestToken);
}

function getCsrfField(): string {
    $token = generateCsrfToken();
    return sprintf('<input type="hidden" name="csrf_token" value="%s">', htmlspecialchars($token, ENT_QUOTES, 'UTF-8'));
}

// Check current installation status
$isInstalled = file_exists($installLockFile);
$hasConfig = file_exists($configFile);
$currentDbVersion = null;
$updateAvailable = false;
$mode = 'install'; // 'install' or 'update'

// If config exists, check database version
if ($hasConfig && $isInstalled) {
    try {
        require_once $configFile;
        require_once dirname(__DIR__) . '/includes/db.php';
        
        // Check if system_info table exists and get version
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_info'");
        if ($stmt->fetch()) {
            $versionStmt = $pdo->prepare("SELECT key_value FROM system_info WHERE key_name = 'db_version'");
            $versionStmt->execute();
            $currentDbVersion = $versionStmt->fetchColumn() ?: '0.0.0';
            
            // Check if update is available
            if (isUpdateAvailable($currentDbVersion)) {
                $updateAvailable = true;
                $mode = 'update';
            }
        } else {
            // system_info table doesn't exist, needs migration
            $currentDbVersion = '0.0.0';
            $updateAvailable = true;
            $mode = 'update';
        }
    } catch (Exception $e) {
        // Database connection failed or other error
        $dbError = $e->getMessage();
    }
}

// Handle POST requests
$error = null;
$success = null;
$action = $_GET['action'] ?? ($mode === 'update' ? 'check' : 'welcome');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = 'Ungültiges CSRF-Token. Bitte versuchen Sie es erneut.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'start_update':
                if ($updateAvailable) {
                    $_SESSION['update_confirmed'] = true;
                    header('Location: ?action=run_update');
                    exit;
                }
                break;
                
            case 'run_migrations':
                try {
                    require_once $configFile;
                    require_once dirname(__DIR__) . '/includes/db.php';
                    
                    $migrationsDir = __DIR__ . '/migrations';
                    $migrationFiles = glob($migrationsDir . '/*.sql');
                    sort($migrationFiles); // Sort by filename (numbered)
                    
                    $executedMigrations = [];
                    $errors = [];
                    
                    // Get already executed migrations
                    try {
                        $stmt = $pdo->query("SELECT filename FROM migration_log WHERE status = 'success'");
                        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($executed as $file) {
                            $executedMigrations[basename($file)] = true;
                        }
                    } catch (Exception $e) {
                        // migration_log table might not exist yet
                    }
                    
                    // Ensure migration_log table exists first
                    try {
                        $pdo->exec("CREATE TABLE IF NOT EXISTS migration_log (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            filename VARCHAR(255) NOT NULL,
                            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            execution_time FLOAT,
                            status ENUM('success', 'failed') DEFAULT 'success',
                            error_message TEXT,
                            UNIQUE KEY unique_filename (filename)
                        )");
                    } catch (Exception $e) {
                        // Table might already exist, continue
                    }
                    
                    // Run each migration
                    foreach ($migrationFiles as $file) {
                        $filename = basename($file);
                        
                        // Skip already executed migrations
                        if (isset($executedMigrations[$filename])) {
                            continue;
                        }
                        
                        $startTime = microtime(true);
                        
                        try {
                            $sql = file_get_contents($file);
                            
                            // Split by semicolon but not within quotes
                            $statements = preg_split('/;(?=(?:[^\'"]|\'[^\']*\'|"[^"]*")*$)/', $sql);
                            
                            $pdo->beginTransaction();
                            
                            foreach ($statements as $statement) {
                                $statement = trim($statement);
                                if (!empty($statement)) {
                                    $pdo->exec($statement);
                                }
                            }
                            
                            $pdo->commit();
                            
                            $executionTime = microtime(true) - $startTime;
                            
                            // Log successful migration (table might not exist for first migration)
                            try {
                                $stmt = $pdo->prepare("
                                    INSERT INTO migration_log (filename, version, execution_time, status) 
                                    VALUES (:filename, :version, :time, 'success')
                                    ON DUPLICATE KEY UPDATE 
                                        executed_at = NOW(),
                                        execution_time = :time,
                                        status = 'success'
                                ");
                                $stmt->execute([
                                    ':filename' => $filename,
                                    ':version' => APP_VERSION,
                                    ':time' => $executionTime
                                ]);
                            } catch (Exception $e) {
                                // Ignore logging errors for first migration
                            }
                            
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $errors[] = "Migration $filename failed: " . $e->getMessage();
                            
                            // Try to log the error
                            try {
                                $stmt = $pdo->prepare("
                                    INSERT INTO migration_log (filename, version, status, error_message) 
                                    VALUES (:filename, :version, 'failed', :error)
                                    ON DUPLICATE KEY UPDATE 
                                        status = 'failed',
                                        error_message = :error
                                ");
                                $stmt->execute([
                                    ':filename' => $filename,
                                    ':version' => APP_VERSION,
                                    ':error' => $e->getMessage()
                                ]);
                            } catch (Exception $logError) {
                                // Ignore logging errors
                            }
                        }
                    }
                    
                    if (empty($errors)) {
                        // Ensure system_info table exists
                        $pdo->exec("CREATE TABLE IF NOT EXISTS system_info (
                            key_name VARCHAR(50) PRIMARY KEY,
                            key_value VARCHAR(255),
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        )");
                        
                        // Update or insert database version
                        $stmt = $pdo->prepare("
                            INSERT INTO system_info (key_name, key_value, updated_at) 
                            VALUES ('db_version', :version, NOW())
                            ON DUPLICATE KEY UPDATE 
                                key_value = :version,
                                updated_at = NOW()
                        ");
                        $stmt->execute([':version' => APP_VERSION]);
                        
                        // Update or create lock file
                        $lockContent = "installed:" . date('Y-m-d H:i:s') . PHP_EOL;
                        $lockContent .= "version:" . APP_VERSION . PHP_EOL;
                        $lockContent .= "updated:" . date('Y-m-d H:i:s') . PHP_EOL;
                        file_put_contents($installLockFile, $lockContent);
                        
                        $_SESSION['update_completed'] = true;
                        $success = 'Update erfolgreich abgeschlossen!';
                        header('Location: ?action=complete');
                        exit;
                    } else {
                        $error = implode('<br>', $errors);
                    }
                    
                } catch (Exception $e) {
                    $error = 'Fehler bei der Migration: ' . $e->getMessage();
                }
                break;
                
            case 'fresh_install':
                // Run fresh installation process
                header('Location: installer.php');
                exit;
                break;
        }
    }
}

// Get migration status for display
$pendingMigrations = [];
$completedMigrations = [];

if ($hasConfig && $mode === 'update') {
    try {
        require_once $configFile;
        require_once dirname(__DIR__) . '/includes/db.php';
        
        $migrationsDir = __DIR__ . '/migrations';
        $migrationFiles = glob($migrationsDir . '/*.sql');
        sort($migrationFiles);
        
        // Get executed migrations
        try {
            $stmt = $pdo->query("SELECT filename, executed_at, status FROM migration_log ORDER BY executed_at DESC");
            $executed = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($executed as $row) {
                $completedMigrations[$row['filename']] = $row;
            }
        } catch (Exception $e) {
            // Table might not exist
        }
        
        // Check which migrations are pending
        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            if (!isset($completedMigrations[$filename]) || $completedMigrations[$filename]['status'] !== 'success') {
                $pendingMigrations[] = $filename;
            }
        }
    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $mode === 'update' ? 'System-Update' : 'Installation' ?> - Tierphysio Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .installer-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            max-width: 800px;
            width: 100%;
            margin: 2rem;
            overflow: hidden;
        }
        
        .installer-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2.5rem;
            text-align: center;
        }
        
        .installer-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        
        .installer-header p {
            opacity: 0.9;
            margin: 0;
        }
        
        .installer-body {
            padding: 2.5rem;
        }
        
        .version-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            margin-top: 1rem;
        }
        
        .migration-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .migration-item.completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .migration-item.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn-gradient {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .status-icon {
            font-size: 1.5rem;
        }
        
        .update-info {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem 1.5rem;
            border-radius: 0 8px 8px 0;
            margin-bottom: 1.5rem;
        }
        
        .changelog-list {
            list-style: none;
            padding: 0;
        }
        
        .changelog-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: #f9fafb;
            border-radius: 6px;
            border-left: 3px solid #8b5cf6;
        }
        
        .spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .progress-animated {
            animation: progress 2s ease-in-out infinite;
        }
        
        @keyframes progress {
            0% { width: 0%; }
            50% { width: 100%; }
            100% { width: 0%; }
        }
    </style>
</head>
<body>
    <div class="installer-card">
        <div class="installer-header">
            <h1>
                <span>🐾</span>
                <span>Tierphysio Manager</span>
            </h1>
            <p><?= $mode === 'update' ? 'System-Update' : 'Installation' ?></p>
            <?php if ($currentDbVersion): ?>
            <div class="version-badge">
                Aktuelle Version: <?= htmlspecialchars($currentDbVersion) ?>
                <?php if ($updateAvailable): ?>
                → Neue Version: <?= APP_VERSION ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="installer-body">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($action === 'check' && $updateAvailable): ?>
                <!-- Update Available -->
                <div class="update-info">
                    <h5><i class="bi bi-info-circle me-2"></i>Update verfügbar!</h5>
                    <p class="mb-0">
                        Eine neue Version (<?= APP_VERSION ?>) ist verfügbar. 
                        Ihre aktuelle Version ist <?= htmlspecialchars($currentDbVersion ?: 'unbekannt') ?>.
                    </p>
                </div>
                
                <?php if (!empty($pendingMigrations)): ?>
                <h5 class="mb-3">Ausstehende Migrationen:</h5>
                <div class="mb-4">
                    <?php foreach ($pendingMigrations as $migration): ?>
                    <div class="migration-item pending">
                        <i class="bi bi-clock-history status-icon"></i>
                        <div>
                            <strong><?= htmlspecialchars($migration) ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <h5 class="mb-3">Changelog:</h5>
                <ul class="changelog-list mb-4">
                    <?php foreach (getChangelog() as $version): ?>
                    <?php if (compareVersions($version['version'], $currentDbVersion ?: '0.0.0') > 0): ?>
                    <li class="changelog-item">
                        <strong><?= $version['icon'] ?> Version <?= htmlspecialchars($version['version']) ?></strong>
                        <small class="text-muted d-block"><?= htmlspecialchars($version['date']) ?></small>
                        <div class="mt-1"><?= htmlspecialchars($version['changes']) ?></div>
                    </li>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                
                <form method="post">
                    <?= getCsrfField() ?>
                    <input type="hidden" name="action" value="start_update">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Wichtig:</strong> Bitte erstellen Sie vor dem Update ein Backup Ihrer Datenbank!
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-gradient">
                            <i class="bi bi-download me-2"></i>Jetzt aktualisieren
                        </button>
                        <a href="../dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Abbrechen
                        </a>
                    </div>
                </form>
                
            <?php elseif ($action === 'run_update'): ?>
                <!-- Running Update -->
                <h4 class="mb-4">Update wird durchgeführt...</h4>
                
                <div class="progress mb-4" style="height: 30px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 100%">
                        Migrationen werden ausgeführt...
                    </div>
                </div>
                
                <form method="post" id="migrationForm">
                    <?= getCsrfField() ?>
                    <input type="hidden" name="action" value="run_migrations">
                </form>
                
                <script>
                    // Auto-submit form after page load
                    setTimeout(function() {
                        document.getElementById('migrationForm').submit();
                    }, 1000);
                </script>
                
            <?php elseif ($action === 'complete'): ?>
                <!-- Update Complete -->
                <div class="text-center">
                    <div class="mb-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="mb-3">Update erfolgreich abgeschlossen!</h3>
                    <p class="text-muted mb-4">
                        Ihr System wurde erfolgreich auf Version <?= APP_VERSION ?> aktualisiert.
                    </p>
                    
                    <h5 class="mb-3">Neue Funktionen:</h5>
                    <ul class="changelog-list text-start mb-4">
                        <?php 
                        $latestVersion = VERSION_HISTORY[APP_VERSION] ?? null;
                        if ($latestVersion): 
                        ?>
                        <li class="changelog-item">
                            <?= match($latestVersion['type']) {
                                'release' => '🚀',
                                'feature' => '✨',
                                'bugfix' => '🐛',
                                'security' => '🔒',
                                default => '📝'
                            } ?> 
                            <?= htmlspecialchars($latestVersion['changes']) ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <a href="../dashboard.php" class="btn btn-gradient btn-lg">
                        <i class="bi bi-speedometer2 me-2"></i>Zum Dashboard
                    </a>
                </div>
                
            <?php elseif (!$isInstalled || !$hasConfig): ?>
                <!-- Fresh Installation Needed -->
                <div class="text-center">
                    <div class="mb-4">
                        <i class="bi bi-box-seam" style="font-size: 4rem; color: #667eea;"></i>
                    </div>
                    <h3 class="mb-3">Willkommen bei Tierphysio Manager</h3>
                    <p class="text-muted mb-4">
                        Das System ist noch nicht installiert. Klicken Sie auf "Installation starten", 
                        um mit der Einrichtung zu beginnen.
                    </p>
                    
                    <div class="alert alert-info text-start">
                        <h6><i class="bi bi-info-circle me-2"></i>Vor der Installation benötigen Sie:</h6>
                        <ul class="mb-0 mt-2">
                            <li>MySQL/MariaDB Datenbankzugangsdaten</li>
                            <li>Informationen zu Ihrer Praxis</li>
                            <li>E-Mail-Adresse für den Admin-Zugang</li>
                        </ul>
                    </div>
                    
                    <form method="post">
                        <?= getCsrfField() ?>
                        <input type="hidden" name="action" value="fresh_install">
                        <button type="submit" class="btn btn-gradient btn-lg">
                            <i class="bi bi-play-circle me-2"></i>Installation starten
                        </button>
                    </form>
                </div>
                
            <?php else: ?>
                <!-- System is up to date -->
                <div class="text-center">
                    <div class="mb-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="mb-3">System ist aktuell</h3>
                    <p class="text-muted mb-4">
                        Ihr System läuft bereits auf der neuesten Version (<?= APP_VERSION ?>).
                    </p>
                    
                    <?php if (!empty($completedMigrations)): ?>
                    <h5 class="mb-3">Ausgeführte Migrationen:</h5>
                    <div class="mb-4">
                        <?php foreach (array_slice($completedMigrations, 0, 5) as $filename => $info): ?>
                        <div class="migration-item completed">
                            <i class="bi bi-check-circle status-icon"></i>
                            <div class="flex-grow-1">
                                <strong><?= htmlspecialchars($filename) ?></strong>
                                <small class="text-muted d-block">
                                    Ausgeführt: <?= htmlspecialchars($info['executed_at']) ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="../dashboard.php" class="btn btn-gradient">
                            <i class="bi bi-speedometer2 me-2"></i>Zum Dashboard
                        </a>
                        <a href="../login.php" class="btn btn-secondary">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Zum Login
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
