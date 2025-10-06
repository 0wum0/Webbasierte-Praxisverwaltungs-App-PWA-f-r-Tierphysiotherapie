<?php
/**
 * Unified API Router
 * Central endpoint for all API actions
 */

// Include bootstrap
require_once __DIR__ . '/bootstrap.php';

// Get action from request
$action = $_REQUEST['action'] ?? $_GET['action'] ?? $_POST['action'] ?? null;

// Get request method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Get JSON input if available
$json_input = get_json_input();
if ($json_input) {
    $_POST = array_merge($_POST, $json_input);
    if (!$action && isset($json_input['action'])) {
        $action = $json_input['action'];
    }
}

// Log the request
log_api_request($action, $_POST);

// Route based on action
switch ($action) {
    // ========== OWNER ENDPOINTS ==========
    case 'owner_create':
        check_auth();
        handle_owner_create();
        break;
        
    case 'owner_update':
        check_auth();
        handle_owner_update();
        break;
        
    case 'owner_list':
        check_auth();
        handle_owner_list();
        break;
        
    case 'owner_get':
        check_auth();
        handle_owner_get();
        break;
        
    // ========== PATIENT ENDPOINTS ==========
    case 'patient_create':
        check_auth();
        handle_patient_create();
        break;
        
    case 'patient_update':
        check_auth();
        handle_patient_update();
        break;
        
    case 'patient_list':
        check_auth();
        handle_patient_list();
        break;
        
    case 'patient_get':
        check_auth();
        handle_patient_get();
        break;
        
    // ========== COMBINED OWNER/PATIENT ==========
    case 'owner_patient_create':
        check_auth();
        handle_owner_patient_create();
        break;
        
    // ========== APPOINTMENT ENDPOINTS ==========
    case 'appointment_create':
        check_auth();
        handle_appointment_create();
        break;
        
    case 'appointment_update':
        check_auth();
        handle_appointment_update();
        break;
        
    case 'appointment_list':
        check_auth();
        handle_appointment_list();
        break;
        
    case 'appointment_delete':
        check_auth();
        handle_appointment_delete();
        break;
        
    // ========== INVOICE ENDPOINTS ==========
    case 'invoice_create':
        check_auth();
        handle_invoice_create();
        break;
        
    case 'invoice_update':
        check_auth();
        handle_invoice_update();
        break;
        
    case 'invoice_list':
        check_auth();
        handle_invoice_list();
        break;
        
    case 'invoice_status':
        check_auth();
        handle_invoice_status();
        break;
        
    // ========== SEARCH & METRICS ==========
    case 'search':
        check_auth();
        handle_search();
        break;
        
    case 'dashboard_metrics':
        check_auth();
        handle_dashboard_metrics();
        break;
        
    case 'admin_stats':
        check_auth();
        handle_admin_stats();
        break;
        
    // ========== HEALTH CHECK ==========
    case 'health':
    case 'ping':
        json_ok([
            'status' => 'healthy',
            'time' => gmdate('c'),
            'version' => '2.0.0'
        ]);
        break;
        
    // ========== UNKNOWN ACTION ==========
    default:
        json_fail('Unknown action: ' . ($action ?: 'none'), 404, [
            'available_actions' => [
                'owner_create', 'owner_update', 'owner_list', 'owner_get',
                'patient_create', 'patient_update', 'patient_list', 'patient_get',
                'owner_patient_create',
                'appointment_create', 'appointment_update', 'appointment_list', 'appointment_delete',
                'invoice_create', 'invoice_update', 'invoice_list', 'invoice_status',
                'search', 'dashboard_metrics', 'admin_stats',
                'health'
            ]
        ]);
}

// ============================================
// HANDLER FUNCTIONS
// ============================================

/**
 * Create new owner
 */
function handle_owner_create() {
    global $pdo;
    
    $required = validate_required(['first_name', 'last_name'], $_POST);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO owners (first_name, last_name, email, phone, address, created_at)
            VALUES (:first_name, :last_name, :email, :phone, :address, NOW())
        ");
        
        $stmt->execute([
            ':first_name' => $required['first_name'],
            ':last_name' => $required['last_name'],
            ':email' => $_POST['email'] ?? null,
            ':phone' => $_POST['phone'] ?? null,
            ':address' => $_POST['address'] ?? null
        ]);
        
        $owner_id = $pdo->lastInsertId();
        $pdo->commit();
        
        json_ok([
            'id' => (int)$owner_id,
            'message' => 'Besitzer erfolgreich angelegt'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_fail('Fehler beim Anlegen des Besitzers', 500, [
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Update existing owner
 */
function handle_owner_update() {
    global $pdo;
    
    $required = validate_required(['id'], $_POST);
    $owner_id = (int)$required['id'];
    
    try {
        $updates = [];
        $params = [':id' => $owner_id];
        
        if (isset($_POST['first_name'])) {
            $updates[] = "first_name = :first_name";
            $params[':first_name'] = $_POST['first_name'];
        }
        if (isset($_POST['last_name'])) {
            $updates[] = "last_name = :last_name";
            $params[':last_name'] = $_POST['last_name'];
        }
        if (isset($_POST['email'])) {
            $updates[] = "email = :email";
            $params[':email'] = $_POST['email'];
        }
        if (isset($_POST['phone'])) {
            $updates[] = "phone = :phone";
            $params[':phone'] = $_POST['phone'];
        }
        if (isset($_POST['address'])) {
            $updates[] = "address = :address";
            $params[':address'] = $_POST['address'];
        }
        
        if (empty($updates)) {
            json_fail('Keine Änderungen angegeben', 400);
        }
        
        $sql = "UPDATE owners SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() == 0) {
            json_fail('Besitzer nicht gefunden', 404);
        }
        
        json_ok([
            'id' => $owner_id,
            'message' => 'Besitzer erfolgreich aktualisiert'
        ]);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Aktualisieren', 500, [
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * List all owners
 */
function handle_owner_list() {
    global $pdo;
    
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, COUNT(p.id) as patient_count
            FROM owners o
            LEFT JOIN patients p ON o.id = p.owner_id
            GROUP BY o.id
            ORDER BY o.last_name, o.first_name
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $total = $pdo->query("SELECT COUNT(*) FROM owners")->fetchColumn();
        
        json_ok([
            'owners' => $owners,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Abrufen der Besitzer', 500);
    }
}

/**
 * Get single owner details
 */
function handle_owner_get() {
    global $pdo;
    
    $owner_id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if (!$owner_id) {
        json_fail('Besitzer-ID fehlt', 400);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM owners WHERE id = :id
        ");
        $stmt->execute([':id' => $owner_id]);
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$owner) {
            json_fail('Besitzer nicht gefunden', 404);
        }
        
        // Get patients
        $stmt = $pdo->prepare("
            SELECT * FROM patients WHERE owner_id = :owner_id
        ");
        $stmt->execute([':owner_id' => $owner_id]);
        $owner['patients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        json_ok($owner);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Abrufen des Besitzers', 500);
    }
}

/**
 * Create new patient
 */
function handle_patient_create() {
    global $pdo;
    
    $required = validate_required(['name', 'owner_id'], $_POST);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO patients (
                owner_id, name, species, breed, birthdate, 
                gender, weight, chip_number, notes, created_at
            ) VALUES (
                :owner_id, :name, :species, :breed, :birthdate,
                :gender, :weight, :chip_number, :notes, NOW()
            )
        ");
        
        $stmt->execute([
            ':owner_id' => (int)$required['owner_id'],
            ':name' => $required['name'],
            ':species' => $_POST['species'] ?? 'Hund',
            ':breed' => $_POST['breed'] ?? null,
            ':birthdate' => $_POST['birthdate'] ?? null,
            ':gender' => $_POST['gender'] ?? null,
            ':weight' => $_POST['weight'] ?? null,
            ':chip_number' => $_POST['chip_number'] ?? null,
            ':notes' => $_POST['notes'] ?? null
        ]);
        
        $patient_id = $pdo->lastInsertId();
        $pdo->commit();
        
        json_ok([
            'id' => (int)$patient_id,
            'message' => 'Patient erfolgreich angelegt'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_fail('Fehler beim Anlegen des Patienten', 500, [
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Update existing patient
 */
function handle_patient_update() {
    global $pdo;
    
    $required = validate_required(['id'], $_POST);
    $patient_id = (int)$required['id'];
    
    try {
        $updates = [];
        $params = [':id' => $patient_id];
        
        $fields = ['name', 'species', 'breed', 'birthdate', 'gender', 'weight', 'chip_number', 'notes'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $_POST[$field];
            }
        }
        
        if (empty($updates)) {
            json_fail('Keine Änderungen angegeben', 400);
        }
        
        $sql = "UPDATE patients SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() == 0) {
            json_fail('Patient nicht gefunden', 404);
        }
        
        json_ok([
            'id' => $patient_id,
            'message' => 'Patient erfolgreich aktualisiert'
        ]);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Aktualisieren', 500, [
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * List all patients
 */
function handle_patient_list() {
    global $pdo;
    
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    $owner_id = (int)($_GET['owner_id'] ?? 0);
    
    try {
        $sql = "
            SELECT p.*, 
                   CONCAT(o.first_name, ' ', o.last_name) as owner_name
            FROM patients p
            JOIN owners o ON p.owner_id = o.id
        ";
        
        $params = [];
        if ($owner_id) {
            $sql .= " WHERE p.owner_id = :owner_id";
            $params[':owner_id'] = $owner_id;
        }
        
        $sql .= " ORDER BY p.name LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        if ($owner_id) {
            $stmt->bindValue(':owner_id', $owner_id, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM patients";
        if ($owner_id) {
            $count_sql .= " WHERE owner_id = " . $owner_id;
        }
        $total = $pdo->query($count_sql)->fetchColumn();
        
        json_ok([
            'patients' => $patients,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Abrufen der Patienten', 500);
    }
}

/**
 * Get single patient details
 */
function handle_patient_get() {
    global $pdo;
    
    $patient_id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if (!$patient_id) {
        json_fail('Patienten-ID fehlt', 400);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   o.first_name as owner_first_name,
                   o.last_name as owner_last_name,
                   o.email as owner_email,
                   o.phone as owner_phone
            FROM patients p
            JOIN owners o ON p.owner_id = o.id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $patient_id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$patient) {
            json_fail('Patient nicht gefunden', 404);
        }
        
        json_ok($patient);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Abrufen des Patienten', 500);
    }
}

/**
 * Create owner and patient together
 */
function handle_owner_patient_create() {
    global $pdo;
    
    // Validate owner fields
    $owner_required = validate_required(['owner_first_name', 'owner_last_name'], $_POST);
    
    // Validate patient fields
    $patient_required = validate_required(['patient_name'], $_POST);
    
    try {
        $pdo->beginTransaction();
        
        // Create owner first
        $stmt = $pdo->prepare("
            INSERT INTO owners (first_name, last_name, email, phone, address, created_at)
            VALUES (:first_name, :last_name, :email, :phone, :address, NOW())
        ");
        
        $stmt->execute([
            ':first_name' => $owner_required['owner_first_name'],
            ':last_name' => $owner_required['owner_last_name'],
            ':email' => $_POST['owner_email'] ?? null,
            ':phone' => $_POST['owner_phone'] ?? null,
            ':address' => $_POST['owner_address'] ?? null
        ]);
        
        $owner_id = $pdo->lastInsertId();
        
        // Create patient
        $stmt = $pdo->prepare("
            INSERT INTO patients (
                owner_id, name, species, breed, birthdate, 
                gender, weight, chip_number, notes, created_at
            ) VALUES (
                :owner_id, :name, :species, :breed, :birthdate,
                :gender, :weight, :chip_number, :notes, NOW()
            )
        ");
        
        $stmt->execute([
            ':owner_id' => $owner_id,
            ':name' => $patient_required['patient_name'],
            ':species' => $_POST['patient_species'] ?? 'Hund',
            ':breed' => $_POST['patient_breed'] ?? null,
            ':birthdate' => $_POST['patient_birthdate'] ?? null,
            ':gender' => $_POST['patient_gender'] ?? null,
            ':weight' => $_POST['patient_weight'] ?? null,
            ':chip_number' => $_POST['patient_chip'] ?? null,
            ':notes' => $_POST['patient_notes'] ?? null
        ]);
        
        $patient_id = $pdo->lastInsertId();
        
        $pdo->commit();
        
        json_ok([
            'owner_id' => (int)$owner_id,
            'patient_id' => (int)$patient_id,
            'message' => 'Besitzer und Patient erfolgreich angelegt'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_fail('Fehler beim Anlegen', 500, [
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Create new appointment
 */
function handle_appointment_create() {
    global $pdo;
    
    $required = validate_required(['patient_id', 'date', 'time'], $_POST);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO appointments (
                patient_id, date, time, type, duration, 
                status, notes, created_at
            ) VALUES (
                :patient_id, :date, :time, :type, :duration,
                :status, :notes, NOW()
            )
        ");
        
        $stmt->execute([
            ':patient_id' => (int)$required['patient_id'],
            ':date' => $required['date'],
            ':time' => $required['time'],
            ':type' => $_POST['type'] ?? 'Behandlung',
            ':duration' => (int)($_POST['duration'] ?? 30),
            ':status' => $_POST['status'] ?? 'scheduled',
            ':notes' => $_POST['notes'] ?? null
        ]);
        
        $appointment_id = $pdo->lastInsertId();
        
        json_ok([
            'id' => (int)$appointment_id,
            'message' => 'Termin erfolgreich angelegt'
        ]);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Anlegen des Termins', 500, [
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Update appointment
 */
function handle_appointment_update() {
    global $pdo;
    
    $required = validate_required(['id'], $_POST);
    $appointment_id = (int)$required['id'];
    
    try {
        $updates = [];
        $params = [':id' => $appointment_id];
        
        $fields = ['date', 'time', 'type', 'duration', 'status', 'notes'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $_POST[$field];
            }
        }
        
        if (empty($updates)) {
            json_fail('Keine Änderungen angegeben', 400);
        }
        
        $sql = "UPDATE appointments SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() == 0) {
            json_fail('Termin nicht gefunden', 404);
        }
        
        json_ok([
            'id' => $appointment_id,
            'message' => 'Termin erfolgreich aktualisiert'
        ]);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Aktualisieren', 500);
    }
}

/**
 * List appointments
 */
function handle_appointment_list() {
    global $pdo;
    
    $date = $_GET['date'] ?? date('Y-m-d');
    $patient_id = (int)($_GET['patient_id'] ?? 0);
    
    try {
        $sql = "
            SELECT a.*,
                   p.name as patient_name,
                   p.species as patient_species,
                   CONCAT(o.first_name, ' ', o.last_name) as owner_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN owners o ON p.owner_id = o.id
            WHERE a.date = :date
        ";
        
        $params = [':date' => $date];
        
        if ($patient_id) {
            $sql .= " AND a.patient_id = :patient_id";
            $params[':patient_id'] = $patient_id;
        }
        
        $sql .= " ORDER BY a.time";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        json_ok([
            'appointments' => $appointments,
            'date' => $date
        ]);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Abrufen der Termine', 500);
    }
}

/**
 * Delete appointment
 */
function handle_appointment_delete() {
    global $pdo;
    
    $appointment_id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if (!$appointment_id) {
        json_fail('Termin-ID fehlt', 400);
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = :id");
        $stmt->execute([':id' => $appointment_id]);
        
        if ($stmt->rowCount() == 0) {
            json_fail('Termin nicht gefunden', 404);
        }
        
        json_ok([
            'message' => 'Termin erfolgreich gelöscht'
        ]);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Löschen', 500);
    }
}

/**
 * Create new invoice
 */
function handle_invoice_create() {
    global $pdo;
    
    $required = validate_required(['owner_id', 'patient_id', 'date'], $_POST);
    
    try {
        $pdo->beginTransaction();
        
        // Create invoice
        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                owner_id, patient_id, invoice_number, date,
                subtotal, tax_rate, tax_amount, total,
                status, notes, created_at
            ) VALUES (
                :owner_id, :patient_id, :invoice_number, :date,
                :subtotal, :tax_rate, :tax_amount, :total,
                :status, :notes, NOW()
            )
        ");
        
        // Generate invoice number
        $year = date('Y');
        $stmt_max = $pdo->query("
            SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) as max_num 
            FROM invoices 
            WHERE invoice_number LIKE '$year-%'
        ");
        $max_num = $stmt_max->fetchColumn() ?: 0;
        $invoice_number = $year . '-' . str_pad($max_num + 1, 4, '0', STR_PAD_LEFT);
        
        // Calculate totals
        $items = json_decode($_POST['items'] ?? '[]', true);
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += ($item['quantity'] ?? 1) * ($item['price'] ?? 0);
        }
        
        $tax_rate = (float)($_POST['tax_rate'] ?? 19);
        $tax_amount = $subtotal * ($tax_rate / 100);
        $total = $subtotal + $tax_amount;
        
        $stmt->execute([
            ':owner_id' => (int)$required['owner_id'],
            ':patient_id' => (int)$required['patient_id'],
            ':invoice_number' => $invoice_number,
            ':date' => $required['date'],
            ':subtotal' => $subtotal,
            ':tax_rate' => $tax_rate,
            ':tax_amount' => $tax_amount,
            ':total' => $total,
            ':status' => $_POST['status'] ?? 'draft',
            ':notes' => $_POST['notes'] ?? null
        ]);
        
        $invoice_id = $pdo->lastInsertId();
        
        // Add invoice items
        if (!empty($items)) {
            $stmt_item = $pdo->prepare("
                INSERT INTO invoice_items (
                    invoice_id, description, quantity, price, total
                ) VALUES (
                    :invoice_id, :description, :quantity, :price, :total
                )
            ");
            
            foreach ($items as $item) {
                $quantity = (float)($item['quantity'] ?? 1);
                $price = (float)($item['price'] ?? 0);
                
                $stmt_item->execute([
                    ':invoice_id' => $invoice_id,
                    ':description' => $item['description'] ?? '',
                    ':quantity' => $quantity,
                    ':price' => $price,
                    ':total' => $quantity * $price
                ]);
            }
        }
        
        $pdo->commit();
        
        json_ok([
            'id' => (int)$invoice_id,
            'invoice_number' => $invoice_number,
            'message' => 'Rechnung erfolgreich erstellt'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_fail('Fehler beim Erstellen der Rechnung', 500, [
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Update invoice
 */
function handle_invoice_update() {
    global $pdo;
    
    $required = validate_required(['id'], $_POST);
    $invoice_id = (int)$required['id'];
    
    try {
        $updates = [];
        $params = [':id' => $invoice_id];
        
        $fields = ['status', 'paid_date', 'notes'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $_POST[$field];
            }
        }
        
        if (empty($updates)) {
            json_fail('Keine Änderungen angegeben', 400);
        }
        
        $sql = "UPDATE invoices SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() == 0) {
            json_fail('Rechnung nicht gefunden', 404);
        }
        
        json_ok([
            'id' => $invoice_id,
            'message' => 'Rechnung erfolgreich aktualisiert'
        ]);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Aktualisieren', 500);
    }
}

/**
 * List invoices
 */
function handle_invoice_list() {
    global $pdo;
    
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    $status = $_GET['status'] ?? null;
    
    try {
        $sql = "
            SELECT i.*,
                   p.name as patient_name,
                   CONCAT(o.first_name, ' ', o.last_name) as owner_name
            FROM invoices i
            JOIN patients p ON i.patient_id = p.id
            JOIN owners o ON i.owner_id = o.id
        ";
        
        $params = [];
        if ($status) {
            $sql .= " WHERE i.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY i.date DESC, i.id DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        if ($status) {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM invoices";
        if ($status) {
            $count_sql .= " WHERE status = '$status'";
        }
        $total = $pdo->query($count_sql)->fetchColumn();
        
        json_ok([
            'invoices' => $invoices,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Abrufen der Rechnungen', 500);
    }
}

/**
 * Update invoice status
 */
function handle_invoice_status() {
    global $pdo;
    
    $required = validate_required(['id', 'status'], $_POST);
    $invoice_id = (int)$required['id'];
    $status = $required['status'];
    
    // Validate status
    $valid_statuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        json_fail('Ungültiger Status', 400, [
            'valid_statuses' => $valid_statuses
        ]);
    }
    
    try {
        $updates = ['status = :status'];
        $params = [
            ':id' => $invoice_id,
            ':status' => $status
        ];
        
        // If marking as paid, set paid_date
        if ($status === 'paid' && !isset($_POST['paid_date'])) {
            $updates[] = 'paid_date = CURDATE()';
        }
        
        $sql = "UPDATE invoices SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() == 0) {
            json_fail('Rechnung nicht gefunden', 404);
        }
        
        json_ok([
            'id' => $invoice_id,
            'status' => $status,
            'message' => 'Status erfolgreich aktualisiert'
        ]);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Aktualisieren des Status', 500);
    }
}

/**
 * Search for patients and owners
 */
function handle_search() {
    global $pdo;
    
    $query = trim($_GET['q'] ?? $_POST['q'] ?? '');
    if (strlen($query) < 2) {
        json_ok([]);
        return;
    }
    
    try {
        $results = [];
        
        // Search patients
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.species,
                   CONCAT(o.first_name, ' ', o.last_name) as owner_name
            FROM patients p
            JOIN owners o ON p.owner_id = o.id
            WHERE p.name LIKE :q OR o.last_name LIKE :q2
            LIMIT 10
        ");
        $stmt->execute([
            ':q' => "%$query%",
            ':q2' => "%$query%"
        ]);
        
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'type' => 'patient',
                'id' => (int)$row['id'],
                'label' => $row['name'] . ' (' . $row['species'] . ') - ' . $row['owner_name'],
                'url' => '/patient.php?id=' . $row['id']
            ];
        }
        
        // Search owners
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name
            FROM owners
            WHERE first_name LIKE :q OR last_name LIKE :q2
            LIMIT 10
        ");
        $stmt->execute([
            ':q' => "%$query%",
            ':q2' => "%$query%"
        ]);
        
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'type' => 'owner',
                'id' => (int)$row['id'],
                'label' => $row['first_name'] . ' ' . $row['last_name'],
                'url' => '/owner.php?id=' . $row['id']
            ];
        }
        
        json_ok($results);
        
    } catch (Exception $e) {
        json_fail('Fehler bei der Suche', 500);
    }
}

/**
 * Get dashboard metrics
 */
function handle_dashboard_metrics() {
    global $pdo;
    
    try {
        $today = date('Y-m-d');
        $metrics = [];
        
        // Today's appointments
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM appointments 
            WHERE date = :today
        ");
        $stmt->execute([':today' => $today]);
        $appointments = $stmt->fetch();
        
        $metrics['appointments_today'] = [
            'total' => (int)$appointments['total'],
            'completed' => (int)$appointments['completed']
        ];
        
        // Active patients (with appointments in last 3 months)
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT p.id) as count
            FROM patients p
            INNER JOIN appointments a ON p.id = a.patient_id
            WHERE a.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        ");
        $metrics['active_patients'] = (int)$stmt->fetchColumn();
        
        // Revenue this month
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(total), 0) as revenue
            FROM invoices
            WHERE DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
            AND status != 'cancelled'
        ");
        $metrics['revenue_month'] = (float)$stmt->fetchColumn();
        
        // Open invoices
        $stmt = $pdo->query("
            SELECT COUNT(*) as count,
                   COALESCE(SUM(total), 0) as amount
            FROM invoices
            WHERE status IN ('sent', 'overdue')
        ");
        $open = $stmt->fetch();
        $metrics['open_invoices'] = [
            'count' => (int)$open['count'],
            'amount' => (float)$open['amount']
        ];
        
        // Quick stats
        $metrics['total_patients'] = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
        $metrics['total_owners'] = (int)$pdo->query("SELECT COUNT(*) FROM owners")->fetchColumn();
        
        json_ok($metrics);
        
    } catch (Exception $e) {
        json_fail('Fehler beim Abrufen der Metriken', 500);
    }
}

/**
 * Get admin statistics
 */
function handle_admin_stats() {
    // Similar to dashboard_metrics but with more detailed admin data
    handle_dashboard_metrics();
}