<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || empty($data['patient_id']) || empty($data['date'])) {
        throw new Exception("Ungültige Eingabe.");
    }

    $stmt = $pdo->prepare("
        INSERT INTO appointments (patient_id, appointment_date, notes, created_at)
        VALUES (:pid, :date, :notes, NOW())
    ");
    $stmt->execute([
        ':pid'   => (int)$data['patient_id'],
        ':date'  => $data['date'],
        ':notes' => $data['notes'] ?? null
    ]);

    echo json_encode([
        "success" => true,
        "msg" => "Behandlung erfolgreich hinzugefügt."
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "msg" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}