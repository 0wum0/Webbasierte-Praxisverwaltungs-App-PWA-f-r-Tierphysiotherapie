<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/includes/db.php";   // DB Verbindung
require_once __DIR__ . "/includes/twig.php"; // Twig Setup

// Patienten laden (mit Besitzer)
$search = $_GET['search'] ?? '';
$patients = [];

try {
    if ($search) {
        $stmt = $pdo->prepare("
            SELECT p.*, o.firstname, o.lastname 
            FROM patients p
            JOIN owners o ON p.owner_id = o.id
            WHERE p.name LIKE :s1
               OR o.lastname LIKE :s2
               OR o.firstname LIKE :s3
            ORDER BY p.name ASC
        ");
        $stmt->execute([
            ':s1' => "%$search%",
            ':s2' => "%$search%",
            ':s3' => "%$search%"
        ]);
    } else {
        $stmt = $pdo->query("
            SELECT p.*, o.firstname, o.lastname 
            FROM patients p
            JOIN owners o ON p.owner_id = o.id
            ORDER BY p.name ASC
        ");
    }

    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    echo "<pre>Fehler in patients.php: " . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}

// Rendern
try {
    echo $twig->render("patients.twig", [
        "title"    => "Patientenliste",
        "patients" => $patients,
        "search"   => $search
    ]);
} catch (Throwable $e) {
    echo "<pre>Fehler im patients.twig: " . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}