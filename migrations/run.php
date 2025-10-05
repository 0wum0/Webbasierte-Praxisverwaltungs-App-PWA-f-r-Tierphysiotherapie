<?php
declare(strict_types=1);

/**
 * Migrations Runner
 * Führt alle SQL-Migrations in der richtigen Reihenfolge aus
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/logger.php';

echo "🔧 Tierphysio Migrations Runner\n";
echo "================================\n\n";

// Alle SQL-Dateien in der richtigen Reihenfolge
$migrations = [
    '001_create_admin_tables.sql',
    '002_seed_admin_data.sql',
];

$executed = 0;
$failed = 0;

foreach ($migrations as $file) {
    $path = __DIR__ . '/' . $file;
    
    if (!file_exists($path)) {
        echo "❌ Migration nicht gefunden: $file\n";
        $failed++;
        continue;
    }
    
    echo "⏳ Führe Migration aus: $file ... ";
    
    try {
        $sql = file_get_contents($path);
        
        // SQL in einzelne Statements aufteilen (sehr einfache Implementierung)
        // Für komplexe Migrations besser ein richtiges Tool verwenden
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s) && !str_starts_with($s, '--')
        );
        
        foreach ($statements as $stmt) {
            if (empty(trim($stmt))) {
                continue;
            }
            $pdo->exec($stmt);
        }
        
        echo "✅ Erfolgreich\n";
        logInfo("Migration executed successfully", ['file' => $file]);
        $executed++;
    } catch (PDOException $e) {
        echo "❌ Fehler\n";
        echo "   Fehlermeldung: " . $e->getMessage() . "\n";
        logError("Migration failed", [
            'file' => $file,
            'error' => $e->getMessage(),
        ]);
        $failed++;
    }
}

echo "\n================================\n";
echo "✅ Erfolgreich: $executed\n";
echo "❌ Fehlgeschlagen: $failed\n";

if ($failed === 0) {
    echo "\n🎉 Alle Migrations erfolgreich ausgeführt!\n\n";
    echo "Standard Admin-Login:\n";
    echo "  Email: admin@tierphysio.local\n";
    echo "  Passwort: Admin123!\n";
    echo "  ⚠️  Bitte nach dem ersten Login ändern!\n";
}

exit($failed > 0 ? 1 : 0);
