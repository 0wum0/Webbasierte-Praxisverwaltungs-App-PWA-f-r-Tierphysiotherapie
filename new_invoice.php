<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/twig.php";

// Patienten laden für Auswahl
$stmt = $pdo->prepare("
    SELECT p.id, p.name, o.firstname, o.lastname 
    FROM patients p 
    JOIN owners o ON p.owner_id = o.id
    ORDER BY o.lastname, p.name
");
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        $stmt = $pdo->prepare("
            INSERT INTO invoices (patient_id, amount, description, status, created_at)
            VALUES (:patient_id, :amount, :description, 'open', NOW())
        ");
        $stmt->execute([
            ':patient_id' => $patientId,
            ':amount'     => $amount,
            ':description'=> $description
        ]);

        $_SESSION['notify'][] = [
            "type" => "success",
            "msg"  => "✅ Neue Rechnung erfolgreich erstellt."
        ];

        header("Location: invoices.php");
        exit;
    }
}

// Notifications aus der Session ziehen
$notifications = $_SESSION['notify'] ?? [];
unset($_SESSION['notify']);

// Rendern
echo $twig->render("new_invoice.twig", [
    "title"        => "Neue Rechnung",
    "patients"     => $patients,
    "notifications"=> $notifications
]);