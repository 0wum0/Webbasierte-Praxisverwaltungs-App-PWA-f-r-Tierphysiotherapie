<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();

// Safety check
if (!$pdo) {
    die("Database connection unavailable.");
}

require_once __DIR__ . '/includes/twig.php'; // Twig Setup

// Patienten laden (mit Besitzer)
$search = $_GET['search'] ?? '';
$patients = [];

try {
    if ($search) {
        $stmt = $pdo->prepare("
            SELECT p.*, o.firstname, o.lastname 
            FROM patients p
            JOIN owners o ON p.owner_id = o.id
            WHERE p.name LIKE :s1
               OR o.lastname LIKE :s2
               OR o.firstname LIKE :s3
            ORDER BY p.name ASC
        ");
        $stmt->execute([
            ':s1' => "%$search%",
            ':s2' => "%$search%",
            ':s3' => "%$search%"
        ]);
    } else {
        $stmt = $pdo->query("
            SELECT p.*, o.firstname, o.lastname 
            FROM patients p
            JOIN owners o ON p.owner_id = o.id
            ORDER BY p.name ASC
        ");
    }

    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log the error
    if (function_exists('logError')) {
        logError('Database error in patients.php', ['error' => $e->getMessage()]);
    }
    
    // Set empty data to prevent template errors
    $patients = [];
    $errorMessage = "❌ Datenbankfehler: " . (APP_ENV === 'development' ? $e->getMessage() : 'Bitte kontaktieren Sie den Administrator.');
}

// Rendern
try {
    echo $twig->render("patients.twig", [
        "title"    => "Patientenliste",
        "patients" => $patients,
        "search"   => $search,
        "errorMessage" => $errorMessage ?? null
    ]);
} catch (Exception $e) {
    // Log the error
    if (function_exists('logError')) {
        logError('Template error in patients.php', ['error' => $e->getMessage()]);
    }
    
    // Display a user-friendly error page
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Fehler</title></head><body>';
    echo '<h1>Ein Fehler ist aufgetreten</h1>';
    echo '<p>Die Seite konnte nicht geladen werden. Bitte kontaktieren Sie den Administrator.</p>';
    if (APP_ENV === 'development') {
        echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    echo '</body></html>';
}