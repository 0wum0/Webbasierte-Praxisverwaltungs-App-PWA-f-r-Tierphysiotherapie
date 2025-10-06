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
$owners = [];
$errorMessage = null;
$successMessage = null;

// Handle POST request for adding new owner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && csrf_validate($_POST['csrf_token'] ?? '')) {
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $zip = trim($_POST['zip'] ?? '');
        $city = trim($_POST['city'] ?? '');
        
        if (empty($firstname) || empty($lastname)) {
            $errorMessage = "Vor- und Nachname sind erforderlich.";
        } elseif (empty($email) && empty($phone)) {
            $errorMessage = "Mindestens eine Kontaktmöglichkeit (E-Mail oder Telefon) ist erforderlich.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO owners (firstname, lastname, email, phone, street, zip, city, created_at)
                    VALUES (:firstname, :lastname, :email, :phone, :street, :zip, :city, NOW())
                ");
                $stmt->execute([
                    ':firstname' => $firstname,
                    ':lastname' => $lastname,
                    ':email' => $email ?: null,
                    ':phone' => $phone ?: null,
                    ':street' => $street ?: null,
                    ':zip' => $zip ?: null,
                    ':city' => $city ?: null
                ]);
                
                $_SESSION['notify'][] = [
                    'type' => 'success',
                    'msg' => '✅ Besitzer wurde erfolgreich angelegt!'
                ];
                
                // Redirect to prevent form resubmission
                header('Location: owners.php');
                exit;
                
            } catch (PDOException $e) {
                if (function_exists('logError')) {
                    logError('Database error creating owner', ['error' => $e->getMessage()]);
                }
                $errorMessage = "Datenbankfehler beim Anlegen des Besitzers.";
            }
        }
    } else {
        $errorMessage = "Ungültiger Sicherheitstoken. Bitte versuchen Sie es erneut.";
    }
}

// Load owners list
try {
    $stmt = $pdo->prepare("
        SELECT o.id, CONCAT_WS(' ', o.firstname, o.lastname) AS name,
               o.email, o.phone,
               (SELECT COUNT(*) FROM patients p WHERE p.owner_id = o.id) AS patients_count,
               o.created_at
        FROM owners o
        ORDER BY o.created_at DESC
        LIMIT 200
    ");
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    if (function_exists('logError')) {
        logError('Database error loading owners', ['error' => $e->getMessage()]);
    }
    $errorMessage = "Datenbankfehler beim Laden der Besitzer.";
    $owners = [];
}

// Get notifications from session
$notifications = $_SESSION['notify'] ?? [];
unset($_SESSION['notify']);

// Generate CSRF token
$csrfToken = csrf_token();

// Render template
try {
    echo $twig->render('owners_new.twig', [
        'title' => 'Besitzerverwaltung',
        'owners' => $owners,
        'errorMessage' => $errorMessage,
        'successMessage' => $successMessage,
        'notifications' => $notifications,
        'csrf_token' => $csrfToken
    ]);
} catch (Exception $e) {
    if (function_exists('logError')) {
        logError('Template error in owners.php', ['error' => $e->getMessage()]);
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