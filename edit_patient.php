<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/twig.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success = false;

// Besitzer laden (Dropdown)
$owners = $pdo->query("SELECT id, firstname, lastname FROM owners ORDER BY lastname ASC")->fetchAll(PDO::FETCH_ASSOC);

// Patient laden, falls Bearbeitung
$patient = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Formular abgesendet?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_id   = (int)($_POST['owner_id'] ?? 0);
    $name       = trim($_POST['name'] ?? '');
    $species    = trim($_POST['species'] ?? '');
    $breed      = trim($_POST['breed'] ?? '');
    $birthdate  = $_POST['birthdate'] ?? null;
    $findings   = trim($_POST['findings'] ?? '');
    $medications= trim($_POST['medications'] ?? '');
    $therapies  = trim($_POST['therapies'] ?? '');
    $symptoms   = trim($_POST['symptoms'] ?? '');
    $extras     = trim($_POST['extras'] ?? '');
    $notes      = trim($_POST['notes'] ?? '');
    $imagePath  = $patient['image'] ?? null;

    // Bild-Upload
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . "/uploads/patients/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $target   = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $imagePath = "uploads/patients/" . $fileName;
        } else {
            $errors[] = "Fehler beim Hochladen des Bildes.";
        }
    }

    if ($owner_id <= 0) {
        $errors[] = "Bitte einen Besitzer auswählen.";
    }
    if ($name === '') {
        $errors[] = "Bitte einen Namen eingeben.";
    }

    if (empty($errors)) {
        if ($id > 0) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE patients
                SET owner_id=:owner_id, name=:name, species=:species, breed=:breed,
                    birthdate=:birthdate, findings=:findings, medications=:medications,
                    therapies=:therapies, symptoms=:symptoms, extras=:extras,
                    notes=:notes, image=:image, sync_status='local'
                WHERE id=:id
            ");
            $stmt->execute([
                ":owner_id" => $owner_id,
                ":name" => $name,
                ":species" => $species,
                ":breed" => $breed,
                ":birthdate" => $birthdate,
                ":findings" => $findings,
                ":medications" => $medications,
                ":therapies" => $therapies,
                ":symptoms" => $symptoms,
                ":extras" => $extras,
                ":notes" => $notes,
                ":image" => $imagePath,
                ":id" => $id
            ]);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO patients (owner_id, name, species, breed, birthdate, findings,
                    medications, therapies, symptoms, extras, notes, image, sync_status)
                VALUES (:owner_id, :name, :species, :breed, :birthdate, :findings,
                    :medications, :therapies, :symptoms, :extras, :notes, :image, 'local')
            ");
            $stmt->execute([
                ":owner_id" => $owner_id,
                ":name" => $name,
                ":species" => $species,
                ":breed" => $breed,
                ":birthdate" => $birthdate,
                ":findings" => $findings,
                ":medications" => $medications,
                ":therapies" => $therapies,
                ":symptoms" => $symptoms,
                ":extras" => $extras,
                ":notes" => $notes,
                ":image" => $imagePath
            ]);
            $id = (int)$pdo->lastInsertId();
        }
        $success = true;
        // Nach Speichern zurück zur Patientenübersicht
        header("Location: patients.php");
        exit;
    }
}

// Render
echo $twig->render("edit_patient.twig", [
    "patient" => $patient,
    "owners" => $owners,
    "errors" => $errors,
    "success" => $success
]);