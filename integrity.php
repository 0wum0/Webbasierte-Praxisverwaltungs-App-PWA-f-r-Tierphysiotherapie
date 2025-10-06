<?php
/**
 * Tierphysio Manager – Integrity Check System
 * Auto-repair mode implementation
 * @version 3.0.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Parse command line arguments
$mode = isset($argv[1]) && $argv[1] === '--mode=auto' ? 'auto' : 'html';

// Base directory
$baseDir = __DIR__;

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

// Check file existence
function checkFile($path, $label) {
    global $mode;
    $exists = file_exists($path);
    
    if ($mode === 'auto') {
        if ($exists) {
            echo color("✅ $label found at $path\n", 'green');
        } else {
            echo color("❌ $label missing at $path\n", 'red');
        }
    }
    
    return [
        'label' => $label,
        'path' => $path,
        'status' => $exists ? 'ok' : 'error',
        'message' => $exists ? 'Found' : 'Missing'
    ];
}

// Check pattern in file
function checkPattern($path, $pattern, $desc) {
    global $mode;
    
    if (!file_exists($path)) {
        if ($mode === 'auto') {
            echo color("❌ File missing: $path\n", 'red');
        }
        return false;
    }
    
    $content = file_get_contents($path);
    $found = preg_match($pattern, $content);
    
    if ($mode === 'auto') {
        if ($found) {
            echo color("✅ $desc in $path\n", 'green');
        } else {
            echo color("⚠️  $desc missing in $path\n", 'yellow');
        }
    }
    
    return $found;
}

// Find files recursively
function findFiles($dir, $extensions = ['php', 'twig', 'css', 'js']) {
    $files = [];
    if (!is_dir($dir)) return $files;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = pathinfo($file->getPathname(), PATHINFO_EXTENSION);
            if (in_array($ext, $extensions)) {
                $files[] = $file->getPathname();
            }
        }
    }
    
    return $files;
}

// Generate HTML badge
function badge($status) {
    switch ($status) {
        case 'ok': return '<span class="badge bg-success">✅ OK</span>';
        case 'warn': return '<span class="badge bg-warning text-dark">⚠️ Warning</span>';
        case 'error': return '<span class="badge bg-danger">❌ Error</span>';
    }
}

// Initialize check results
$checks = [];
$warnings = [];
$errors = [];

// === PHASE 1: DIRECTORY STRUCTURE CHECK ===
if ($mode === 'auto') {
    echo "\n" . color("=== PHASE 1: Directory Structure Check ===\n", 'cyan');
}

$requiredFiles = [
    'Header Layout' => 'includes/layout/_header.html',
    'Navigation Layout' => 'includes/layout/_nav.html',
    'Footer Layout' => 'includes/layout/_footer.html',
    'Base Template' => 'templates/base.twig',
    'Main CSS' => 'public/css/main.css',
    'Theme JS' => 'public/js/theme.js',
    'App JS' => 'public/js/app.js',
    'Version File' => 'includes/version.php'
];

foreach ($requiredFiles as $label => $path) {
    $checks[] = checkFile("$baseDir/$path", $label);
}

// === PHASE 2: THEME CONSISTENCY CHECK ===
if ($mode === 'auto') {
    echo "\n" . color("=== PHASE 2: Theme Consistency Check ===\n", 'cyan');
}

// Check theme.js
$themeWarnings = [];
if (file_exists("$baseDir/public/js/theme.js")) {
    $theme = file_get_contents("$baseDir/public/js/theme.js");
    
    if (!strpos($theme, 'localStorage.getItem')) {
        $themeWarnings[] = 'Theme persistence via localStorage missing';
    }
    if (!strpos($theme, 'data-theme')) {
        $themeWarnings[] = 'data-theme attribute handling missing';
    }
    if (!strpos($theme, 'tierphysio-theme')) {
        $themeWarnings[] = 'Theme storage key missing';
    }
}

// Check CSS
$cssWarnings = [];
if (file_exists("$baseDir/public/css/main.css")) {
    $css = file_get_contents("$baseDir/public/css/main.css");
    
    if (!strpos($css, '#7C4DFF')) {
        $cssWarnings[] = 'Primary color (#7C4DFF) not found';
    }
    if (!strpos($css, 'data-theme="dark"')) {
        $cssWarnings[] = 'Dark theme styles missing';
    }
    if (!strpos($css, '--primary-gradient')) {
        $cssWarnings[] = 'Gradient variables missing';
    }
}

// === PHASE 3: HEADER INTEGRITY CHECK ===
if ($mode === 'auto') {
    echo "\n" . color("=== PHASE 3: Header Integrity Check ===\n", 'cyan');
}

$headerWarnings = [];
if (file_exists("$baseDir/includes/layout/_header.html")) {
    $header = file_get_contents("$baseDir/includes/layout/_header.html");
    
    if (!strpos($header, 'topbar')) {
        $headerWarnings[] = 'Topbar class missing';
    }
    if (!strpos($header, 'bi-list')) {
        $headerWarnings[] = 'Burger menu icon missing';
    }
    if (!strpos($header, 'themeToggle')) {
        $headerWarnings[] = 'Theme toggle button missing';
    }
    if (!strpos($header, 'dropdown-menu')) {
        $headerWarnings[] = 'User dropdown menu missing';
    }
    if (!strpos($header, 'globalSearchBtn')) {
        $headerWarnings[] = 'Global search button missing';
    }
}

// === PHASE 4: TWIG TEMPLATE CHECK ===
if ($mode === 'auto') {
    echo "\n" . color("=== PHASE 4: Twig Template Check ===\n", 'cyan');
}

$twigWarnings = [];
$twigFiles = findFiles("$baseDir/templates", ['twig']);
$baseTemplates = ['base.twig', 'mail_template.twig', 'invoice_pdf.twig'];

foreach ($twigFiles as $file) {
    $filename = basename($file);
    
    // Skip base templates
    if (in_array($filename, $baseTemplates)) continue;
    
    $content = file_get_contents($file);
    $relativePath = str_replace($baseDir . '/', '', $file);
    
    // Check if extends base.twig
    if (!preg_match('/{% *extends *[\'"]base\.twig[\'"] *%}/', $content)) {
        $twigWarnings[] = "$relativePath does not extend base.twig";
    }
    
    // Check for endblock
    if (!strpos($content, '{% endblock %}')) {
        $twigWarnings[] = "$relativePath missing {% endblock %}";
    }
}

// === PHASE 5: MODAL Z-INDEX CHECK ===
if ($mode === 'auto') {
    echo "\n" . color("=== PHASE 5: Modal Z-Index Check ===\n", 'cyan');
}

$modalWarnings = [];
$phpFiles = findFiles($baseDir, ['php']);

foreach ($phpFiles as $file) {
    // Skip vendor and test files
    if (strpos($file, '/vendor/') !== false) continue;
    if (strpos($file, '/test/') !== false) continue;
    
    $content = file_get_contents($file);
    $relativePath = str_replace($baseDir . '/', '', $file);
    
    // Check for modal usage without z-index
    if (strpos($content, 'modal') !== false && strpos($content, 'z-index') === false) {
        // Only warn if it's actually a modal dialog
        if (strpos($content, 'modal-dialog') !== false || strpos($content, 'modal fade') !== false) {
            $modalWarnings[] = "$relativePath contains modal without z-index";
        }
    }
}

// === PHASE 6: NAVIGATION CHECK ===
if ($mode === 'auto') {
    echo "\n" . color("=== PHASE 6: Navigation Check ===\n", 'cyan');
}

$navWarnings = [];
if (file_exists("$baseDir/includes/layout/_nav.html")) {
    $nav = file_get_contents("$baseDir/includes/layout/_nav.html");
    
    $requiredLinks = [
        'dashboard.php' => 'Dashboard',
        'patients.php' => 'Patients',
        'appointments.php' => 'Appointments',
        'notes.php' => 'Notes',
        'invoices.php' => 'Invoices',
        'owners.php' => 'Owners',
        'accounting.php' => 'Accounting',
        'settings.php' => 'Settings'
    ];
    
    foreach ($requiredLinks as $link => $label) {
        if (!strpos($nav, $link)) {
            $navWarnings[] = "Link to $link missing in navigation";
        }
    }
}

// === PHASE 7: DASHBOARD KPI CHECK ===
if ($mode === 'auto') {
    echo "\n" . color("=== PHASE 7: Dashboard KPI Check ===\n", 'cyan');
}

$dashboardWarnings = [];
if (file_exists("$baseDir/dashboard.php")) {
    $dashboard = file_get_contents("$baseDir/dashboard.php");
    
    if (!strpos($dashboard, 'Chart')) {
        $dashboardWarnings[] = 'Chart.js integration missing';
    }
    if (!strpos($dashboard, 'income_month')) {
        $dashboardWarnings[] = 'Monthly income calculation missing';
    }
    if (!strpos($dashboard, 'appointments')) {
        $dashboardWarnings[] = 'Appointments display missing';
    }
}

// === SUMMARY ===
$totalWarnings = count($themeWarnings) + count($cssWarnings) + count($headerWarnings) + 
                 count($twigWarnings) + count($modalWarnings) + count($navWarnings) + 
                 count($dashboardWarnings);

$totalErrors = 0;
foreach ($checks as $check) {
    if ($check['status'] === 'error') $totalErrors++;
}

if ($mode === 'auto') {
    // CLI output
    echo "\n" . color("=== INTEGRITY CHECK SUMMARY ===\n", 'cyan');
    echo color("Errors: $totalErrors\n", $totalErrors > 0 ? 'red' : 'green');
    echo color("Warnings: $totalWarnings\n", $totalWarnings > 0 ? 'yellow' : 'green');
    
    if ($totalErrors === 0 && $totalWarnings === 0) {
        echo color("\n✅ System integrity check passed!\n", 'green');
    } else {
        echo color("\n⚠️  System requires attention\n", 'yellow');
        
        if ($totalErrors > 0) {
            echo color("\nCritical files missing - auto-repair recommended\n", 'red');
        }
        if ($totalWarnings > 0) {
            echo color("\nWarnings detected - review recommended\n", 'yellow');
        }
    }
    
    // Log results
    $logFile = "$baseDir/ew/logs/integrity_auto.log";
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'errors' => $totalErrors,
        'warnings' => $totalWarnings,
        'details' => [
            'missing_files' => array_filter($checks, fn($c) => $c['status'] === 'error'),
            'theme_warnings' => $themeWarnings,
            'css_warnings' => $cssWarnings,
            'header_warnings' => $headerWarnings,
            'twig_warnings' => $twigWarnings,
            'modal_warnings' => $modalWarnings,
            'nav_warnings' => $navWarnings,
            'dashboard_warnings' => $dashboardWarnings
        ]
    ];
    
    file_put_contents($logFile, json_encode($logEntry, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    echo color("\nResults logged to: $logFile\n", 'blue');
    
} else {
    // HTML output
    ?>
<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Integrity Check – Tierphysio Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #7C4DFF;
            --primary-2: #9C27B0;
        }
        body[data-theme="dark"] {
            background: #121212;
            color: #f1f1f1;
        }
        body[data-theme="dark"] .card {
            background: #1e1e1e;
            color: #f1f1f1;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .badge {
            padding: 0.35em 0.65em;
        }
        .header-gradient {
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="header-gradient">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-2">🔍 Integrity Check</h1>
                <p class="mb-0 opacity-75">Tierphysio Manager System Analysis</p>
            </div>
            <button id="themeToggle" class="btn btn-light btn-sm">
                <i class="bi bi-moon-fill"></i>
            </button>
        </div>
    </div>

    <!-- Summary -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">📊 Summary</h5>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1">
                        <strong>Errors:</strong> 
                        <?php if ($totalErrors > 0): ?>
                            <span class="badge bg-danger"><?= $totalErrors ?></span>
                        <?php else: ?>
                            <span class="badge bg-success">0</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1">
                        <strong>Warnings:</strong> 
                        <?php if ($totalWarnings > 0): ?>
                            <span class="badge bg-warning text-dark"><?= $totalWarnings ?></span>
                        <?php else: ?>
                            <span class="badge bg-success">0</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <hr>
            <p class="mb-0">
                <strong>Status:</strong> 
                <?php if ($totalErrors === 0 && $totalWarnings === 0): ?>
                    <?= badge('ok') ?> System stable
                <?php elseif ($totalErrors > 0): ?>
                    <?= badge('error') ?> Critical issues detected
                <?php else: ?>
                    <?= badge('warn') ?> Review recommended
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- File Structure -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">📁 File Structure</h5>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Component</th>
                        <th>Status</th>
                        <th>Path</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checks as $check): ?>
                    <tr>
                        <td><?= htmlspecialchars($check['label']) ?></td>
                        <td><?= badge($check['status']) ?></td>
                        <td><code><?= htmlspecialchars($check['path']) ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Theme & Styling -->
    <?php if ($themeWarnings || $cssWarnings): ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">🎨 Theme & Styling</h5>
            <ul class="mb-0">
                <?php foreach (array_merge($themeWarnings, $cssWarnings) as $warning): ?>
                <li><?= badge('warn') ?> <?= htmlspecialchars($warning) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header Components -->
    <?php if ($headerWarnings): ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">🧭 Header Components</h5>
            <ul class="mb-0">
                <?php foreach ($headerWarnings as $warning): ?>
                <li><?= badge('warn') ?> <?= htmlspecialchars($warning) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Twig Templates -->
    <?php if ($twigWarnings): ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">🧱 Twig Templates</h5>
            <ul class="mb-0">
                <?php foreach ($twigWarnings as $warning): ?>
                <li><?= badge('warn') ?> <?= htmlspecialchars($warning) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <?php if ($navWarnings): ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">📋 Navigation</h5>
            <ul class="mb-0">
                <?php foreach ($navWarnings as $warning): ?>
                <li><?= badge('warn') ?> <?= htmlspecialchars($warning) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dashboard -->
    <?php if ($dashboardWarnings): ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">📊 Dashboard KPIs</h5>
            <ul class="mb-0">
                <?php foreach ($dashboardWarnings as $warning): ?>
                <li><?= badge('warn') ?> <?= htmlspecialchars($warning) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Issues -->
    <?php if ($modalWarnings): ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">🪟 Modal Z-Index</h5>
            <p class="text-muted">Files with potential modal layering issues:</p>
            <ul class="mb-0">
                <?php foreach (array_slice($modalWarnings, 0, 10) as $warning): ?>
                <li><?= badge('warn') ?> <?= htmlspecialchars($warning) ?></li>
                <?php endforeach; ?>
                <?php if (count($modalWarnings) > 10): ?>
                <li class="text-muted">... and <?= count($modalWarnings) - 10 ?> more</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Theme toggle
document.getElementById('themeToggle').addEventListener('click', function() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme') || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    html.setAttribute('data-theme', next);
    localStorage.setItem('integrity-theme', next);
});

// Load saved theme
(function() {
    const saved = localStorage.getItem('integrity-theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
})();
</script>
</body>
</html>
    <?php
}
?>