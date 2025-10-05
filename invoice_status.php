<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

require_once __DIR__ . "/includes/bootstrap.php";

// Rechnungs-ID und Zielstatus abholen
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = $_GET['status'] ?? '';

if ($id <= 0 || !in_array($status, ['open', 'paid'])) {
    $_SESSION['notify'][] = [
        "type" => "error",
        "msg"  => "❌ Ungültiger Aufruf – keine Änderung durchgeführt."
    ];
    header("Location: invoices.php");
    exit;
}

try {
    // Status ändern
    $stmt = $pdo->prepare("
        UPDATE invoices 
        SET status = :status, updated_at = NOW(), sync_status = 'local' 
        WHERE id = :id
    ");
    $stmt->execute([
        ":status" => $status,
        ":id" => $id
    ]);

    // Erfolgsmeldung in Session speichern
    if ($status === 'paid') {
        $_SESSION['notify'][] = [
            "type" => "success",
            "msg"  => "✅ Rechnung #$id wurde als bezahlt markiert."
        ];
    } else {
        $_SESSION['notify'][] = [
            "type" => "info",
            "msg"  => "↩️ Rechnung #$id wurde wieder als offen markiert."
        ];
    }

} catch (Throwable $e) {
    $_SESSION['notify'][] = [
        "type" => "error",
        "msg"  => "❌ Fehler beim Aktualisieren: " . htmlspecialchars($e->getMessage())
    ];
}

// Zurück zur Übersicht
header("Location: invoices.php");
exit;