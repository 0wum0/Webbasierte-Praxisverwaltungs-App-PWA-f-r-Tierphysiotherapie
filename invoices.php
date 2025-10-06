<?php
declare(strict_types=1);

/**
 * Invoices Management Page - Twig Version
 * Unified design with SSoT implementation
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/twig.php';

$db = db();
if (!$db) {
    throw new RuntimeException('DB connection unavailable');
}

// Initialize variables
$invoices = [];
$stats = [
    'total_invoices' => 0,
    'open_invoices' => 0,
    'paid_invoices' => 0,
    'total_amount' => 0,
    'open_amount' => 0,
    'paid_amount' => 0
];
$errors = [];

// Fetch invoice statistics
try {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_invoices,
            COUNT(CASE WHEN status = 'open' THEN 1 END) as open_invoices,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
            COALESCE(SUM(total_amount), 0) as total_amount,
            COALESCE(SUM(CASE WHEN status = 'open' THEN total_amount END), 0) as open_amount,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount END), 0) as paid_amount
        FROM invoices
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching invoice stats: " . $e->getMessage());
}

// Fetch all invoices with patient and owner information
try {
    $stmt = $db->prepare("
        SELECT 
            i.*,
            p.name as patient_name,
            o.firstname as owner_firstname,
            o.lastname as owner_lastname,
            o.email as owner_email
        FROM invoices i
        LEFT JOIN patients p ON i.patient_id = p.id
        LEFT JOIN owners o ON p.owner_id = o.id
        ORDER BY i.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format status for display
    foreach ($invoices as &$invoice) {
        switch ($invoice['status']) {
            case 'paid':
                $invoice['status_badge'] = 'badge-success';
                $invoice['status_text'] = 'Bezahlt';
                break;
            case 'open':
                $invoice['status_badge'] = 'badge-warning';
                $invoice['status_text'] = 'Offen';
                break;
            case 'cancelled':
                $invoice['status_badge'] = 'badge-danger';
                $invoice['status_text'] = 'Storniert';
                break;
            default:
                $invoice['status_badge'] = 'badge-secondary';
                $invoice['status_text'] = 'Entwurf';
        }
        
        // Check if overdue
        if ($invoice['status'] === 'open' && !empty($invoice['due_date'])) {
            if (strtotime($invoice['due_date']) < time()) {
                $invoice['status_badge'] = 'badge-danger';
                $invoice['status_text'] = 'Überfällig';
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching invoices: " . $e->getMessage());
    $errors[] = "Fehler beim Laden der Rechnungen.";
}

// User information
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Benutzer';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['admin_email'] ?? '';
$isAdmin = isset($_SESSION['admin_id']);

// Render with Twig template
echo $twig->render('invoices.twig', [
    'title' => 'Rechnungen - Tierphysio Manager',
    'current_page' => 'invoices',
    'user_name' => $userName,
    'user_email' => $userEmail,
    'is_admin' => $isAdmin,
    'theme' => $_SESSION['theme'] ?? 'light',
    
    // Data
    'invoices' => $invoices,
    'stats' => $stats,
    
    // Messages
    'errors' => $errors
]);