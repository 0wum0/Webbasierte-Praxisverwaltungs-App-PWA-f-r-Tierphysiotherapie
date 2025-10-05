<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/twig.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

$errors = [];

// Patient laden (für Anzeige im Formular)
$patient = null;
if ($patient_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
    $stmt->execute([":id" => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Termin laden, falls Bearbeitung
$appointment = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($appointment) {
        $patient_id = (int)$appointment['patient_id'];
        if (!$patient) {
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
            $stmt->execute([":id" => $patient_id]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// Formular abgesendet?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_date = $_POST['appointment_date'] ?? '';
    $duration = (int)($_POST['duration'] ?? 60);
    $notes = trim($_POST['notes'] ?? '');

    if ($appointment_date === '') {
        $errors[] = "Bitte Datum und Uhrzeit wählen.";
    }

    if (empty($errors)) {
        if ($id > 0) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE appointments
                SET appointment_date=:appointment_date, duration=:duration, notes=:notes, sync_status='local'
                WHERE id=:id
            ");
            $stmt->execute([
                ":appointment_date" => $appointment_date,
                ":duration" => $duration,
                ":notes" => $notes,
                ":id" => $id
            ]);

            $_SESSION['notify'][] = [
                "type" => "success",
                "msg"  => "✅ Termin #$id wurde erfolgreich aktualisiert."
            ];
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO appointments (patient_id, appointment_date, duration, notes, sync_status)
                VALUES (:patient_id, :appointment_date, :duration, :notes, 'local')
            ");
            $stmt->execute([
                ":patient_id" => $patient_id,
                ":appointment_date" => $appointment_date,
                ":duration" => $duration,
                ":notes" => $notes
            ]);
            $id = (int)$pdo->lastInsertId();

            $_SESSION['notify'][] = [
                "type" => "success",
                "msg"  => "➕ Neuer Termin für Patient #$patient_id wurde angelegt."
            ];
        }

        // Nach Speichern zurück zur Patientenseite
        header("Location: patient.php?id=" . $patient_id);
        exit;
    } else {
        // Fehler → Notifications
        foreach ($errors as $err) {
            $_SESSION['notify'][] = [
                "type" => "error",
                "msg"  => "❌ $err"
            ];
        }
    }
}

// Render
echo $twig->render("edit_appointment.twig", [
    "patient" => $patient,
    "appointment" => $appointment,
    "errors" => $errors
]);