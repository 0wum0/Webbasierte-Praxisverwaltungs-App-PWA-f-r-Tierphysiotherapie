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
$patients = [];
$owners = [];
$errorMessage = null;
$species = ['Hund', 'Katze', 'Pferd', 'Kaninchen', 'Meerschweinchen', 'Andere'];

// Handle POST request for adding new patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && csrf_validate($_POST['csrf_token'] ?? '')) {
        $owner_id = (int)($_POST['owner_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $species = trim($_POST['species'] ?? '');
        $breed = trim($_POST['breed'] ?? '');
        $age = (int)($_POST['age'] ?? 0);
        $weight = (float)($_POST['weight'] ?? 0);
        
        if (empty($name) || empty($species) || $owner_id === 0) {
            $errorMessage = "Name, Tierart und Besitzer sind erforderlich.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO patients (owner_id, name, species, breed, age, weight, created_at)
                    VALUES (:owner_id, :name, :species, :breed, :age, :weight, NOW())
                ");
                $stmt->execute([
                    ':owner_id' => $owner_id,
                    ':name' => $name,
                    ':species' => $species,
                    ':breed' => $breed ?: null,
                    ':age' => $age > 0 ? $age : null,
                    ':weight' => $weight > 0 ? $weight : null
                ]);
                
                $_SESSION['notify'][] = [
                    'type' => 'success',
                    'msg' => '✅ Patient wurde erfolgreich angelegt!'
                ];
                
                header('Location: patients.php');
                exit;
                
            } catch (PDOException $e) {
                if (function_exists('logError')) {
                    logError('Database error creating patient', ['error' => $e->getMessage()]);
                }
                $errorMessage = "Datenbankfehler beim Anlegen des Patienten.";
            }
        }
    }
}

// Load patients list
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.species, p.breed, p.image,
               CONCAT_WS(' ', o.firstname, o.lastname) AS owner_name,
               p.created_at
        FROM patients p
        JOIN owners o ON o.id = p.owner_id
        ORDER BY p.created_at DESC
        LIMIT 200
    ");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Load owners for dropdown
    $stmt = $pdo->prepare("
        SELECT id, CONCAT_WS(' ', firstname, lastname) AS name
        FROM owners
        ORDER BY lastname ASC, firstname ASC
    ");
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    if (function_exists('logError')) {
        logError('Database error loading patients', ['error' => $e->getMessage()]);
    }
    $errorMessage = "Datenbankfehler beim Laden der Patienten.";
    $patients = [];
    $owners = [];
}

// Get notifications from session
$notifications = $_SESSION['notify'] ?? [];
unset($_SESSION['notify']);

// Generate CSRF token
$csrfToken = csrf_token();

// Render template
try {
    echo $twig->render('patients_new.twig', [
        'title' => 'Patientenverwaltung',
        'patients' => $patients,
        'owners' => $owners,
        'species_list' => $species,
        'errorMessage' => $errorMessage,
        'notifications' => $notifications,
        'csrf_token' => $csrfToken
    ]);
} catch (Exception $e) {
    if (function_exists('logError')) {
        logError('Template error in patients.php', ['error' => $e->getMessage()]);
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