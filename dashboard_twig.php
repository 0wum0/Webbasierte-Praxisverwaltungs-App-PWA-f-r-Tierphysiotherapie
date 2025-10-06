<?php
declare(strict_types=1);

/**
 * Dashboard with Twig Template Engine
 */

// Bootstrap laden
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/version.php';
require_once __DIR__ . '/vendor/autoload.php';

// Twig initialisieren
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,
]);

// Benutzerinformationen
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Benutzer';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['admin_email'] ?? '';
$userRole = isset($_SESSION['admin_id']) ? 'Administrator' : 'Benutzer';
$isAdmin = isset($_SESSION['admin_id']);

// KPI Daten sammeln
try {
    // Statistiken für diesen Monat
    $currentMonth = date('Y-m');
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT p.id) as total_patients,
            COUNT(DISTINCT o.id) as total_owners,
            COUNT(DISTINCT CASE WHEN DATE_FORMAT(a.date, '%Y-%m') = ? THEN a.id END) as appointments_month,
            COUNT(DISTINCT CASE WHEN DATE_FORMAT(i.invoice_date, '%Y-%m') = ? THEN i.id END) as invoices_month,
            COALESCE(SUM(CASE WHEN DATE_FORMAT(i.invoice_date, '%Y-%m') = ? THEN i.total_amount END), 0) as income_month
        FROM patients p
        LEFT JOIN owners o ON 1=1
        LEFT JOIN appointments a ON 1=1
        LEFT JOIN invoices i ON 1=1
    ");
    $stmt->execute([$currentMonth, $currentMonth, $currentMonth]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Heutige Termine
    $stmt = $db->prepare("
        SELECT COUNT(*) as today_appointments 
        FROM appointments 
        WHERE DATE(date) = CURDATE()
    ");
    $stmt->execute();
    $todayStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Nächste Termine
    $stmt = $db->prepare("
        SELECT a.*, p.name as patient_name, o.name as owner_name
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        LEFT JOIN owners o ON p.owner_id = o.id
        WHERE a.date >= NOW()
        ORDER BY a.date ASC
        LIMIT 5
    ");
    $stmt->execute();
    $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Letzte Aktivitäten
    $stmt = $db->prepare("
        SELECT 
            'appointment' as type,
            CONCAT('Termin mit ', p.name) as description,
            a.date as created_at
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        WHERE a.date < NOW()
        ORDER BY a.date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $stats = [
        'total_patients' => 0,
        'total_owners' => 0,
        'appointments_month' => 0,
        'invoices_month' => 0,
        'income_month' => 0
    ];
    $todayStats = ['today_appointments' => 0];
    $upcomingAppointments = [];
    $recentActivities = [];
}

// Template rendern
echo $twig->render('dashboard.twig', [
    'title' => 'Dashboard',
    'current_page' => 'dashboard',
    'user_name' => $userName,
    'user_email' => $userEmail,
    'user_role' => $userRole,
    'is_admin' => $isAdmin,
    'app_version' => APP_VERSION,
    'theme' => $_SESSION['theme'] ?? 'light',
    
    // KPI Daten
    'totalPatients' => $stats['total_patients'],
    'totalOwners' => $stats['total_owners'],
    'appointmentsMonth' => $stats['appointments_month'],
    'invoicesMonth' => $stats['invoices_month'],
    'incomeMonth' => $stats['income_month'],
    'todayAppointments' => $todayStats['today_appointments'],
    
    // Listen
    'upcomingAppointments' => $upcomingAppointments,
    'recentActivities' => $recentActivities,
]);