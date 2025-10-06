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
$notes = [];
$patients = [];
$owners = [];
$errorMessage = null;

// Handle POST request for adding new note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && csrf_validate($_POST['csrf_token'] ?? '')) {
        $content = trim($_POST['content'] ?? '');
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $owner_id = (int)($_POST['owner_id'] ?? 0);
        
        if (empty($content)) {
            $errorMessage = "Notiztext ist erforderlich.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO notes (patient_id, owner_id, content, sync_status, created_at)
                    VALUES (:patient_id, :owner_id, :content, 'local', NOW())
                ");
                $stmt->execute([
                    ':patient_id' => $patient_id > 0 ? $patient_id : null,
                    ':owner_id' => $owner_id > 0 ? $owner_id : null,
                    ':content' => $content
                ]);
                
                $_SESSION['notify'][] = [
                    'type' => 'success',
                    'msg' => '✅ Notiz wurde erfolgreich gespeichert!'
                ];
                
                header('Location: notes.php');
                exit;
                
            } catch (PDOException $e) {
                if (function_exists('logError')) {
                    logError('Database error creating note', ['error' => $e->getMessage()]);
                }
                $errorMessage = "Datenbankfehler beim Speichern der Notiz.";
            }
        }
    }
}

// Load notes list
try {
    $stmt = $pdo->prepare("
        SELECT n.id, n.content, n.created_at,
               COALESCE(p.name, '-') AS patient_name,
               COALESCE(CONCAT_WS(' ', o.firstname, o.lastname), '-') AS owner_name
        FROM notes n
        LEFT JOIN patients p ON p.id = n.patient_id
        LEFT JOIN owners o ON o.id = n.owner_id
        ORDER BY n.created_at DESC
        LIMIT 200
    ");
    $stmt->execute();
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Load patients for dropdown
    $stmt = $pdo->prepare("
        SELECT id, name FROM patients ORDER BY name ASC
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
        logError('Database error loading notes', ['error' => $e->getMessage()]);
    }
    $errorMessage = "Datenbankfehler beim Laden der Notizen.";
    $notes = [];
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
    echo $twig->render('notes_new.twig', [
        'title' => 'Notizverwaltung',
        'notes' => $notes,
        'patients' => $patients,
        'owners' => $owners,
        'errorMessage' => $errorMessage,
        'notifications' => $notifications,
        'csrf_token' => $csrfToken
    ]);
} catch (Exception $e) {
    if (function_exists('logError')) {
        logError('Template error in notes.php', ['error' => $e->getMessage()]);
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