<?php
declare(strict_types=1);

/**
 * Notes Management Page - Twig Version
 * Unified design with SSoT implementation
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/twig.php';

$db = db();
if (!$db) {
    throw new RuntimeException('DB connection unavailable');
}

// Initialize variables
$notes = [];
$patients = [];
$owners = [];
$errors = [];
$success = null;

// Ensure notes table exists
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT DEFAULT NULL,
            owner_id INT DEFAULT NULL,
            content TEXT NOT NULL,
            category VARCHAR(50) DEFAULT 'general',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
            INDEX idx_patient (patient_id),
            INDEX idx_owner (owner_id),
            INDEX idx_category (category),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Check if note_text column exists and migrate if needed
    $columns = $db->query("SHOW COLUMNS FROM notes")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('note_text', $columns) && !in_array('content', $columns)) {
        $db->exec("ALTER TABLE notes CHANGE COLUMN note_text content TEXT NOT NULL");
    }
    if (!in_array('owner_id', $columns)) {
        $db->exec("ALTER TABLE notes ADD COLUMN owner_id INT DEFAULT NULL AFTER patient_id");
        $db->exec("ALTER TABLE notes ADD FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE");
        $db->exec("ALTER TABLE notes ADD INDEX idx_owner (owner_id)");
    }
} catch (Exception $e) {
    error_log("Notes table setup error: " . $e->getMessage());
}

// Handle POST request for adding/updating notes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_validate($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? 'create';
        
        if ($action === 'create') {
            $patient_id = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
            $owner_id = !empty($_POST['owner_id']) ? (int)$_POST['owner_id'] : null;
            $content = trim($_POST['content'] ?? '');
            $category = $_POST['category'] ?? 'general';
            
            if (empty($content)) {
                $errors[] = "Notiztext ist erforderlich.";
            } else {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO notes (patient_id, owner_id, content, category) 
                        VALUES (:patient_id, :owner_id, :content, :category)
                    ");
                    $stmt->execute([
                        ':patient_id' => $patient_id,
                        ':owner_id' => $owner_id,
                        ':content' => $content,
                        ':category' => $category
                    ]);
                    $success = "Notiz erfolgreich hinzugefügt!";
                } catch (Exception $e) {
                    $errors[] = "Fehler beim Speichern der Notiz: " . $e->getMessage();
                }
            }
        } elseif ($action === 'update' && isset($_POST['note_id'])) {
            $note_id = (int)$_POST['note_id'];
            $content = trim($_POST['content'] ?? '');
            
            if (empty($content)) {
                $errors[] = "Notiztext ist erforderlich.";
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE notes 
                        SET content = :content, updated_at = NOW() 
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':content' => $content,
                        ':id' => $note_id
                    ]);
                    $success = "Notiz erfolgreich aktualisiert!";
                } catch (Exception $e) {
                    $errors[] = "Fehler beim Aktualisieren der Notiz: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete' && isset($_POST['note_id'])) {
            $note_id = (int)$_POST['note_id'];
            try {
                $stmt = $db->prepare("DELETE FROM notes WHERE id = :id");
                $stmt->execute([':id' => $note_id]);
                $success = "Notiz erfolgreich gelöscht!";
            } catch (Exception $e) {
                $errors[] = "Fehler beim Löschen der Notiz: " . $e->getMessage();
            }
        }
    } else {
        $errors[] = "CSRF-Token ungültig. Bitte versuchen Sie es erneut.";
    }
}

// Fetch all notes with related data
try {
    $stmt = $db->prepare("
        SELECT 
            n.*,
            p.name as patient_name,
            o.firstname as owner_firstname,
            o.lastname as owner_lastname
        FROM notes n
        LEFT JOIN patients p ON n.patient_id = p.id
        LEFT JOIN owners o ON n.owner_id = o.id
        ORDER BY n.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching notes: " . $e->getMessage());
    $notes = [];
}

// Fetch patients for dropdown
try {
    $stmt = $db->prepare("
        SELECT id, name 
        FROM patients 
        ORDER BY name ASC
    ");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $patients = [];
}

// Fetch owners for dropdown
try {
    $stmt = $db->prepare("
        SELECT id, firstname, lastname 
        FROM owners 
        ORDER BY lastname, firstname ASC
    ");
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $owners = [];
}

// User information
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Benutzer';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['admin_email'] ?? '';
$isAdmin = isset($_SESSION['admin_id']);

// Render with Twig template
echo $twig->render('notes.twig', [
    'title' => 'Notizen - Tierphysio Manager',
    'current_page' => 'notes',
    'user_name' => $userName,
    'user_email' => $userEmail,
    'is_admin' => $isAdmin,
    'theme' => $_SESSION['theme'] ?? 'light',
    
    // Data
    'notes' => $notes,
    'patients' => $patients,
    'owners' => $owners,
    
    // Messages
    'errors' => $errors,
    'success' => $success,
    
    // CSRF
    'csrf_token' => csrf_token()
]);