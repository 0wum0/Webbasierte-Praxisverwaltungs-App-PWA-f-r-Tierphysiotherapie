<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();
if (!$pdo) {
    throw new RuntimeException('DB connection unavailable');
}

require_once __DIR__ . '/includes/twig.php';
require_once __DIR__ . '/includes/csrf.php';

// Initialize variables
$appointments = [];
$patients = [];
$errorMessage = null;

// Handle POST request for adding new appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && csrf_validate($_POST['csrf_token'] ?? '')) {
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $appointment_date = $_POST['appointment_date'] ?? '';
        $appointment_time = $_POST['appointment_time'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        
        if ($patient_id === 0 || empty($appointment_date) || empty($appointment_time)) {
            $errorMessage = "Patient, Datum und Uhrzeit sind erforderlich.";
        } else {
            try {
                $datetime = $appointment_date . ' ' . $appointment_time . ':00';
                $stmt = $pdo->prepare("
                    INSERT INTO appointments (patient_id, appointment_date, notes, created_at)
                    VALUES (:patient_id, :appointment_date, :notes, NOW())
                ");
                $stmt->execute([
                    ':patient_id' => $patient_id,
                    ':appointment_date' => $datetime,
                    ':notes' => $notes ?: null
                ]);
                
                $_SESSION['notify'][] = [
                    'type' => 'success',
                    'msg' => '✅ Termin wurde erfolgreich angelegt!'
                ];
                
                header('Location: appointments.php');
                exit;
                
            } catch (PDOException $e) {
                if (function_exists('logError')) {
                    logError('Database error creating appointment', ['error' => $e->getMessage()]);
                }
                $errorMessage = "Datenbankfehler beim Anlegen des Termins.";
            }
        }
    }
}

// Load appointments list
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.appointment_date,
               p.name AS patient_name,
               CONCAT_WS(' ', o.firstname, o.lastname) AS owner_name,
               a.notes
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        JOIN owners o ON o.id = p.owner_id
        ORDER BY a.appointment_date DESC
        LIMIT 200
    ");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Load patients for dropdown
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, CONCAT_WS(' ', o.firstname, o.lastname) AS owner_name
        FROM patients p
        JOIN owners o ON o.id = p.owner_id
        ORDER BY p.name ASC
    ");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    if (function_exists('logError')) {
        logError('Database error loading appointments', ['error' => $e->getMessage()]);
    }
    $errorMessage = "Datenbankfehler beim Laden der Termine.";
    $appointments = [];
    $patients = [];
}

// Get notifications from session
$notifications = $_SESSION['notify'] ?? [];
unset($_SESSION['notify']);

// Generate CSRF token
$csrfToken = csrf_token();

// Render template
try {
    echo $twig->render('appointments_new.twig', [
        'title' => 'Terminverwaltung',
        'appointments' => $appointments,
        'patients' => $patients,
        'errorMessage' => $errorMessage,
        'notifications' => $notifications,
        'csrf_token' => $csrfToken
    ]);
} catch (Exception $e) {
    if (function_exists('logError')) {
        logError('Template error in appointments.php', ['error' => $e->getMessage()]);
    }
    
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Fehler</title></head><body>';
    echo '<h1>Ein Fehler ist aufgetreten</h1>';
    echo '<p>Die Seite konnte nicht geladen werden. Bitte kontaktieren Sie den Administrator.</p>';
    if (APP_ENV === 'development') {
        echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    echo '</body></html>';
}