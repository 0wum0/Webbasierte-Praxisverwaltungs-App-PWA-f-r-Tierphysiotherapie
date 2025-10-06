<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();

// Safety check
if (!$pdo) {
    die("Database connection unavailable.");
}

require_once __DIR__ . '/includes/twig.php';

// Initialize variables
$appointments = [];
$patients = [];
$errorMessage = null;

try {
    // Termine laden
    $stmt = $pdo->query("
        SELECT a.*, p.name AS patient_name, o.firstname, o.lastname
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN owners o ON p.owner_id = o.id
        ORDER BY a.appointment_date ASC
    ");
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Patienten für Dropdown
    $stmt = $pdo->query("
        SELECT p.id, p.name, o.firstname, o.lastname
        FROM patients p
        JOIN owners o ON p.owner_id = o.id
        ORDER BY p.name ASC
    ");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log the error
    if (function_exists('logError')) {
        logError('Database error in appointments.php', ['error' => $e->getMessage()]);
    }
    
    $errorMessage = "❌ Datenbankfehler: " . (APP_ENV === 'development' ? $e->getMessage() : 'Bitte kontaktieren Sie den Administrator.');
}

// Rendern
try {
    echo $twig->render("appointments.twig", [
        "title"       => "Termine",
        "appointments"=> $appointments,
        "patients"    => $patients,
        "errorMessage" => $errorMessage
    ]);
} catch (Exception $e) {
    // Log the error
    if (function_exists('logError')) {
        logError('Template error in appointments.php', ['error' => $e->getMessage()]);
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