<?php
/**
 * Tierphysio Manager - Integritätsprüfung
 * Prüft das gesamte Projekt auf Layout-, Include-, Theme- und Modal-Fehler.
 * © 2025 Florian Engelhardt – Diagnose & Code by ChatGPT GPT-5
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n🔍 Starte Integritätsprüfung für Tierphysio Manager...\n";
$baseDir = __DIR__;

// Hilfsfunktion zur farblichen Konsolen-Ausgabe
function color($text, $color = 'default') {
    $colors = [
        'green' => "\033[32m", 'red' => "\033[31m",
        'yellow' => "\033[33m", 'cyan' => "\033[36m",
        'default' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['default'];
}

// Prüft ob Datei existiert
function checkFile($path, $desc) {
    if (file_exists($path)) {
        echo color("✅ $desc gefunden: $path\n", 'green');
        return true;
    } else {
        echo color("❌ $desc fehlt: $path\n", 'red');
        return false;
    }
}

// Prüft Inhalt einer Datei nach Mustern
function checkPattern($path, $pattern, $desc) {
    if (!file_exists($path)) return;
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
