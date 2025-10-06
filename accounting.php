<?php
/**
 * Buchhaltung (Accounting) Page
 * Financial overview and management for Tierphysio Manager
 * @package TierphysioManager
 * @version 3.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/twig.php';

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db();
if (!$pdo) {
    throw new RuntimeException('DB connection unavailable');
}

// Get current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Get filter parameters
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)$currentMonth;
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)$currentYear;
$filterStatus = $_GET['status'] ?? 'all';

// Calculate income for current month
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total
    FROM invoices
    WHERE MONTH(created_at) = :month 
    AND YEAR(created_at) = :year
    AND status IN ('paid', 'partially_paid')
");
$stmt->execute([':month' => $filterMonth, ':year' => $filterYear]);
$monthlyIncome = $stmt->fetchColumn();

// Calculate income for current year
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total
    FROM invoices
    WHERE YEAR(created_at) = :year
    AND status IN ('paid', 'partially_paid')
");
$stmt->execute([':year' => $filterYear]);
$yearlyIncome = $stmt->fetchColumn();

// Calculate total income all time
$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) as total
    FROM invoices
    WHERE status IN ('paid', 'partially_paid')
");
$totalIncome = $stmt->fetchColumn();

// Calculate outstanding invoices
$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) as total
    FROM invoices
    WHERE status IN ('open', 'overdue', 'partially_paid')
");
$outstandingAmount = $stmt->fetchColumn();

// Count invoices by status
$stmt = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count,
        COALESCE(SUM(amount), 0) as total
    FROM invoices
    GROUP BY status
");
$invoiceStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly revenue for chart (last 12 months)
$chartData = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM invoices
        WHERE DATE_FORMAT(created_at, '%Y-%m') = :month
        AND status IN ('paid', 'partially_paid')
    ");
    $stmt->execute([':month' => $month]);
    $chartData[] = [
        'month' => date('M Y', strtotime($month . '-01')),
        'amount' => $stmt->fetchColumn()
    ];
}

// Get recent transactions
$stmt = $pdo->prepare("
    SELECT 
        i.id,
        i.invoice_number,
        i.amount,
        i.status,
        i.due_date,
        i.created_at,
        i.paid_date,
        p.name as patient_name,
        o.firstname,
        o.lastname
    FROM invoices i
    LEFT JOIN patients p ON i.patient_id = p.id
    LEFT JOIN owners o ON p.owner_id = o.id
    WHERE (:status = 'all' OR i.status = :status)
    AND MONTH(i.created_at) = :month
    AND YEAR(i.created_at) = :year
    ORDER BY i.created_at DESC
    LIMIT 50
");

$stmt->execute([
    ':status' => $filterStatus,
    ':month' => $filterMonth,
    ':year' => $filterYear
]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get tax summary (assuming 19% VAT)
$vatRate = 0.19;
$netIncome = $monthlyIncome / (1 + $vatRate);
$vatAmount = $monthlyIncome - $netIncome;

// Prepare data for template
$data = [
    'title' => 'Buchhaltung',
    'monthlyIncome' => $monthlyIncome,
    'yearlyIncome' => $yearlyIncome,
    'totalIncome' => $totalIncome,
    'outstandingAmount' => $outstandingAmount,
    'invoiceStats' => $invoiceStats,
    'chartData' => $chartData,
    'transactions' => $transactions,
    'netIncome' => $netIncome,
    'vatAmount' => $vatAmount,
    'vatRate' => $vatRate * 100,
    'filterMonth' => $filterMonth,
    'filterYear' => $filterYear,
    'filterStatus' => $filterStatus,
    'currentMonth' => $currentMonth,
    'currentYear' => $currentYear
];

// Render template
echo twig()->render('accounting.twig', $data);