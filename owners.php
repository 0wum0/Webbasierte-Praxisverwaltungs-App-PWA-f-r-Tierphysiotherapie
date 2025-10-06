<?php
declare(strict_types=1);

/**
 * Owners Management Page - Twig Version
 * Unified design with SSoT implementation
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/twig.php';

$db = db();
if (!$db) {
    throw new RuntimeException('DB connection unavailable');
}

// Initialize variables
$owners = [];
$errors = [];

// Fetch all owners with patient count
try {
    $stmt = $db->prepare("
        SELECT 
            o.*,
            COUNT(DISTINCT p.id) as patient_count,
            GROUP_CONCAT(p.name SEPARATOR ', ') as patient_names
        FROM owners o
        LEFT JOIN patients p ON p.owner_id = o.id
        GROUP BY o.id
        ORDER BY o.lastname, o.firstname ASC
    ");
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process owner data
    foreach ($owners as &$owner) {
        // Create full name
        $owner['full_name'] = trim($owner['firstname'] . ' ' . $owner['lastname']);
        
        // Format address
        $addressParts = [];
        if (!empty($owner['address'])) $addressParts[] = $owner['address'];
        if (!empty($owner['postal_code'])) $addressParts[] = $owner['postal_code'];
        if (!empty($owner['city'])) $addressParts[] = $owner['city'];
        $owner['full_address'] = implode(', ', $addressParts);
        
        // Limit patient names display
        if (!empty($owner['patient_names'])) {
            $patientList = explode(', ', $owner['patient_names']);
            if (count($patientList) > 3) {
                $owner['patient_names'] = implode(', ', array_slice($patientList, 0, 3)) . ' ...';
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching owners: " . $e->getMessage());
    $errors[] = "Fehler beim Laden der Besitzer.";
}

// User information
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Benutzer';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['admin_email'] ?? '';
$isAdmin = isset($_SESSION['admin_id']);

// Render with Twig template
echo $twig->render('owners.twig', [
    'title' => 'Besitzer - Tierphysio Manager',
    'current_page' => 'owners',
    'user_name' => $userName,
    'user_email' => $userEmail,
    'is_admin' => $isAdmin,
    'theme' => $_SESSION['theme'] ?? 'light',
    
    // Data
    'owners' => $owners,
    
    // Messages
    'errors' => $errors
]);