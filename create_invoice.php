<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/twig.php";

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;

$errors = [];

// Patient laden (falls direkt gewählt)
$patient = null;
if ($patient_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
    $stmt->execute([":id" => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Patientenliste für Dropdown
$stmt = $pdo->query("
    SELECT p.id, p.name AS patient_name, o.firstname, o.lastname
    FROM patients p
    JOIN owners o ON p.owner_id = o.id
    ORDER BY o.lastname, p.name
");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formular abgesendet?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'open';
    $items = $_POST['items'] ?? [];

    if (empty($items)) {
        $errors[] = "Bitte mindestens eine Position hinzufügen.";
    }

    // Gesamtsumme berechnen
    $amountTotal = 0;
    foreach ($items as $item) {
        if (trim($item['description']) !== '' && (float)$item['amount'] > 0) {
            $amountTotal += (float)$item['amount'];
        }
    }

    if ($amountTotal <= 0) {
        $errors[] = "Die Rechnungssumme muss größer als 0 sein.";
    }

    if (empty($errors)) {
        // Hauptrechnung speichern
        $stmt = $pdo->prepare("
            INSERT INTO invoices (patient_id, appointment_id, amount, status, sync_status)
            VALUES (:patient_id, :appointment_id, :amount, :status, 'local')
        ");
        $stmt->execute([
            ":patient_id" => (int)($_POST['patient_id'] ?? $patient_id),
            ":appointment_id" => $appointment_id ?: null,
            ":amount" => $amountTotal,
            ":status" => $status
        ]);
        $invoice_id = (int)$pdo->lastInsertId();

        // Positionen speichern
        $stmtItem = $pdo->prepare("
            INSERT INTO invoice_items (invoice_id, description, amount)
            VALUES (:invoice_id, :description, :amount)
        ");
        foreach ($items as $item) {
            if (trim($item['description']) !== '' && (float)$item['amount'] > 0) {
                $stmtItem->execute([
                    ":invoice_id" => $invoice_id,
                    ":description" => trim($item['description']),
                    ":amount" => (float)$item['amount']
                ]);
            }
        }

        // Erfolg -> Notification
        $_SESSION['notify'][] = [
            "type" => "success",
            "msg"  => "✅ Rechnung #$invoice_id wurde erfolgreich erstellt."
        ];

        header("Location: invoices.php");
        exit;
    } else {
        // Fehler -> Notifications
        foreach ($errors as $err) {
            $_SESSION['notify'][] = [
                "type" => "error",
                "msg"  => "❌ $err"
            ];
        }
    }
}

// Rendern
echo $twig->render("create_invoice.twig", [
    "title"    => "Rechnung erstellen",
    "patient"  => $patient,
    "patients" => $patients,
    "errors"   => $errors
]);