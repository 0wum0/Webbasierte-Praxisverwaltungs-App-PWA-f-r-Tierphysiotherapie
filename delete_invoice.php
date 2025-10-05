<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    session_start();
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

session_start();
$_SESSION['notify'][] = [
    "type" => "warning",
    "msg"  => "🗑️ Rechnung #$id wurde gelöscht."
];

// Zurück zur Übersicht
header("Location: invoices.php");
exit;