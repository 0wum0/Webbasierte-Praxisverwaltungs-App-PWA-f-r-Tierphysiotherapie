<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/db.php";

session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = $_GET['status'] ?? '';

if ($id > 0 && in_array($status, ['open', 'paid'])) {
    $stmt = $pdo->prepare("
        UPDATE invoices
        SET status = :status,
            sync_status = 'local'
        WHERE id = :id
    ");
    $stmt->execute([
        ":status" => $status,
        ":id" => $id
    ]);

    // Notification je nach Status
    if ($status === 'paid') {
        $_SESSION['notify'][] = [
            "type" => "success",
            "msg"  => "✅ Rechnung #$id wurde als bezahlt markiert."
        ];
    } else {
        $_SESSION['notify'][] = [
            "type" => "warning",
            "msg"  => "↩️ Rechnung #$id wurde wieder als offen markiert."
        ];
    }
} else {
    $_SESSION['notify'][] = [
        "type" => "error",
        "msg"  => "❌ Ungültiger Aufruf – keine Aktion ausgeführt."
    ];
}

// Zurück zur Rechnungsübersicht
header("Location: invoices.php");
exit;