<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/twig.php";

// Zeitraum definieren
$monthStart = date("Y-m-01");
$yearStart  = date("Y-01-01");

// Einnahmen Monat
$stmt = $pdo->prepare("SELECT SUM(amount) FROM invoices WHERE status = 'paid' AND DATE(updated_at) >= :monthStart");
$stmt->execute([':monthStart' => $monthStart]);
$incomeMonth = (float)$stmt->fetchColumn();

// Einnahmen Jahr
$stmt = $pdo->prepare("SELECT SUM(amount) FROM invoices WHERE status = 'paid' AND DATE(updated_at) >= :yearStart");
$stmt->execute([':yearStart' => $yearStart]);
$incomeYear = (float)$stmt->fetchColumn();

// Gesamtausgaben Jahr (aus settings, später erweiterbar)
$stmt = $pdo->prepare("SELECT SUM(setting_value) FROM settings WHERE setting_key = 'expense' AND updated_at >= :yearStart");
$stmt->execute([':yearStart' => $yearStart]);
$expensesYear = (float)$stmt->fetchColumn();

// Gesamteinnahmen
$stmt = $pdo->query("SELECT SUM(amount) FROM invoices WHERE status = 'paid'");
$incomeTotal = (float)$stmt->fetchColumn();

// Offene Rechnungen
$stmt = $pdo->query("
    SELECT i.*, p.name AS patient_name, o.firstname, o.lastname
    FROM invoices i
    JOIN patients p ON i.patient_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE i.status = 'open'
    ORDER BY i.updated_at DESC
");
$openInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bezahlte Rechnungen (letzte 10)
$stmt = $pdo->query("
    SELECT i.*, p.name AS patient_name, o.firstname, o.lastname
    FROM invoices i
    JOIN patients p ON i.patient_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE i.status = 'paid'
    ORDER BY i.updated_at DESC
    LIMIT 10
");
$paidInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rendern
try {
    echo $twig->render("bookkeeping.twig", [
        "incomeMonth"   => $incomeMonth,
        "incomeYear"    => $incomeYear,
        "incomeTotal"   => $incomeTotal,
        "expensesYear"  => $expensesYear,
        "openInvoices"  => $openInvoices,
        "paidInvoices"  => $paidInvoices
    ]);
} catch (Throwable $e) {
    echo "<pre>Fehler in bookkeeping.twig: " . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}