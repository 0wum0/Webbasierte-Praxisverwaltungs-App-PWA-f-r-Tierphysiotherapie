<?php
declare(strict_types=1);

/**
 * Patients Management Page - Twig Version
 * Unified design with SSoT implementation
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/twig.php';

$db = db();
if (!$db) {
    throw new RuntimeException('DB connection unavailable');
}

// Initialize variables
$patients = [];
$errors = [];

// Fetch all patients with owner information
try {
    $stmt = $db->prepare("
        SELECT 
            p.*,
            o.firstname,
            o.lastname,
            o.email as owner_email,
            o.phone as owner_phone,
            o.address as owner_address,
            o.city as owner_city,
            o.postal_code as owner_postal_code
        FROM patients p
        LEFT JOIN owners o ON p.owner_id = o.id
        ORDER BY p.name ASC
    ");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process image paths
    foreach ($patients as &$patient) {
        if (!empty($patient['image'])) {
            // Ensure image path is relative to web root
            if (strpos($patient['image'], '/') !== 0) {
                $patient['image'] = '/uploads/patients/' . $patient['image'];
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching patients: " . $e->getMessage());
    $errors[] = "Fehler beim Laden der Patienten.";
}

// User information
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Benutzer';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['admin_email'] ?? '';
$isAdmin = isset($_SESSION['admin_id']);

// Render with Twig template
echo $twig->render('patients.twig', [
    'title' => 'Patienten - Tierphysio Manager',
    'current_page' => 'patients',
    'user_name' => $userName,
    'user_email' => $userEmail,
    'is_admin' => $isAdmin,
    'theme' => $_SESSION['theme'] ?? 'light',
    
    // Data
    'patients' => $patients,
    
    // Messages
    'errors' => $errors
]);