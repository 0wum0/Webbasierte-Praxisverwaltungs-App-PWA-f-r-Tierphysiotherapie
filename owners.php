<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/includes/twig.php";

// -------------------------------
// Besitzer laden (Übersicht)
// -------------------------------
$stmt = $pdo->query("
    SELECT o.*,
           (SELECT COUNT(*) FROM patients p WHERE p.owner_id = o.id) AS patient_count,
           (SELECT COUNT(*) 
              FROM invoices i 
              JOIN patients p ON i.patient_id = p.id 
             WHERE p.owner_id = o.id) AS invoice_count,
           (SELECT COUNT(*) 
              FROM appointments a 
              JOIN patients p ON a.patient_id = p.id 
             WHERE p.owner_id = o.id) AS appointment_count,
           (SELECT COUNT(*) 
              FROM invoices i 
              JOIN patients p ON i.patient_id = p.id 
             WHERE p.owner_id = o.id AND i.status = 'open') AS open_invoices
    FROM owners o
    ORDER BY o.lastname ASC, o.firstname ASC
");
$owners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------
// Details laden (pro Besitzer)
// -------------------------------
$details = [];

foreach ($owners as $o) {
    $oid = (int)$o['id'];

    // Patienten
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE owner_id = :oid ORDER BY name ASC");
    $stmt->execute([":oid" => $oid]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rechnungen
    $stmt = $pdo->prepare("
        SELECT i.*, p.name AS patient_name
        FROM invoices i
        JOIN patients p ON i.patient_id = p.id
        WHERE p.owner_id = :oid
        ORDER BY i.id DESC
    ");
    $stmt->execute([":oid" => $oid]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Termine
    $stmt = $pdo->prepare("
        SELECT a.*, p.name AS patient_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE p.owner_id = :oid
        ORDER BY a.appointment_date DESC
    ");
    $stmt->execute([":oid" => $oid]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Notizen
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE owner_id = :oid ORDER BY created_at DESC");
    $stmt->execute([":oid" => $oid]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $details[$oid] = [
        "patients"     => $patients,
        "invoices"     => $invoices,
        "appointments" => $appointments,
        "notes"        => $notes
    ];
}

// -------------------------------
// Notifications
// -------------------------------
$notifications = $_SESSION['notify'] ?? [];
unset($_SESSION['notify']);

// -------------------------------
// Render
// -------------------------------
echo $twig->render("owners.twig", [
    "title"        => "Besitzerverwaltung",
    "owners"       => $owners,
    "details"      => $details,
    "notifications"=> $notifications
]);