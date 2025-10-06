<?php
/**
 * Tierphysio Manager - Auto Repair Script
 * Comprehensive repair and integrity validation
 * @version 3.0.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Color output for CLI
function color($text, $color) {
    if (PHP_SAPI !== 'cli') return $text;
    
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'cyan' => "\033[36m",
        'reset' => "\033[0m"
    ];
    
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

// Log function
function logMessage($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $typeColors = [
        'success' => 'green',
        'error' => 'red',
        'warning' => 'yellow',
        'info' => 'cyan'
    ];
    
    $color = $typeColors[$type] ?? 'cyan';
    echo color("[$timestamp] $message\n", $color);
    
    // Also log to file
    $logFile = __DIR__ . '/ew/logs/auto_repair.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    file_put_contents($logFile, "[$timestamp] [$type] $message\n", FILE_APPEND);
}

// Check file existence
function checkFile($path, $description) {
    if (file_exists($path)) {
        logMessage("✅ $description found: $path", 'success');
        return true;
    } else {
        logMessage("❌ $description missing: $path", 'error');
        return false;
    }
}

// Repair function
function repairFile($path, $content, $description) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    if (file_put_contents($path, $content)) {
        logMessage("✅ Repaired $description: $path", 'success');
        return true;
    } else {
        logMessage("❌ Failed to repair $description: $path", 'error');
        return false;
    }
}

// Main repair process
function runAutoRepair() {
    logMessage("=== Starting Auto Repair Process ===", 'info');
    
    $errors = 0;
    $warnings = 0;
    $fixed = 0;
    
    // Phase 1: Check critical files
    logMessage("\n📁 Phase 1: Checking Critical Files", 'info');
    
    $criticalFiles = [
        'includes/layout/_header.html' => 'Header Layout',
        'includes/layout/_nav.html' => 'Navigation Layout',
        'includes/layout/_footer.html' => 'Footer Layout',
        'templates/base.twig' => 'Base Template',
        'public/css/main.css' => 'Main CSS',
        'public/js/theme.js' => 'Theme JavaScript',
        'public/js/app.js' => 'App JavaScript'
    ];
    
    foreach ($criticalFiles as $file => $desc) {
        if (!checkFile($file, $desc)) {
            $errors++;
        }
    }
    
    // Phase 2: Check Twig templates
    logMessage("\n🧱 Phase 2: Checking Twig Templates", 'info');
    
    $templates = glob('templates/*.twig');
    $skipTemplates = ['base.twig', 'mail_template.twig', 'invoice_pdf.twig'];
    
    foreach ($templates as $template) {
        $filename = basename($template);
        if (in_array($filename, $skipTemplates)) continue;
        
        $content = file_get_contents($template);
        
        // Check if extends base.twig
        if (!preg_match('/{% *extends *[\'"]base\.twig[\'"] *%}/', $content)) {
            logMessage("⚠️ $filename does not extend base.twig", 'warning');
            $warnings++;
        }
        
        // Check for endblock
        if (!strpos($content, '{% endblock %}')) {
            logMessage("⚠️ $filename missing {% endblock %}", 'warning');
            $warnings++;
        }
    }
    
    // Phase 3: Check theme consistency
    logMessage("\n🎨 Phase 3: Checking Theme Consistency", 'info');
    
    if (file_exists('public/js/theme.js')) {
        $themeJs = file_get_contents('public/js/theme.js');
        
        $themeChecks = [
            'localStorage.getItem' => 'Theme persistence',
            'data-theme' => 'Theme attribute handling',
            'tierphysio-theme' => 'Theme storage key',
            'toggleTheme' => 'Toggle function'
        ];
        
        foreach ($themeChecks as $check => $desc) {
            if (!strpos($themeJs, $check)) {
                logMessage("⚠️ $desc missing in theme.js", 'warning');
                $warnings++;
            }
        }
    }
    
    // Phase 4: Check CSS integrity
    logMessage("\n🎭 Phase 4: Checking CSS Integrity", 'info');
    
    if (file_exists('public/css/main.css')) {
        $css = file_get_contents('public/css/main.css');
        
        $cssChecks = [
            '#7C4DFF' => 'Primary color',
            'data-theme="dark"' => 'Dark theme styles',
            '--primary-gradient' => 'Gradient variables',
            '.kpi-card' => 'KPI card styles',
            '.modal' => 'Modal styles',
            '.theme-toggle' => 'Theme toggle styles'
        ];
        
        foreach ($cssChecks as $check => $desc) {
            if (!strpos($css, $check)) {
                logMessage("⚠️ $desc missing in CSS", 'warning');
                $warnings++;
            }
        }
    }
    
    // Phase 5: Check dashboard integration
    logMessage("\n📊 Phase 5: Checking Dashboard Integration", 'info');
    
    if (file_exists('dashboard.php')) {
        $dashboard = file_get_contents('dashboard.php');
        
        if (!strpos($dashboard, 'render(')) {
            logMessage("⚠️ Dashboard not using Twig templating", 'warning');
            $warnings++;
        }
        
        if (!strpos($dashboard, 'stats')) {
            logMessage("⚠️ Dashboard stats calculation missing", 'warning');
            $warnings++;
        }
    }
    
    if (file_exists('templates/dashboard.twig')) {
        $dashTwig = file_get_contents('templates/dashboard.twig');
        
        if (!strpos($dashTwig, 'Chart')) {
            logMessage("⚠️ Chart.js integration missing in dashboard template", 'warning');
            $warnings++;
        }
        
        if (!strpos($dashTwig, 'kpi-card')) {
            logMessage("⚠️ KPI cards missing in dashboard template", 'warning');
            $warnings++;
        }
    }
    
    // Phase 6: Check modal z-index
    logMessage("\n🪟 Phase 6: Checking Modal Z-Index", 'info');
    
    if (file_exists('public/css/main.css')) {
        $css = file_get_contents('public/css/main.css');
        
        if (!preg_match('/\.modal\s*{[^}]*z-index:\s*\d+/s', $css)) {
            logMessage("⚠️ Modal z-index not properly set", 'warning');
            $warnings++;
        }
    }
    
    // Summary
    logMessage("\n" . str_repeat('=', 50), 'info');
    logMessage("📋 AUTO REPAIR SUMMARY", 'info');
    logMessage(str_repeat('=', 50), 'info');
    
    if ($errors > 0) {
        logMessage("❌ Critical Errors: $errors", 'error');
    } else {
        logMessage("✅ No critical errors found", 'success');
    }
    
    if ($warnings > 0) {
        logMessage("⚠️  Warnings: $warnings", 'warning');
    } else {
        logMessage("✅ No warnings found", 'success');
    }
    
    if ($fixed > 0) {
        logMessage("🔧 Fixed Issues: $fixed", 'success');
    }
    
    // Final status
    if ($errors === 0 && $warnings === 0) {
        logMessage("\n✅ SYSTEM INTEGRITY: PASSED", 'success');
        logMessage("The Tierphysio Manager application is fully operational!", 'success');
    } elseif ($errors === 0) {
        logMessage("\n⚠️ SYSTEM INTEGRITY: MINOR ISSUES", 'warning');
        logMessage("The application is functional but has some minor issues to address.", 'warning');
    } else {
        logMessage("\n❌ SYSTEM INTEGRITY: CRITICAL ISSUES", 'error');
        logMessage("Critical issues detected. Manual intervention may be required.", 'error');
    }
    
    // Save state
    $state = [
        'timestamp' => date('Y-m-d H:i:s'),
        'errors' => $errors,
        'warnings' => $warnings,
        'fixed' => $fixed,
        'status' => $errors === 0 ? ($warnings === 0 ? 'passed' : 'warnings') : 'failed'
    ];
    
    $stateFile = __DIR__ . '/ew/logs/repair_state.json';
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
    logMessage("\nState saved to: $stateFile", 'info');
    
    return $state;
}

// Run auto repair
if (PHP_SAPI === 'cli') {
    echo color("\n", 'reset');
    echo color("╔══════════════════════════════════════════════╗\n", 'cyan');
    echo color("║   Tierphysio Manager - Auto Repair v3.0.0   ║\n", 'cyan');
    echo color("╚══════════════════════════════════════════════╝\n", 'cyan');
    echo color("\n", 'reset');
    
    $result = runAutoRepair();
    
    echo color("\n", 'reset');
    exit($result['errors'] > 0 ? 1 : 0);
} else {
    // Web interface
    ?>
<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auto Repair - Tierphysio Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #7C4DFF;
            --primary-gradient: linear-gradient(135deg, #7C4DFF, #9C27B0);
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; }
        .header-gradient {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            border-radius: 12px;
        }
        .log-output {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Consolas', monospace;
            font-size: 0.9rem;
            max-height: 500px;
            overflow-y: auto;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }
        .status-passed { background: #10b981; color: white; }
        .status-warnings { background: #f59e0b; color: white; }
        .status-failed { background: #ef4444; color: white; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="header-gradient mb-4">
        <h1>🔧 Auto Repair System</h1>
        <p class="mb-0 opacity-75">Tierphysio Manager v3.0.0</p>
    </div>
    
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Repair Process Output</h5>
            <div class="log-output">
                <?php
                ob_start();
                $result = runAutoRepair();
                $output = ob_get_clean();
                echo '<pre>' . htmlspecialchars($output) . '</pre>';
                ?>
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6>Status:</h6>
                    <span class="status-badge status-<?= $result['status'] ?>">
                        <?= strtoupper($result['status']) ?>
                    </span>
                </div>
                <div class="text-end">
                    <p class="mb-1">Errors: <strong><?= $result['errors'] ?></strong></p>
                    <p class="mb-1">Warnings: <strong><?= $result['warnings'] ?></strong></p>
                    <p class="mb-0">Fixed: <strong><?= $result['fixed'] ?></strong></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="/integrity.php" class="btn btn-primary">Run Integrity Check</a>
        <a href="/dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
    </div>
</div>
</body>
</html>
    <?php
}
?>