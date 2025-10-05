<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/includes/twig.php";

// --------------------------
// Neue Rechnung speichern
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId   = $_POST['patient_id'] ?? null;
    $amount      = $_POST['amount'] ?? null;
    $description = $_POST['description'] ?? null;

    if (!$patientId || !$amount) {
        $_SESSION['notify'][] = [
            "type" => "error",
            "msg"  => "❌ Bitte Patient und Betrag eingeben!"
        ];
    } else {
        try {
            // Prüfen ob Spalte description existiert
            $columns = $pdo->query("SHOW COLUMNS FROM invoices")->fetchAll(PDO::FETCH_COLUMN);

            if (in_array('description', $columns)) {
                $stmt = $pdo->prepare("
                    INSERT INTO invoices (patient_id, amount, description, status, created_at)
                    VALUES (:patient_id, :amount, :description, 'open', NOW())
                ");
                $stmt->execute([
                    ':patient_id' => $patientId,
                    ':amount'     => $amount,
                    ':description'=> $description
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO invoices (patient_id, amount, status, created_at)
                    VALUES (:patient_id, :amount, 'open', NOW())
                ");
                $stmt->execute([
                    ':patient_id' => $patientId,
                    ':amount'     => $amount
                ]);
            }

            $_SESSION['notify'][] = [
                "type" => "success",
                "msg"  => "✅ Neue Rechnung erfolgreich erstellt."
            ];
        } catch (Throwable $e) {
            $_SESSION['notify'][] = [
                "type" => "error",
                "msg"  => "❌ Fehler beim Speichern: " . $e->getMessage()
            ];
        }
    }

    header("Location: invoices.php");
    exit;
}

// --------------------------
// Offene Rechnungen laden
// --------------------------
$stmt = $pdo->query("
    SELECT i.*, p.name AS patient_name, o.firstname, o.lastname
    FROM invoices i
    JOIN patients p ON i.patient_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE i.status = 'open'
    ORDER BY i.created_at DESC
");
$openInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------------------
// Bezahlte Rechnungen laden
// --------------------------
$stmt = $pdo->query("
    SELECT i.*, p.name AS patient_name, o.firstname, o.lastname
    FROM invoices i
    JOIN patients p ON i.patient_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE i.status = 'paid'
    ORDER BY i.updated_at DESC
");
$paidInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------------------
// Patientenliste für Modal
// --------------------------
$stmt = $pdo->query("
    SELECT p.id, p.name, o.firstname, o.lastname
    FROM patients p
    JOIN owners o ON p.owner_id = o.id
    ORDER BY o.lastname, p.name
");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------------------
// Rendern
// --------------------------
echo $twig->render("invoices.twig", [
    "title"        => "Rechnungen",
    "openInvoices" => $openInvoices,
    "paidInvoices" => $paidInvoices,
    "patients"     => $patients
]);