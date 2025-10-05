<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/includes/logger.php";

session_start();

// Nur POST-Requests erlauben für Lösch-Operationen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// CSRF-Schutz
try {
    csrf_validate();
} catch (RuntimeException $e) {
    logWarning('CSRF validation failed on delete_invoice', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);
    $_SESSION['notify'][] = [
        "type" => "error",
        "msg" => "❌ " . htmlspecialchars($e->getMessage()),
    ];
    header("Location: invoices.php");
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    $_SESSION['notify'][] = [
        "type" => "error",
        "msg"  => "❌ Ungültige Rechnungs-ID."
    ];
    header("Location: invoices.php");
    exit;
}

// Rechnung löschen (Positionen sind über FOREIGN KEY mit ON DELETE CASCADE verknüpft)
$stmt = $pdo->prepare("DELETE FROM invoices WHERE id = :id");
$stmt->execute([":id" => $id]);

logInfo('Invoice deleted', ['invoice_id' => $id, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

$_SESSION['notify'][] = [
    "type" => "warning",
    "msg"  => "🗑️ Rechnung #$id wurde gelöscht."
];

// Zurück zur Übersicht
header("Location: invoices.php");
exit;