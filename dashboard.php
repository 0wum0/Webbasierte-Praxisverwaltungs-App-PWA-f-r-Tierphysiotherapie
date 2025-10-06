<?php
declare(strict_types=1);

/**
 * KPI Dashboard 2.0 with Twig Template Engine
 * Unified design with SSoT implementation
 */

// Bootstrap laden
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/version.php';
require_once __DIR__ . '/includes/twig.php';

// Benutzerinformationen abrufen
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Benutzer';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['admin_email'] ?? '';
$isAdmin = isset($_SESSION['admin_id']);
$userRole = $isAdmin ? 'Administrator' : 'Benutzer';

// Check for available updates
$updateAvailable = $_SESSION['update_available'] ?? false;
$updateFromVersion = $_SESSION['update_from_version'] ?? null;
$updateToVersion = $_SESSION['update_to_version'] ?? null;

// Initialize all variables to prevent undefined errors
$stats = [
    'total_patients' => 0,
    'total_owners' => 0,
    'appointments_month' => 0,
    'invoices_month' => 0,
    'income_month' => 0,
    'income_year' => 0,
    'total_income' => 0,
    'open_invoices' => 0,
    'treatments_week' => 0
];
$todayStats = ['today_appointments' => 0];
$upcomingAppointments = [];
$recentActivities = [];
$monthlyIncome = [];
$appointmentTrend = [];
$treatmentStats = [];
$topPatients = [];
$birthdaysThisMonth = [];
$overdueInvoices = [];
$appointmentsToday = [];
$birthdayOwners = [];
$birthdayPatients = [];
$birthdaySuccess = '';
$totalExpenses = 0;

// Get database connection
$db = db();

if ($db) {
    try {
        // Current month and year
        $currentMonth = date('Y-m');
        $currentYear = date('Y');
        $lastWeek = date('Y-m-d', strtotime('-7 days'));
        
        // Main statistics
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT p.id) as total_patients,
                COUNT(DISTINCT o.id) as total_owners
            FROM patients p
            LEFT JOIN owners o ON 1=1
        ");
        $stmt->execute();
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Appointments and invoices for this month
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT CASE WHEN DATE_FORMAT(a.appointment_date, '%Y-%m') = ? THEN a.id END) as appointments_month,
                COUNT(DISTINCT CASE WHEN DATE_FORMAT(i.invoice_date, '%Y-%m') = ? THEN i.id END) as invoices_month,
                COALESCE(SUM(CASE WHEN DATE_FORMAT(i.invoice_date, '%Y-%m') = ? AND i.status = 'paid' THEN i.total_amount END), 0) as income_month,
                COALESCE(SUM(CASE WHEN DATE_FORMAT(i.invoice_date, '%Y') = ? AND i.status = 'paid' THEN i.total_amount END), 0) as income_year,
                COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total_amount END), 0) as total_income,
                COUNT(DISTINCT CASE WHEN i.status = 'open' THEN i.id END) as open_invoices,
                COUNT(DISTINCT CASE WHEN i.status = 'paid' THEN i.id END) as paid_invoices,
                COUNT(DISTINCT CASE WHEN i.status = 'open' AND i.due_date < CURDATE() THEN i.id END) as overdue_invoices,
                COALESCE(SUM(CASE WHEN DATE(i.invoice_date) = CURDATE() AND i.status = 'paid' THEN i.total_amount END), 0) as income_today
            FROM appointments a
            LEFT JOIN invoices i ON 1=1
        ");
        $stmt->execute([$currentMonth, $currentMonth, $currentMonth, $currentYear]);
        $monthStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Treatments this week (if table exists)
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) as treatments_week 
                FROM treatments 
                WHERE date >= ?
            ");
            $stmt->execute([$lastWeek]);
            $treatmentCount = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['treatments_week'] = $treatmentCount['treatments_week'] ?? 0;
        } catch (Exception $e) {
            $stats['treatments_week'] = 0;
        }
        
        // Merge all stats
        $stats = array_merge($stats, $counts, $monthStats);
        
        // Today's appointments
        $stmt = $db->prepare("
            SELECT COUNT(*) as today_appointments 
            FROM appointments 
            WHERE DATE(appointment_date) = CURDATE()
        ");
        $stmt->execute();
        $todayStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Upcoming appointments
        $stmt = $db->prepare("
            SELECT 
                a.*,
                p.name as patient_name,
                o.firstname as owner_firstname,
                o.lastname as owner_lastname
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN owners o ON p.owner_id = o.id
            WHERE a.appointment_date >= NOW()
            ORDER BY a.appointment_date ASC
            LIMIT 5
        ");
        $stmt->execute();
        $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent activities
        $stmt = $db->prepare("
            SELECT * FROM (
                SELECT 
                    'patient' as type,
                    CONCAT('Neuer Patient: ', name) as description,
                    created_at,
                    id
                FROM patients
                WHERE created_at IS NOT NULL
                
                UNION ALL
                
                SELECT 
                    'invoice' as type,
                    CONCAT('Rechnung #', invoice_number, ' erstellt') as description,
                    created_at,
                    id
                FROM invoices
                WHERE created_at IS NOT NULL
                
                UNION ALL
                
                SELECT 
                    'appointment' as type,
                    CONCAT('Termin gebucht') as description,
                    created_at,
                    id
                FROM appointments
                WHERE created_at IS NOT NULL
            ) as activities
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Monthly income Chart.js data (last 12 months)
        $stmt = $db->prepare("
            SELECT 
                DATE_FORMAT(invoice_date, '%Y-%m') as month,
                SUM(total_amount) as amount
            FROM invoices
            WHERE status = 'paid'
                AND invoice_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute();
        $monthlyIncomeData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill in missing months with 0
        $monthlyIncome = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthKey = date('Y-m', strtotime("-$i months"));
            $monthlyIncome[$monthKey] = 0;
        }
        foreach ($monthlyIncomeData as $row) {
            $monthlyIncome[$row['month']] = (float)$row['amount'];
        }
        
        // Appointment trend (last 7 days)
        $appointmentTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM appointments 
                WHERE DATE(appointment_date) = ?
            ");
            $stmt->execute([$date]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $appointmentTrend[date('d.m', strtotime($date))] = $result['count'];
        }
        
        // Treatment statistics by type (if table exists)
        try {
            $stmt = $db->prepare("
                SELECT 
                    treatment_type,
                    COUNT(*) as count
                FROM treatments
                WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY treatment_type
                ORDER BY count DESC
                LIMIT 5
            ");
            $stmt->execute();
            $treatmentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $treatmentStats = [];
        }
        
        // Top patients by revenue
        $stmt = $db->prepare("
            SELECT 
                p.name as patient_name,
                COUNT(DISTINCT i.id) as invoice_count,
                SUM(i.total_amount) as total_revenue
            FROM patients p
            JOIN invoices i ON i.patient_id = p.id
            WHERE i.status = 'paid'
            GROUP BY p.id
            ORDER BY total_revenue DESC
            LIMIT 5
        ");
        $stmt->execute();
        $topPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Birthdays this month
        $stmt = $db->prepare("
            SELECT 
                p.name as patient_name,
                p.birthdate,
                o.firstname as owner_firstname,
                o.lastname as owner_lastname,
                o.email as owner_email
            FROM patients p
            JOIN owners o ON p.owner_id = o.id
            WHERE MONTH(p.birthdate) = MONTH(CURRENT_DATE())
            ORDER BY DAY(p.birthdate) ASC
            LIMIT 10
        ");
        $stmt->execute();
        $birthdaysThisMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Overdue invoices
        $stmt = $db->prepare("
            SELECT 
                i.*,
                p.name as patient_name,
                o.firstname as owner_firstname,
                o.lastname as owner_lastname,
                DATEDIFF(NOW(), i.due_date) as days_overdue
            FROM invoices i
            LEFT JOIN patients p ON i.patient_id = p.id
            LEFT JOIN owners o ON p.owner_id = o.id
            WHERE i.status = 'open' 
                AND i.due_date < CURDATE()
            ORDER BY i.due_date ASC
            LIMIT 5
        ");
        $stmt->execute();
        $overdueInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Today's appointments with details
        $stmt = $db->prepare("
            SELECT 
                a.*,
                p.name as patient_name,
                p.id as patient_id,
                o.firstname,
                o.lastname
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN owners o ON p.owner_id = o.id
            WHERE DATE(a.appointment_date) = CURDATE()
            ORDER BY a.appointment_date ASC
        ");
        $stmt->execute();
        $appointmentsToday = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Today's birthdays - Owners
        $stmt = $db->prepare("
            SELECT 
                o.id,
                o.firstname,
                o.lastname,
                o.email,
                o.birthdate
            FROM owners o
            WHERE DATE_FORMAT(o.birthdate, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
        ");
        $stmt->execute();
        $birthdayOwners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Today's birthdays - Patients
        $stmt = $db->prepare("
            SELECT 
                p.id,
                p.name,
                p.birthdate,
                o.firstname,
                o.lastname,
                o.email
            FROM patients p
            LEFT JOIN owners o ON p.owner_id = o.id
            WHERE DATE_FORMAT(p.birthdate, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
        ");
        $stmt->execute();
        $birthdayPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total expenses (open invoices total)
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM invoices
            WHERE status = 'open'
        ");
        $stmt->execute();
        $totalExpenses = $stmt->fetchColumn();
        
        // Check for birthday success message
        if (isset($_GET['birthday_success'])) {
            $birthdaySuccess = 'Geburtstagsgrüße wurden erfolgreich versendet!';
        }
        
    } catch (Exception $e) {
        error_log("Dashboard error: " . $e->getMessage());
    }
}

// Render with Twig template
echo $twig->render('dashboard.twig', [
    'title' => 'Dashboard - Tierphysio Manager',
    'current_page' => 'dashboard',
    'user_name' => $userName,
    'user_email' => $userEmail,
    'user_role' => $userRole,
    'is_admin' => $isAdmin,
    'theme' => $_SESSION['theme'] ?? 'light',
    
    // Update info
    'update_available' => $updateAvailable,
    'update_from_version' => $updateFromVersion,
    'update_to_version' => $updateToVersion,
    
    // KPI Data
    'totalPatients' => $stats['total_patients'],
    'totalOwners' => $stats['total_owners'],
    'appointmentsMonth' => $stats['appointments_month'],
    'invoicesMonth' => $stats['invoices_month'],
    'incomeMonth' => $stats['income_month'],
    'incomeYear' => $stats['income_year'],
    'totalIncome' => $stats['total_income'],
    'openInvoices' => $stats['open_invoices'],
    'treatmentsWeek' => $stats['treatments_week'],
    'todayAppointments' => $todayStats['today_appointments'],
    
    // Additional stats for template
    'stats' => $stats,
    'todayStats' => $todayStats,
    
    // Lists and charts
    'upcomingAppointments' => $upcomingAppointments,
    'recentActivities' => $recentActivities,
    'monthlyIncome' => $monthlyIncome,
    'appointmentTrend' => $appointmentTrend,
    'treatmentStats' => $treatmentStats,
    'topPatients' => $topPatients,
    'birthdaysThisMonth' => $birthdaysThisMonth,
    'overdueInvoices' => $overdueInvoices,
    'appointmentsToday' => $appointmentsToday,
    'birthdayOwners' => $birthdayOwners,
    'birthdayPatients' => $birthdayPatients,
    'birthdaySuccess' => $birthdaySuccess,
    'totalExpenses' => $totalExpenses,
]);