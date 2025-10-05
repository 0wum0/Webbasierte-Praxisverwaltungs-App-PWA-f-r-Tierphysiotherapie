<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Alle Termine mit Patient & Besitzer zurückgeben
    $stmt = $pdo->query("
        SELECT a.id, a.appointment_date, a.notes,
               p.name AS patient_name,
               o.firstname, o.lastname
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN owners o ON p.owner_id = o.id
        ORDER BY a.appointment_date ASC
    ");

    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = [
            'id'    => $row['id'],
            'title' => $row['patient_name'] . ' – ' . $row['notes'],
            'start' => $row['appointment_date'],
        ];
    }

    echo json_encode($events);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['action'])) {
        echo json_encode(['error' => 'Keine Aktion angegeben']);
        exit;
    }

    if ($data['action'] === 'create') {
        $date       = $data['date'] ?? null;
        $notes      = $data['notes'] ?? '';
        $patient_id = (int)($data['patient_id'] ?? 0);

        if ($date && $patient_id > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO appointments (appointment_date, notes, patient_id, sync_status)
                VALUES (:date, :notes, :patient_id, 'local')
            ");
            $stmt->execute([
                ":date"       => $date,
                ":notes"      => $notes,
                ":patient_id" => $patient_id
            ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['error' => 'Datum oder Patient fehlt']);
        }
        exit;
    }
}

echo json_encode(['error' => 'Ungültige Anfrage']);