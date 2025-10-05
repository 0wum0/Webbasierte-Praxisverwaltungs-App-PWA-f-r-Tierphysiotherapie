<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/twig.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT p.*, 
           o.firstname, o.lastname, o.email, o.phone, o.street, o.zipcode, o.city
    FROM patients p
    JOIN owners o ON p.owner_id = o.id
    WHERE p.id = :id
");
$stmt->execute([":id" => $id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("❌ Patient nicht gefunden");
}

$errors = [];
$success = null;

// Neue Notiz hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_content'])) {
    $content = trim($_POST['note_content']);
    if ($content === '') {
        $errors[] = "Bitte eine Notiz eingeben.";
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO notes (patient_id, content, sync_status)
            VALUES (:pid, :content, 'local')
        ");
        $stmt->execute([
            ":pid" => $id,
            ":content" => $content
        ]);
        $success = "✅ Notiz gespeichert.";
    }
}

// Notiz löschen
if (isset($_GET['delete_note'])) {
    $nid = (int)$_GET['delete_note'];
    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id AND patient_id = :pid");
    $stmt->execute([":id" => $nid, ":pid" => $id]);
    header("Location: patient.php?id=" . $id);
    exit;
}

// Notizen laden
$stmt = $pdo->prepare("SELECT * FROM notes WHERE patient_id = :pid ORDER BY created_at DESC");
$stmt->execute([":pid" => $id]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Termine laden
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE patient_id = :pid ORDER BY appointment_date DESC");
$stmt->execute([":pid" => $id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rechnungen laden
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE patient_id = :pid ORDER BY id DESC");
$stmt->execute([":pid" => $id]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rendern
echo $twig->render("patient.twig", [
    "title" => "Patientenprofil",
    "patient" => $patient,
    "appointments" => $appointments,
    "invoices" => $invoices,
    "notes" => $notes,
    "errors" => $errors,
    "success" => $success
]);