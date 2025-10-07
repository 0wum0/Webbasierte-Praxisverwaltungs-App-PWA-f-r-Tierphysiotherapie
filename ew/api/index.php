<?php
/**
 * Main API Router
 * Handles all API requests with strict JSON responses
 */

require_once __DIR__ . '/bootstrap.php';

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Route to appropriate handler
switch ($action) {
    case 'owner_create':
        handle_owner_create();
        break;
        
    case 'patient_create':
        handle_patient_create();
        break;
        
    case 'owner_patient_create':
        handle_owner_patient_create();
        break;
        
    case 'appointment_create':
        handle_appointment_create();
        break;
        
    case 'invoice_create':
        handle_invoice_create();
        break;
        
    case 'search':
        handle_search();
        break;
        
    case 'dashboard_metrics':
        handle_dashboard_metrics();
        break;
        
    default:
        json_fail('Unknown action: ' . ($action ?? 'none'), 404);
}

/**
 * Create new owner
 */
function handle_owner_create(): void {
    global $pdo;
    
    $input = read_json();
    
    // Validate required fields
    $first_name = req($input, 'first_name');
    $last_name = req($input, 'last_name');
    
    if (strlen($first_name) < 1) {
        json_fail('Vorname ist erforderlich', 400);
    }
    
    if (strlen($last_name) < 1) {
        json_fail('Nachname ist erforderlich', 400);
    }
    
    // Optional fields
    $email = nullIfEmpty($input['email'] ?? '');
    $phone = nullIfEmpty($input['phone'] ?? '');
    $address = nullIfEmpty($input['address'] ?? '');
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO owners (first_name, last_name, email, phone, address, created_at)
            VALUES (:first_name, :last_name, :email, :phone, :address, NOW())
        ');
        
        $stmt->execute([
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address
        ]);
        
        $id = (int)$pdo->lastInsertId();
        
        json_ok(['id' => $id]);
        
    } catch (PDOException $e) {
        error_log('Owner create error: ' . $e->getMessage());
        json_fail('Fehler beim Erstellen des Besitzers', 500);
    }
}

/**
 * Create new patient
 */
function handle_patient_create(): void {
    global $pdo;
    
    $input = read_json();
    
    // Validate required fields
    $owner_id = (int)($input['owner_id'] ?? 0);
    $name = req($input, 'name');
    $species = req($input, 'species');
    
    if ($owner_id <= 0) {
        json_fail('Besitzer ID ist erforderlich', 400);
    }
    
    if (strlen($name) < 1) {
        json_fail('Name ist erforderlich', 400);
    }
    
    if (strlen($species) < 1) {
        json_fail('Tierart ist erforderlich', 400);
    }
    
    // Check owner exists
    $stmt = $pdo->prepare('SELECT id FROM owners WHERE id = ?');
    $stmt->execute([$owner_id]);
    if (!$stmt->fetch()) {
        json_fail('Besitzer nicht gefunden', 404);
    }
    
    // Optional fields
    $breed = nullIfEmpty($input['breed'] ?? '');
    $birthdate = nullIfEmpty($input['birthdate'] ?? '');
    $notes = nullIfEmpty($input['notes'] ?? '');
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO patients (owner_id, name, species, breed, birthdate, notes, created_at)
            VALUES (:owner_id, :name, :species, :breed, :birthdate, :notes, NOW())
        ');
        
        $stmt->execute([
            ':owner_id' => $owner_id,
            ':name' => $name,
            ':species' => $species,
            ':breed' => $breed,
            ':birthdate' => $birthdate,
            ':notes' => $notes
        ]);
        
        $id = (int)$pdo->lastInsertId();
        
        json_ok(['id' => $id]);
        
    } catch (PDOException $e) {
        error_log('Patient create error: ' . $e->getMessage());
        json_fail('Fehler beim Erstellen des Patienten', 500);
    }
}

/**
 * Create owner and patient together (modal flow)
 */
function handle_owner_patient_create(): void {
    global $pdo;
    
    $input = read_json();
    
    // Extract owner and patient data
    $owner = $input['owner'] ?? [];
    $patient = $input['patient'] ?? [];
    
    // Validate owner fields
    $owner_first_name = req($owner, 'first_name');
    $owner_last_name = req($owner, 'last_name');
    
    if (strlen($owner_first_name) < 1) {
        json_fail('Besitzer Vorname ist erforderlich', 400);
    }
    
    if (strlen($owner_last_name) < 1) {
        json_fail('Besitzer Nachname ist erforderlich', 400);
    }
    
    // Validate patient fields
    $patient_name = req($patient, 'name');
    $patient_species = req($patient, 'species');
    
    if (strlen($patient_name) < 1) {
        json_fail('Patient Name ist erforderlich', 400);
    }
    
    if (strlen($patient_species) < 1) {
        json_fail('Tierart ist erforderlich', 400);
    }
    
    // Start transaction
    try {
        $pdo->beginTransaction();
        
        // Insert owner
        $stmt = $pdo->prepare('
            INSERT INTO owners (first_name, last_name, email, phone, address, created_at)
            VALUES (:first_name, :last_name, :email, :phone, :address, NOW())
        ');
        
        $stmt->execute([
            ':first_name' => $owner_first_name,
            ':last_name' => $owner_last_name,
            ':email' => nullIfEmpty($owner['email'] ?? ''),
            ':phone' => nullIfEmpty($owner['phone'] ?? ''),
            ':address' => nullIfEmpty($owner['address'] ?? '')
        ]);
        
        $owner_id = (int)$pdo->lastInsertId();
        
        // Insert patient
        $stmt = $pdo->prepare('
            INSERT INTO patients (owner_id, name, species, breed, birthdate, notes, created_at)
            VALUES (:owner_id, :name, :species, :breed, :birthdate, :notes, NOW())
        ');
        
        $stmt->execute([
            ':owner_id' => $owner_id,
            ':name' => $patient_name,
            ':species' => $patient_species,
            ':breed' => nullIfEmpty($patient['breed'] ?? ''),
            ':birthdate' => nullIfEmpty($patient['birthdate'] ?? ''),
            ':notes' => nullIfEmpty($patient['notes'] ?? '')
        ]);
        
        $patient_id = (int)$pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        
        json_ok([
            'owner_id' => $owner_id,
            'patient_id' => $patient_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Owner-Patient create error: ' . $e->getMessage());
        json_fail('Fehler beim Erstellen von Besitzer und Patient', 500);
    }
}

/**
 * Create appointment
 */
function handle_appointment_create(): void {
    global $pdo;
    
    $input = read_json();
    
    // Validate required fields
    $patient_id = (int)($input['patient_id'] ?? 0);
    $date = req($input, 'date');
    $duration = (int)($input['duration'] ?? 30);
    
    if ($patient_id <= 0) {
        json_fail('Patient ID ist erforderlich', 400);
    }
    
    if (strlen($date) < 1) {
        json_fail('Datum ist erforderlich', 400);
    }
    
    if ($duration <= 0) {
        json_fail('Dauer muss größer als 0 sein', 400);
    }
    
    // Check patient exists
    $stmt = $pdo->prepare('SELECT id FROM patients WHERE id = ?');
    $stmt->execute([$patient_id]);
    if (!$stmt->fetch()) {
        json_fail('Patient nicht gefunden', 404);
    }
    
    // Optional fields
    $notes = nullIfEmpty($input['notes'] ?? '');
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO appointments (patient_id, date, duration, notes, created_at)
            VALUES (:patient_id, :date, :duration, :notes, NOW())
        ');
        
        $stmt->execute([
            ':patient_id' => $patient_id,
            ':date' => $date,
            ':duration' => $duration,
            ':notes' => $notes
        ]);
        
        $id = (int)$pdo->lastInsertId();
        
        json_ok(['id' => $id]);
        
    } catch (PDOException $e) {
        error_log('Appointment create error: ' . $e->getMessage());
        json_fail('Fehler beim Erstellen des Termins', 500);
    }
}

/**
 * Create invoice
 */
function handle_invoice_create(): void {
    global $pdo;
    
    $input = read_json();
    
    // Validate required fields
    $owner_id = (int)($input['owner_id'] ?? 0);
    $amount = (float)($input['amount'] ?? 0);
    
    if ($owner_id <= 0) {
        json_fail('Besitzer ID ist erforderlich', 400);
    }
    
    if ($amount <= 0) {
        json_fail('Betrag muss größer als 0 sein', 400);
    }
    
    // Check owner exists
    $stmt = $pdo->prepare('SELECT id FROM owners WHERE id = ?');
    $stmt->execute([$owner_id]);
    if (!$stmt->fetch()) {
        json_fail('Besitzer nicht gefunden', 404);
    }
    
    // Optional fields
    $due_date = nullIfEmpty($input['due_date'] ?? '');
    $status = $input['status'] ?? 'open';
    $items = $input['items'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // Insert invoice
        $stmt = $pdo->prepare('
            INSERT INTO invoices (owner_id, amount, due_date, status, created_at)
            VALUES (:owner_id, :amount, :due_date, :status, NOW())
        ');
        
        $stmt->execute([
            ':owner_id' => $owner_id,
            ':amount' => $amount,
            ':due_date' => $due_date,
            ':status' => $status
        ]);
        
        $invoice_id = (int)$pdo->lastInsertId();
        
        // Insert invoice items if provided
        if (is_array($items) && count($items) > 0) {
            $stmt = $pdo->prepare('
                INSERT INTO invoice_items (invoice_id, description, quantity, price)
                VALUES (:invoice_id, :description, :quantity, :price)
            ');
            
            foreach ($items as $item) {
                if (is_array($item) && isset($item['description'])) {
                    $stmt->execute([
                        ':invoice_id' => $invoice_id,
                        ':description' => $item['description'],
                        ':quantity' => $item['quantity'] ?? 1,
                        ':price' => $item['price'] ?? 0
                    ]);
                }
            }
        }
        
        $pdo->commit();
        
        json_ok(['id' => $invoice_id]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Invoice create error: ' . $e->getMessage());
        json_fail('Fehler beim Erstellen der Rechnung', 500);
    }
}

/**
 * Search for patients and owners
 */
function handle_search(): void {
    global $pdo;
    
    // Get search query from GET or POST/JSON
    $q = $_GET['q'] ?? '';
    if (!$q) {
        $input = read_json();
        $q = $input['q'] ?? '';
    }
    
    $q = trim($q);
    if (strlen($q) < 1) {
        json_ok([]);
    }
    
    $results = [];
    $search_term = '%' . $q . '%';
    
    try {
        // Search patients
        $stmt = $pdo->prepare('
            SELECT id, name, species 
            FROM patients 
            WHERE name LIKE :search OR species LIKE :search
            LIMIT 10
        ');
        $stmt->execute([':search' => $search_term]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'type' => 'patient',
                'id' => (int)$row['id'],
                'label' => $row['name'] . ' (' . $row['species'] . ')'
            ];
        }
        
        // Search owners
        $stmt = $pdo->prepare('
            SELECT id, first_name, last_name 
            FROM owners 
            WHERE first_name LIKE :search OR last_name LIKE :search OR email LIKE :search
            LIMIT 10
        ');
        $stmt->execute([':search' => $search_term]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'type' => 'owner',
                'id' => (int)$row['id'],
                'label' => $row['first_name'] . ' ' . $row['last_name']
            ];
        }
        
        json_ok($results);
        
    } catch (PDOException $e) {
        error_log('Search error: ' . $e->getMessage());
        json_fail('Fehler bei der Suche', 500);
    }
}

/**
 * Get dashboard metrics
 */
function handle_dashboard_metrics(): void {
    global $pdo;
    
    try {
        $metrics = [
            'income' => [
                'today' => 0,
                'month' => 0,
                'year' => 0,
                'total' => 0,
                'series' => []
            ],
            'counts' => [
                'appointments_today' => 0,
                'active_patients' => 0,
                'new_patients_week' => 0
            ],
            'invoices' => [
                'paid' => 0,
                'unpaid' => 0
            ],
            'birthdays' => []
        ];
        
        // Income metrics
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN amount ELSE 0 END), 0) as today,
                COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN amount ELSE 0 END), 0) as month,
                COALESCE(SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) THEN amount ELSE 0 END), 0) as year,
                COALESCE(SUM(amount), 0) as total
            FROM invoices
            WHERE status = 'paid'
        ");
        $stmt->execute();
        $income = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $metrics['income']['today'] = (float)($income['today'] ?? 0);
        $metrics['income']['month'] = (float)($income['month'] ?? 0);
        $metrics['income']['year'] = (float)($income['year'] ?? 0);
        $metrics['income']['total'] = (float)($income['total'] ?? 0);
        
        // Income series (last 7 days)
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COALESCE(SUM(amount), 0) as value
            FROM invoices
            WHERE status = 'paid' 
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $metrics['income']['series'][] = [
                'label' => $row['date'],
                'value' => (float)$row['value']
            ];
        }
        
        // Appointments today
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM appointments
            WHERE DATE(date) = CURDATE()
        ");
        $stmt->execute();
        $metrics['counts']['appointments_today'] = (int)$stmt->fetchColumn();
        
        // Active patients (with appointments in last 6 months)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT patient_id) as count
            FROM appointments
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        ");
        $stmt->execute();
        $metrics['counts']['active_patients'] = (int)$stmt->fetchColumn();
        
        // New patients this week
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM patients
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $metrics['counts']['new_patients_week'] = (int)$stmt->fetchColumn();
        
        // Invoice counts
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid,
                COUNT(CASE WHEN status = 'open' THEN 1 END) as unpaid
            FROM invoices
        ");
        $stmt->execute();
        $invoice_counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $metrics['invoices']['paid'] = (int)($invoice_counts['paid'] ?? 0);
        $metrics['invoices']['unpaid'] = (int)($invoice_counts['unpaid'] ?? 0);
        
        // Upcoming birthdays (next 30 days)
        $stmt = $pdo->prepare("
            SELECT 
                id, 
                name,
                birthdate
            FROM patients
            WHERE birthdate IS NOT NULL
                AND (
                    DATE_FORMAT(birthdate, '%m-%d') BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') 
                    AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 30 DAY), '%m-%d')
                )
            ORDER BY DATE_FORMAT(birthdate, '%m-%d')
            LIMIT 5
        ");
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $metrics['birthdays'][] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'date' => $row['birthdate']
            ];
        }
        
        json_ok($metrics);
        
    } catch (PDOException $e) {
        error_log('Dashboard metrics error: ' . $e->getMessage());
        json_fail('Fehler beim Abrufen der Dashboard-Metriken', 500);
    }
}