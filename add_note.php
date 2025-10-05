<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/includes/twig.php";
require_once __DIR__ . "/includes/csrf.php";

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$errors = [];
$success = false;

// Patient laden
$patient = null;
if ($patient_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
    $stmt->execute([":id" => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Formular abgesendet?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_validate();
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
        goto render;
    }
    
    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
        $errors[] = "Bitte einen Notiztext eingeben.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO notes (patient_id, content, sync_status)
            VALUES (:patient_id, :content, 'local')
        ");
        $stmt->execute([
            ":patient_id" => $patient_id,
            ":content" => $content
        ]);

        $success = true;
        // Zurück zur Patientenseite
        header("Location: patient.php?id=" . $patient_id);
        exit;
    }
}

// Render
render:
echo $twig->render("add_note.twig", [
    "patient" => $patient,
    "errors" => $errors,
    "success" => $success
]);