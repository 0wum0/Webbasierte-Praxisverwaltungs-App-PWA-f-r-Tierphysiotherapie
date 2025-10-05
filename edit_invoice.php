<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/includes/twig.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// Rechnung laden
$stmt = $pdo->prepare("
    SELECT i.*, p.name AS patient_name, o.firstname, o.lastname
    FROM invoices i
    JOIN patients p ON i.patient_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE i.id = :id
");
$stmt->execute([":id" => $id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    session_start();
    $_SESSION['notify'][] = [
        "type" => "error",
        "msg"  => "❌ Rechnung nicht gefunden."
    ];
    header("Location: invoices.php");
    exit;
}

// Positionen laden
$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = :id");
$stmt->execute([":id" => $id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $patient_id = (int) ($_POST['patient_id'] ?? 0);
    $status = $_POST['status'] ?? 'open';
    $itemsPost = $_POST['items'] ?? [];

    if ($patient_id <= 0) {
        $errors[] = "Bitte einen Patienten auswählen.";
    }

    // Gesamtsumme berechnen
    $amountTotal = 0;
    foreach ($itemsPost as $item) {
        if (trim($item['description']) !== '' && (float)$item['amount'] > 0) {
            $amountTotal += (float)$item['amount'];
        }
    }

    if ($amountTotal <= 0) {
        $errors[] = "Die Rechnungssumme muss größer als 0 sein.";
    }

    if (empty($errors)) {
        // Hauptrechnung aktualisieren
        $stmt = $pdo->prepare("
            UPDATE invoices
            SET patient_id = :patient_id,
                amount = :amount,
                status = :status,
                updated_at = NOW(),
                sync_status = 'local'
            WHERE id = :id
        ");
        $stmt->execute([
            ":patient_id" => $patient_id,
            ":amount" => $amountTotal,
            ":status" => $status,
            ":id" => $id
        ]);

        // Alte Positionen löschen
        $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = :id")->execute([":id" => $id]);

        // Neue Positionen speichern
        $stmtItem = $pdo->prepare("
            INSERT INTO invoice_items (invoice_id, description, amount)
            VALUES (:invoice_id, :description, :amount)
        ");
        foreach ($itemsPost as $item) {
            if (trim($item['description']) !== '' && (float)$item['amount'] > 0) {
                $stmtItem->execute([
                    ":invoice_id" => $id,
                    ":description" => trim($item['description']),
                    ":amount" => (float)$item['amount']
                ]);
            }
        }

        // Erfolg -> zurück
        session_start();
        $_SESSION['notify'][] = [
            "type" => "success",
            "msg"  => "✅ Rechnung #$id wurde erfolgreich bearbeitet."
        ];
        header("Location: invoices.php");
        exit;
    }
}

// Rendern
echo $twig->render("edit_invoice.twig", [
    "title"    => "Rechnung bearbeiten",
    "invoice"  => $invoice,
    "items"    => $items,
    "patients" => $patients,
    "errors"   => $errors
]);