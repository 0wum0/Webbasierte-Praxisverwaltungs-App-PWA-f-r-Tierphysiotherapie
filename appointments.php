<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/twig.php";

// Termine laden
$stmt = $pdo->query("
    SELECT a.*, p.name AS patient_name, o.firstname, o.lastname
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN owners o ON p.owner_id = o.id
    ORDER BY a.appointment_date ASC
");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Patienten für Dropdown
$stmt = $pdo->query("
    SELECT p.id, p.name, o.firstname, o.lastname
    FROM patients p
    JOIN owners o ON p.owner_id = o.id
    ORDER BY p.name ASC
");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rendern
echo $twig->render("appointments.twig", [
    "title"       => "Termine",
    "appointments"=> $appointments,
    "patients"    => $patients
]);