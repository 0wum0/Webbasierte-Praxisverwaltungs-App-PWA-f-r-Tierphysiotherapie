<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);

$id = (int)($data['id'] ?? 0);
$newDate = $data['newDate'] ?? null;

if ($id > 0 && $newDate) {
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET appointment_date = :date, sync_status = 'local' 
        WHERE id = :id
    ");
    $stmt->execute([
        ":date" => $newDate,
        ":id" => $id
    ]);

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Ungültige Daten']);