<?php
/**
 * Tierphysio Manager – Visuelle Integritätsprüfung (HTML-Version)
 * by Florian Engelhardt © 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

function checkFile($label, $path) {
    $exists = file_exists($path);
    return [
        'label' => $label,
        'path' => $path,
        'status' => $exists ? 'ok' : 'error',
        'message' => $exists ? 'Gefunden' : 'Fehlt'
    ];
}

function badge($status) {
    switch ($status) {
        case 'ok': return '<span class="badge bg-success">✅ OK</span>';
        case 'warn': return '<span class="badge bg-warning text-dark">⚠️ Hinweis</span>';
        case 'error': return '<span class="badge bg-danger">❌ Fehler</span>';
    }
}

$checks = [];

// === Verzeichnisprüfung ===
$paths = [
    'Header' => 'includes/layout/header.php',
    'Navigation' => 'includes/layout/nav.php',
    'Footer' => 'includes/layout/footer.php',
    'Installer' => 'install/install.php',
    'Installations-Lock' => 'install/install.lock',
    'CSS (main.css)' => 'public/css/main.css',
    'Theme Script' => 'public/js/theme.js',
    'App Script' => 'public/js/app.js'
];
foreach ($paths as $label => $file) {
    $checks[] = checkFile($label, $file);
}

// === Theme prüfen ===
$themeWarn = [];
if (file_exists('public/js/theme.js')) {
    $theme = file_get_contents('public/js/theme.js');
    if (!str_contains($theme, 'localStorage.getItem'))
        $themeWarn[] = 'Theme-Persistenz fehlt';
    if (!str_contains($theme, 'data-theme'))
        $themeWarn[] = 'data-theme fehlt';
}

$cssWarn = [];
if (file_exists('public/css/main.css')) {
    $css = file_get_contents('public/css/main.css');
    if (!str_contains($css, '#7C4DFF'))
        $cssWarn[] = 'Primärfarbe nicht gefunden';
}

// === Header-Checks ===
$headerWarn = [];
if (file_exists('includes/layout/header.php')) {
    $header = file_get_contents('includes/layout/header.php');
    if (!str_contains($header, 'topbar')) $headerWarn[] = 'Topbar fehlt';
    if (!str_contains($header, 'bi-list')) $headerWarn[] = 'Burger-Menü fehlt';
    if (!str_contains($header, 'theme-toggle')) $headerWarn[] = 'Theme-Button fehlt';
    if (!str_contains($header, 'dropdown-menu')) $headerWarn[] = 'User-Menü fehlt';
}

// === Twig-Dateien prüfen ===
$twigWarn = [];
$twigDir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('templates'));
foreach ($twigDir as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'twig') {
        $content = file_get_contents($file);
        $rel = str_replace(__DIR__.'/', '', $file);
        if (!preg_match('/{% *extends *[\'"]base\.twig[\'"] *%}/', $content))
            $twigWarn[] = "$rel erweitert keine base.twig";
        if (!str_contains($content, '{% endblock %}'))
            $twigWarn[] = "$rel hat kein endblock";
    }
}

// === Modal-Checks ===
$modalWarn = [];
$phpDir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.'));
foreach ($phpDir as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $content = file_get_contents($file);
        $rel = str_replace(__DIR__.'/', '', $file);
        if (str_contains($content, 'modal') && !str_contains($content, 'z-index'))
            $modalWarn[] = "$rel Modal ohne z-index";
    }
}
?>
<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Integritätsprüfung – Tierphysio Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body[data-theme="dark"] {
    background: #121212;
    color: #f1f1f1;
}
.card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.table td { vertical-align: middle; }
.theme-toggle { position: fixed; top: 10px; right: 10px; cursor: pointer; }
</style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🔍 Integritätsprüfung – Tierphysio Manager</h2>
        <button id="toggleTheme" class="btn btn-outline-secondary btn-sm"><i class="bi bi-moon"></i> Theme</button>
    </div>

    <div class="card p-4 mb-4">
        <h5>📁 Verzeichnisstruktur</h5>
        <table class="table table-sm mt-2">
            <thead><tr><th>Datei</th><th>Status</th><th>Pfad</th></tr></thead>
            <tbody>
                <?php foreach ($checks as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['label']) ?></td>
                    <td><?= badge($c['status']) ?></td>
                    <td><code><?= htmlspecialchars($c['path']) ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card p-4 mb-4">
        <h5>🎨 Theme & Header</h5>
        <?php if ($themeWarn || $cssWarn || $headerWarn): ?>
            <ul>
            <?php foreach (array_merge($themeWarn,$cssWarn,$headerWarn) as $w): ?>
                <li><?= badge('warn') ?> <?= htmlspecialchars($w) ?></li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?= badge('ok') ?> Theme und Header sind konsistent.</p>
        <?php endif; ?>
    </div>

    <div class="card p-4 mb-4">
        <h5>🧱 Twig Templates</h5>
        <?php if ($twigWarn): ?>
            <ul><?php foreach ($twigWarn as $w): ?><li><?= badge('warn') ?> <?= htmlspecialchars($w) ?></li><?php endforeach; ?></ul>
        <?php else: ?><p><?= badge('ok') ?> Alle Templates erweitern base.twig korrekt.</p><?php endif; ?>
    </div>

    <div class="card p-4 mb-4">
        <h5>🪟 Modal-Prüfung</h5>
        <?php if ($modalWarn): ?>
            <ul><?php foreach ($modalWarn as $w): ?><li><?= badge('warn') ?> <?= htmlspecialchars($w) ?></li><?php endforeach; ?></ul>
        <?php else: ?><p><?= badge('ok') ?> Alle Modals sind korrekt positioniert.</p><?php endif; ?>
    </div>

    <div class="card p-4">
        <h5>📋 Zusammenfassung</h5>
        <p>
            <?= badge('ok') ?> = alles korrekt &nbsp;
            <?= badge('warn') ?> = prüfen &nbsp;
            <?= badge('error') ?> = kritisch
        </p>
        <p><strong>Status:</strong> <?= (!$twigWarn && !$modalWarn && !$headerWarn && !$themeWarn && !$cssWarn) ? badge('ok').' System stabil' : badge('warn').' Überprüfung empfohlen'; ?></p>
    </div>
</div>

<script>
document.getElementById("toggleTheme").addEventListener("click",()=>{
  const root=document.documentElement;
  const theme=root.getAttribute("data-theme")==="dark"?"light":"dark";
  root.setAttribute("data-theme",theme);
  localStorage.setItem("integrity-theme",theme);
});
(function(){
  const saved=localStorage.getItem("integrity-theme")||"light";
  document.documentElement.setAttribute("data-theme",saved);
})();
</script>
</body>
</html>    if (!file_exists($path)) return;
    $content = file_get_contents($path);
    if (preg_match($pattern, $content)) {
        echo color("✅ $desc in $path\n", 'green');
    } else {
        echo color("⚠️  $desc fehlt in $path\n", 'yellow');
    }
}

// Rekursive Suche nach Dateien
function findFiles($dir, $exts = ['php', 'twig', 'css', 'js']) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = [];
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        $ext = pathinfo($file->getPathname(), PATHINFO_EXTENSION);
        if (in_array($ext, $exts)) $files[] = $file->getPathname();
    }
    return $files;
}

// 1️⃣ Grundstruktur prüfen
echo "\n📁 Verzeichnisstruktur:\n";
checkFile("$baseDir/includes/layout/header.php", "Header");
checkFile("$baseDir/includes/layout/nav.php", "Navigation");
checkFile("$baseDir/includes/layout/footer.php", "Footer");
checkFile("$baseDir/install/install.php", "Installer");
checkFile("$baseDir/install/install.lock", "Installations-Lock-Datei");
checkFile("$baseDir/public/css/main.css", "Globales CSS");
checkFile("$baseDir/public/js/theme.js", "Theme-Script");
checkFile("$baseDir/public/js/app.js", "App-Script");

// 2️⃣ Theme-Logik prüfen
echo "\n🎨 Theme-Prüfung:\n";
checkPattern("$baseDir/public/js/theme.js", '/localStorage/', "Theme persistiert via localStorage");
checkPattern("$baseDir/public/js/theme.js", '/data-theme/', "HTML-Attribut data-theme vorhanden");
checkPattern("$baseDir/public/css/main.css", '/--primary/', "Primärfarben definiert");

// 3️⃣ Header & Klickbarkeit prüfen
echo "\n🧭 Header & Klickprüfung:\n";
checkPattern("$baseDir/includes/layout/header.php", '/class="topbar/', "Topbar vorhanden");
checkPattern("$baseDir/includes/layout/header.php", '/bi-list/', "Burger-Menü Icon vorhanden");
checkPattern("$baseDir/includes/layout/header.php", '/themeToggle/', "Theme-Button vorhanden");
checkPattern("$baseDir/includes/layout/header.php", '/dropdown/', "User-Menü Dropdown vorhanden");

// 4️⃣ Navigation prüfen
echo "\n📋 Navigationsprüfung:\n";
$navFile = "$baseDir/includes/layout/nav.php";
if (file_exists($navFile)) {
    $nav = file_get_contents($navFile);
    $pages = ['dashboard', 'patients', 'appointments', 'notes', 'invoices', 'owners', 'accounting', 'admin'];
    foreach ($pages as $p) {
        if (stripos($nav, $p) !== false) {
            echo color("✅ Link zu $p.php gefunden\n", 'green');
        } else {
            echo color("⚠️  Link zu $p.php fehlt\n", 'yellow');
        }
    }
}

// 5️⃣ Twig Templates prüfen
echo "\n🧱 Twig-Template-Prüfung:\n";
$twigFiles = findFiles("$baseDir/templates", ['twig']);
foreach ($twigFiles as $file) {
    $content = file_get_contents($file);
    if (preg_match('/extends\s+"base\.twig"/', $content)) {
        echo color("✅ $file erweitert base.twig\n", 'green');
    } else {
        echo color("⚠️  $file verwendet keine base.twig\n", 'yellow');
    }
    if (preg_match('/endblock/', $content) == 0) {
        echo color("❌ Blockende fehlt in $file\n", 'red');
    }
}

// 6️⃣ Modals prüfen
echo "\n🪟 Modal-Prüfung:\n";
$phpFiles = findFiles($baseDir, ['php']);
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    if (preg_match('/modal-dialog/', $content)) {
        if (preg_match('/z-index/', $content)) {
            echo color("✅ Modal-Struktur in $file korrekt\n", 'green');
        } else {
            echo color("⚠️  Modal in $file ohne z-index-Anpassung\n", 'yellow');
        }
    }
}

// 7️⃣ Installer & Migration prüfen
echo "\n🧩 Installer & Migration:\n";
checkPattern("$baseDir/install/install.php", '/install\.lock/', "Installations-Lock-Abfrage vorhanden");
checkPattern("$baseDir/install/install.php", '/system_info/', "System-Info Tabellenprüfung");
checkPattern("$baseDir/install/install.php", '/migration_log/', "Migrationslog vorhanden");
checkPattern("$baseDir/install/install.php", '/db_version/', "Versionsvergleich implementiert");

// 8️⃣ KPI-Dashboard prüfen
echo "\n📊 Dashboard-Prüfung:\n";
$dash = "$baseDir/dashboard.php";
checkPattern($dash, '/Chart/', "Chart.js eingebunden");
checkPattern($dash, '/height:\s*300px/', "Chart-Höhe begrenzt");

// 9️⃣ Klickblocker-Prüfung (CSS)
echo "\n🖱️ Klickblocker prüfen:\n";
$css = file_get_contents("$baseDir/public/css/main.css");
if (preg_match('/pointer-events:\s*none/', $css)) {
    echo color("✅ Klickblocker-Schutz aktiv (pointer-events:none)\n", 'green');
} else {
    echo color("⚠️  Kein Klickblocker-Schutz definiert\n", 'yellow');
}

// 🔟 Gesamtauswertung
echo "\n📋 Zusammenfassung:\n";
echo color("✅ = alles korrekt, ⚠️ = Empfehlung prüfen, ❌ = kritischer Fehler\n\n", 'cyan');

echo color("Prüfung abgeschlossen.\n", 'cyan');
echo color("Wenn ❌-Meldungen erscheinen, bitte Backup prüfen oder Code aus globalem Prompt erneut anwenden.\n\n", 'yellow');
?>
