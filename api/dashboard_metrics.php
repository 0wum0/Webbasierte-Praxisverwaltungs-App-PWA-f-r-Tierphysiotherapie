<?php
declare(strict_types=1);

/**
 * KPI Dashboard 2.0 API Endpoint
 * 
 * Provides real-time metrics for the dashboard including:
 * - Today's appointments
 * - Active patients
 * - Revenue statistics
 * - Birthday reminders
 * - Invoice status
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Include bootstrap
require_once dirname(__DIR__) . '/includes/bootstrap.php';

// Check authentication
if (!function_exists('auth_check') || !auth_check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Today's date
    $today = date('Y-m-d');
    $currentMonth = date('Y-m');
    $currentYear = date('Y');
    $currentWeekStart = date('Y-m-d', strtotime('monday this week'));
    $currentWeekEnd = date('Y-m-d', strtotime('sunday this week'));
    
    // Initialize metrics
    $metrics = [
        'timestamp' => time(),
        'date' => $today
    ];
    
    // 1. Appointments Today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
               SUM(CASE WHEN status IN ('scheduled', 'confirmed') THEN 1 ELSE 0 END) as pending
        FROM appointments 
        WHERE date = :today
    ");
    $stmt->execute([':today' => $today]);
    $appointments = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $metrics['appointments_today'] = [
        'total' => (int)($appointments['total'] ?? 0),
        'completed' => (int)($appointments['completed'] ?? 0),
        'pending' => (int)($appointments['pending'] ?? 0)
    ];
    
    // 2. Active Patients (patients with appointments in last 3 months)
    $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.id) as active_patients
        FROM patients p
        INNER JOIN appointments a ON p.id = a.patient_id
        WHERE a.date >= :three_months_ago
    ");
    $stmt->execute([':three_months_ago' => $threeMonthsAgo]);
    $metrics['active_patients'] = (int)$stmt->fetchColumn();
    
    // 3. New Patients This Week
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as new_patients
        FROM patients
        WHERE created_at >= :week_start 
        AND created_at <= :week_end
    ");
    $stmt->execute([
        ':week_start' => $currentWeekStart . ' 00:00:00',
        ':week_end' => $currentWeekEnd . ' 23:59:59'
    ]);
    $metrics['new_patients_week'] = (int)$stmt->fetchColumn();
    
    // 4. Revenue Statistics
    // Today's revenue
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) as revenue
        FROM invoices
        WHERE date = :today
        AND status != 'cancelled'
    ");
    $stmt->execute([':today' => $today]);
    $todayRevenue = (float)$stmt->fetchColumn();
    
    // This month's revenue
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) as revenue
        FROM invoices
        WHERE DATE_FORMAT(date, '%Y-%m') = :month
        AND status != 'cancelled'
    ");
    $stmt->execute([':month' => $currentMonth]);
    $monthRevenue = (float)$stmt->fetchColumn();
    
    // This year's revenue
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) as revenue
        FROM invoices
        WHERE YEAR(date) = :year
        AND status != 'cancelled'
    ");
    $stmt->execute([':year' => $currentYear]);
    $yearRevenue = (float)$stmt->fetchColumn();
    
    // Total revenue (all time)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) as revenue
        FROM invoices
        WHERE status != 'cancelled'
    ");
    $stmt->execute();
    $totalRevenue = (float)$stmt->fetchColumn();
    
    $metrics['revenue'] = [
        'today' => round($todayRevenue, 2),
        'month' => round($monthRevenue, 2),
        'year' => round($yearRevenue, 2),
        'total' => round($totalRevenue, 2),
        'currency' => 'EUR'
    ];
    
    // 5. Upcoming Birthdays (next 30 days)
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name as patient_name,
            p.birthdate,
            o.first_name as owner_first_name,
            o.last_name as owner_last_name,
            DATEDIFF(
                DATE_ADD(p.birthdate, 
                    INTERVAL YEAR(CURDATE()) - YEAR(p.birthdate) + 
                    IF(DAYOFYEAR(CURDATE()) > DAYOFYEAR(p.birthdate), 1, 0) YEAR),
                CURDATE()
            ) as days_until
        FROM patients p
        JOIN owners o ON p.owner_id = o.id
        WHERE p.birthdate IS NOT NULL
        HAVING days_until BETWEEN 0 AND 30
        ORDER BY days_until ASC
        LIMIT 5
    ");
    $stmt->execute();
    $birthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $metrics['birthdays'] = array_map(function($b) {
        return [
            'patient_id' => (int)$b['id'],
            'patient_name' => $b['patient_name'],
            'owner_name' => $b['owner_first_name'] . ' ' . $b['owner_last_name'],
            'birthdate' => $b['birthdate'],
            'days_until' => (int)$b['days_until'],
            'age' => (int)date_diff(date_create($b['birthdate']), date_create('today'))->y + 1
        ];
    }, $birthdays);
    
    // 6. Invoice Status
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid,
            COUNT(CASE WHEN status IN ('sent', 'overdue') THEN 1 END) as open,
            COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft,
            COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue,
            COALESCE(SUM(CASE WHEN status IN ('sent', 'overdue') THEN total ELSE 0 END), 0) as open_amount
        FROM invoices
        WHERE status != 'cancelled'
    ");
    $invoiceStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $metrics['invoices'] = [
        'paid' => (int)($invoiceStatus['paid'] ?? 0),
        'open' => (int)($invoiceStatus['open'] ?? 0),
        'draft' => (int)($invoiceStatus['draft'] ?? 0),
        'overdue' => (int)($invoiceStatus['overdue'] ?? 0),
        'open_amount' => round((float)($invoiceStatus['open_amount'] ?? 0), 2)
    ];
    
    // 7. Monthly Statistics for Chart (last 12 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month,
            COUNT(*) as invoice_count,
            COALESCE(SUM(total), 0) as revenue
        FROM invoices
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        AND status != 'cancelled'
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fill in missing months with zero values
    $monthlyData = [];
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthlyData[$month] = ['revenue' => 0, 'invoices' => 0];
    }
    
    foreach ($monthlyStats as $stat) {
        $monthlyData[$stat['month']] = [
            'revenue' => round((float)$stat['revenue'], 2),
            'invoices' => (int)$stat['invoice_count']
        ];
    }
    
    $metrics['monthly_chart'] = [
        'labels' => array_map(function($m) {
            return date('M Y', strtotime($m . '-01'));
        }, array_keys($monthlyData)),
        'revenue' => array_values(array_column($monthlyData, 'revenue')),
        'invoices' => array_values(array_column($monthlyData, 'invoices'))
    ];
    
    // 8. Top Services (most common appointment types)
    $stmt = $pdo->query("
        SELECT 
            type as service,
            COUNT(*) as count
        FROM appointments
        WHERE type IS NOT NULL 
        AND type != ''
        AND date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY type
        ORDER BY count DESC
        LIMIT 5
    ");
    $metrics['top_services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 9. Patient Distribution by Animal Type
    $stmt = $pdo->query("
        SELECT 
            COALESCE(animal_type, 'Nicht angegeben') as type,
            COUNT(*) as count
        FROM patients
        GROUP BY animal_type
        ORDER BY count DESC
    ");
    $animalTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $metrics['animal_distribution'] = [
        'labels' => array_column($animalTypes, 'type'),
        'data' => array_map('intval', array_column($animalTypes, 'count'))
    ];
    
    // 10. Quick Stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM patients");
    $totalPatients = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM owners");
    $totalOwners = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE date >= CURDATE()");
    $upcomingAppointments = (int)$stmt->fetchColumn();
    
    $metrics['quick_stats'] = [
        'total_patients' => $totalPatients,
        'total_owners' => $totalOwners,
        'upcoming_appointments' => $upcomingAppointments
    ];
    
    // Success response
    echo json_encode([
        'success' => true,
        'data' => $metrics
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => APP_DEBUG ? $e->getMessage() : 'An error occurred while fetching metrics'
    ]);
}