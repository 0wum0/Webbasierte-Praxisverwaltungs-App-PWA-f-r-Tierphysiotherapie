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
$success = null;
$errors = [];
$species = ['Hund', 'Katze', 'Pferd', 'Kaninchen', 'Meerschweinchen', 'Andere'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $errors[] = "Ungültiger Sicherheitstoken. Bitte versuchen Sie es erneut.";
    } else {
        // Get owner data
        $ownerFirstname = trim($_POST['owner_firstname'] ?? '');
        $ownerLastname = trim($_POST['owner_lastname'] ?? '');
        $ownerEmail = trim($_POST['owner_email'] ?? '');
        $ownerPhone = trim($_POST['owner_phone'] ?? '');
        $ownerStreet = trim($_POST['owner_street'] ?? '');
        $ownerZip = trim($_POST['owner_zip'] ?? '');
        $ownerCity = trim($_POST['owner_city'] ?? '');
        
        // Get patient data
        $patientName = trim($_POST['patient_name'] ?? '');
        $patientSpecies = trim($_POST['patient_species'] ?? '');
        $patientBreed = trim($_POST['patient_breed'] ?? '');
        $patientAge = (int)($_POST['patient_age'] ?? 0);
        $patientWeight = (float)($_POST['patient_weight'] ?? 0);
        $patientColor = trim($_POST['patient_color'] ?? '');
        $patientMicrochip = trim($_POST['patient_microchip'] ?? '');
        $patientNotes = trim($_POST['patient_notes'] ?? '');
        
        // Validate owner data
        if (empty($ownerFirstname)) {
            $errors[] = "Vorname des Besitzers ist erforderlich.";
        }
        if (empty($ownerLastname)) {
            $errors[] = "Nachname des Besitzers ist erforderlich.";
        }
        if (empty($ownerEmail) && empty($ownerPhone)) {
            $errors[] = "Mindestens eine Kontaktmöglichkeit (E-Mail oder Telefon) ist erforderlich.";
        }
        
        // Validate patient data
        if (empty($patientName)) {
            $errors[] = "Name des Patienten ist erforderlich.";
        }
        if (empty($patientSpecies)) {
            $errors[] = "Tierart ist erforderlich.";
        }
        
        // If no errors, save to database
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Insert owner
                $stmt = $pdo->prepare("
                    INSERT INTO owners (firstname, lastname, email, phone, street, zip, city, created_at)
                    VALUES (:firstname, :lastname, :email, :phone, :street, :zip, :city, NOW())
                ");
                $stmt->execute([
                    ':firstname' => $ownerFirstname,
                    ':lastname' => $ownerLastname,
                    ':email' => $ownerEmail ?: null,
                    ':phone' => $ownerPhone ?: null,
                    ':street' => $ownerStreet ?: null,
                    ':zip' => $ownerZip ?: null,
                    ':city' => $ownerCity ?: null
                ]);
                $ownerId = $pdo->lastInsertId();
                
                // Insert patient
                $stmt = $pdo->prepare("
                    INSERT INTO patients (owner_id, name, species, breed, age, weight, color, microchip_number, notes, created_at)
                    VALUES (:owner_id, :name, :species, :breed, :age, :weight, :color, :microchip, :notes, NOW())
                ");
                $stmt->execute([
                    ':owner_id' => $ownerId,
                    ':name' => $patientName,
                    ':species' => $patientSpecies,
                    ':breed' => $patientBreed ?: null,
                    ':age' => $patientAge > 0 ? $patientAge : null,
                    ':weight' => $patientWeight > 0 ? $patientWeight : null,
                    ':color' => $patientColor ?: null,
                    ':microchip' => $patientMicrochip ?: null,
                    ':notes' => $patientNotes ?: null
                ]);
                
                $pdo->commit();
                
                // Set success message and redirect
                $_SESSION['notify'][] = [
                    'type' => 'success',
                    'msg' => "✅ Besitzer und Patient wurden erfolgreich angelegt!"
                ];
                
                header('Location: patients.php');
                exit;
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                
                if (function_exists('logError')) {
                    logError('Database error in owner_patient.php', ['error' => $e->getMessage()]);
                }
                
                $errors[] = "Datenbankfehler: " . (APP_ENV === 'development' ? $e->getMessage() : 'Bitte kontaktieren Sie den Administrator.');
            }
        }
    }
}

// Generate CSRF token
$csrfToken = csrf_token();

// Render template
try {
    echo $twig->render('owner_patient.twig', [
        'title' => 'Besitzer & Patient hinzufügen',
        'success' => $success,
        'errors' => $errors,
        'species' => $species,
        'csrf_token' => $csrfToken,
        'form_data' => $_POST // Preserve form data on error
    ]);
} catch (Exception $e) {
    if (function_exists('logError')) {
        logError('Template error in owner_patient.php', ['error' => $e->getMessage()]);
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