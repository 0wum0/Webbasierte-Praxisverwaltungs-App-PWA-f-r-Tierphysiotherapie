<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . "/includes/db.php";

$id = (int)($_GET['id'] ?? 0);

// Prüfen ob AJAX (z. B. von fetch())
$isAjax = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = :id");
    $stmt->execute([":id" => $id]);

    if ($isAjax) {
        // JSON für Kalender
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    } else {
        // Redirect + Notification
        $_SESSION['notify'][] = [
            "type" => "success",
            "msg"  => "✅ Termin #$id wurde erfolgreich gelöscht."
        ];
        header("Location: appointments.php");
        exit;
    }
}

// Fehlerfall
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Ungültige ID']);
    exit;
}

$_SESSION['notify'][] = [
    "type" => "error",
    "msg"  => "❌ Ungültige Anfrage – kein Termin gelöscht."
];
header("Location: appointments.php");
exit;