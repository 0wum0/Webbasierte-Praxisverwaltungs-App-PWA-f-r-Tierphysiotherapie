<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

$results = [];

// --- Patienten suchen ---
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.species, o.lastname
    FROM patients p
    JOIN owners o ON p.owner_id = o.id
    WHERE p.name LIKE :q1 OR o.lastname LIKE :q2
    LIMIT 10
");
$stmt->execute([
    ':q1' => "%$q%",
    ':q2' => "%$q%"
]);
foreach ($stmt as $row) {
    $results[] = [
        "type"  => "patient",
        "id"    => $row['id'],
        "label" => $row['name']." (".$row['species'].") – ".$row['lastname']
    ];
}

// --- Besitzer suchen ---
$stmt = $pdo->prepare("
    SELECT id, firstname, lastname
    FROM owners
    WHERE firstname LIKE :q1 OR lastname LIKE :q2
    LIMIT 10
");
$stmt->execute([
    ':q1' => "%$q%",
    ':q2' => "%$q%"
]);
foreach ($stmt as $row) {
    $results[] = [
        "type"  => "owner",
        "id"    => $row['id'],
        "label" => $row['firstname']." ".$row['lastname']
    ];
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);