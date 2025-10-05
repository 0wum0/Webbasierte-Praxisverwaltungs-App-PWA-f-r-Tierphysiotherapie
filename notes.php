<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/twig.php";

$errors = [];
$success = null;

// Patienten & Besitzer laden für Dropdown
$stmt = $pdo->query("SELECT id, name FROM patients ORDER BY name ASC");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, firstname, lastname FROM owners ORDER BY lastname ASC");
$owners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Neue Notiz speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $ownerId = (int)($_POST['owner_id'] ?? 0);

    if ($content === '') {
        $errors[] = "Bitte einen Notiztext eingeben.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO notes (patient_id, owner_id, content, sync_status)
            VALUES (:patient_id, :owner_id, :content, 'local')
        ");
        $stmt->execute([
            ":patient_id" => $patientId ?: null,
            ":owner_id" => $ownerId ?: null,
            ":content" => $content
        ]);
        $success = "✅ Notiz gespeichert.";
    }
}

// Notiz löschen
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id");
    $stmt->execute([":id" => $id]);
    header("Location: notes.php");
    exit;
}

// Alle Notizen laden
$stmt = $pdo->query("
    SELECT n.*, 
           p.name AS patient_name, 
           o.firstname, o.lastname
    FROM notes n
    LEFT JOIN patients p ON n.patient_id = p.id
    LEFT JOIN owners o ON n.owner_id = o.id
    ORDER BY n.created_at DESC
");
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Render
echo $twig->render("notes.twig", [
    "title" => "Notizen",
    "patients" => $patients,
    "owners" => $owners,
    "notes" => $notes,
    "errors" => $errors,
    "success" => $success
]);