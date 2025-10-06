<?php
declare(strict_types=1);

/**
 * Appointments Management Page - Twig Version
 * Unified design with SSoT implementation
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/twig.php';

$db = db();
if (!$db) {
    throw new RuntimeException('DB connection unavailable');
}

// Initialize variables
$appointments = [];
$patients = [];
$errors = [];

// Fetch all appointments with patient and owner information
try {
    $stmt = $db->prepare("
        SELECT 
            a.*,
            p.name as patient_name,
            p.species,
            o.firstname as owner_firstname,
            o.lastname as owner_lastname,
            o.phone as owner_phone,
            o.email as owner_email
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        LEFT JOIN owners o ON p.owner_id = o.id
        ORDER BY a.appointment_date DESC
        LIMIT 100
    ");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates and add status
    foreach ($appointments as &$appointment) {
        $appointmentTime = strtotime($appointment['appointment_date']);
        $now = time();
        
        if ($appointmentTime < $now) {
            $appointment['status_badge'] = 'badge-secondary';
            $appointment['status_text'] = 'Vergangen';
        } elseif (date('Y-m-d', $appointmentTime) === date('Y-m-d')) {
            $appointment['status_badge'] = 'badge-warning';
            $appointment['status_text'] = 'Heute';
        } else {
            $appointment['status_badge'] = 'badge-success';
            $appointment['status_text'] = 'Anstehend';
        }
    }
} catch (Exception $e) {
    error_log("Error fetching appointments: " . $e->getMessage());
    $errors[] = "Fehler beim Laden der Termine.";
}

// Fetch patients for dropdown
try {
    $stmt = $db->prepare("
        SELECT p.id, p.name, o.firstname, o.lastname 
        FROM patients p
        LEFT JOIN owners o ON p.owner_id = o.id
        ORDER BY p.name ASC
    ");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $patients = [];
}

// User information
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Benutzer';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['admin_email'] ?? '';
$isAdmin = isset($_SESSION['admin_id']);

// Render with Twig template
echo $twig->render('appointments.twig', [
    'title' => 'Termine - Tierphysio Manager',
    'current_page' => 'appointments',
    'user_name' => $userName,
    'user_email' => $userEmail,
    'is_admin' => $isAdmin,
    'theme' => $_SESSION['theme'] ?? 'light',
    
    // Data
    'appointments' => $appointments,
    'patients' => $patients,
    
    // Messages
    'errors' => $errors
]);