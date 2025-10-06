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
$invoices = [];
$patients = [];
$errorMessage = null;

// Handle POST request for adding new invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && csrf_validate($_POST['csrf_token'] ?? '')) {
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'open';
        
        if ($patient_id === 0 || $amount <= 0) {
            $errorMessage = "Patient und Betrag sind erforderlich.";
        } else {
            try {
                // Check if description column exists
                $columns = $pdo->query("SHOW COLUMNS FROM invoices")->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('description', $columns)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO invoices (patient_id, amount, description, status, created_at)
                        VALUES (:patient_id, :amount, :description, :status, NOW())
                    ");
                    $stmt->execute([
                        ':patient_id' => $patient_id,
                        ':amount' => $amount,
                        ':description' => $description ?: null,
                        ':status' => $status
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO invoices (patient_id, amount, status, created_at)
                        VALUES (:patient_id, :amount, :status, NOW())
                    ");
                    $stmt->execute([
                        ':patient_id' => $patient_id,
                        ':amount' => $amount,
                        ':status' => $status
                    ]);
                }
                
                $_SESSION['notify'][] = [
                    'type' => 'success',
                    'msg' => '✅ Rechnung wurde erfolgreich erstellt!'
                ];
                
                header('Location: invoices.php');
                exit;
                
            } catch (PDOException $e) {
                if (function_exists('logError')) {
                    logError('Database error creating invoice', ['error' => $e->getMessage()]);
                }
                $errorMessage = "Datenbankfehler beim Erstellen der Rechnung.";
            }
        }
    }
}

// Load invoices list
try {
    $stmt = $pdo->prepare("
        SELECT i.id, i.amount, i.status, i.created_at,
               p.name AS patient_name
        FROM invoices i
        JOIN patients p ON p.id = i.patient_id
        ORDER BY i.created_at DESC
        LIMIT 200
    ");
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
        logError('Database error loading invoices', ['error' => $e->getMessage()]);
    }
    $errorMessage = "Datenbankfehler beim Laden der Rechnungen.";
    $invoices = [];
    $patients = [];
}

// Get notifications from session
$notifications = $_SESSION['notify'] ?? [];
unset($_SESSION['notify']);

// Generate CSRF token
$csrfToken = csrf_token();

// Render template
try {
    echo $twig->render('invoices_new.twig', [
        'title' => 'Rechnungsverwaltung',
        'invoices' => $invoices,
        'patients' => $patients,
        'errorMessage' => $errorMessage,
        'notifications' => $notifications,
        'csrf_token' => $csrfToken
    ]);
} catch (Exception $e) {
    if (function_exists('logError')) {
        logError('Template error in invoices.php', ['error' => $e->getMessage()]);
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