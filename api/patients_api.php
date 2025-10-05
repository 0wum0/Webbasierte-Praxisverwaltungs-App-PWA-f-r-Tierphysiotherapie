<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

/** ---- Zeitzonen-Setup ---- */
const DB_TZ  = 'UTC';
const APP_TZ = 'Europe/Berlin';
date_default_timezone_set(APP_TZ);

try { 
    $pdo->exec("SET time_zone = '+00:00'"); 
} catch (\Throwable $e) {}

/** ---- Hilfsfunktionen ---- */
function calcAge(?string $date): ?int {
    if (!$date) return null;
    try {
        $bd  = new DateTime($date, new DateTimeZone(APP_TZ));
        $now = new DateTime('today', new DateTimeZone(APP_TZ));
        return (int)$bd->diff($now)->y;
    } catch (Exception $e) {
        return null;
    }
}
function toIso(?string $datetime, string $fromTz = DB_TZ, string $toTz = APP_TZ): ?string {
    if (!$datetime) return null;
    try {
        $src = new DateTimeZone($fromTz);
        $dst = new DateTimeZone($toTz);
        $d = new DateTime($datetime, $src);
        $d->setTimezone($dst);
        return $d->format('c');
    } catch (Exception $e) {
        return $datetime;
    }
}
function toDisplay(?string $datetime, string $fromTz = DB_TZ, string $toTz = APP_TZ): ?string {
    if (!$datetime) return null;
    try {
        $src = new DateTimeZone($fromTz);
        $dst = new DateTimeZone($toTz);
        $d = new DateTime($datetime, $src);
        $d->setTimezone($dst);
        return $d->format('d.m.Y H:i');
    } catch (Exception $e) {
        return $datetime;
    }
}
function moneyStr($val): string {
    if ($val === null) return "0.00";
    return number_format((float)$val, 2, '.', '');
}
function textToArray(?string $txt): array {
    if (!$txt) return [];
    $parts = preg_split('/\r\n|\r|\n|,/', $txt);
    $clean = array_map('trim', $parts);
    return array_values(array_filter($clean, fn($v) => $v !== ''));
}

/** ---- Patientendetails laden ---- */
function loadPatientDetails(PDO $pdo, int $patientId): array {
    $stmt = $pdo->prepare("
        SELECT p.*, o.firstname, o.lastname, o.email, o.phone, o.street, o.zipcode, o.city
        FROM patients p
        JOIN owners o ON p.owner_id = o.id
        WHERE p.id = :id LIMIT 1
    ");
    $stmt->execute([':id' => $patientId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception("Patient nicht gefunden.");

    $patient = [
        'id'          => (int)$row['id'],
        'owner_id'    => (int)$row['owner_id'],
        'name'        => $row['name'],
        'species'     => $row['species'],
        'breed'       => $row['breed'],
        'birthdate'   => $row['birthdate'],
        'age_years'   => calcAge($row['birthdate']),
        'symptoms'    => $row['symptoms'],
        'image'       => $row['image'],
        'created_at'  => $row['created_at'],
        'updated_at'  => $row['updated_at'] ?? null,
        'sync_status' => $row['sync_status'],
    ];
    $owner = [
        'id'        => (int)$row['owner_id'],
        'firstname' => $row['firstname'],
        'lastname'  => $row['lastname'],
        'email'     => $row['email'],
        'phone'     => $row['phone'],
        'street'    => $row['street'],
        'zipcode'   => $row['zipcode'],
        'city'      => $row['city'],
    ];

    // Behandlungen
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE patient_id = :pid ORDER BY appointment_date DESC");
    $stmt->execute([':pid' => $patientId]);
    $appointments = [];
    while ($a = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $appointments[] = [
            'id'                  => (int)$a['id'],
            'notes'               => $a['notes'],
            'appointment_date'    => $a['appointment_date'],
            'appointment_display' => toDisplay($a['appointment_date']),
            'created_display'     => toDisplay($a['created_at']),
            'updated_display'     => toDisplay($a['updated_at']),
        ];
    }

    // Rechnungen
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE patient_id = :pid ORDER BY id DESC");
    $stmt->execute([':pid' => $patientId]);
    $invoices = [];
    while ($inv = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $invoices[] = [
            'id'              => (int)$inv['id'],
            'amount'          => moneyStr($inv['amount']),
            'status'          => $inv['status'],
            'created_display' => toDisplay($inv['created_at']),
            'updated_display' => toDisplay($inv['updated_at']),
        ];
    }

    // Notizen
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE patient_id = :pid ORDER BY created_at DESC");
    $stmt->execute([':pid' => $patientId]);
    $notes = [];
    while ($n = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $notes[] = [
            'id'              => (int)$n['id'],
            'content'         => $n['content'],
            'created_display' => toDisplay($n['created_at']),
            'updated_display' => toDisplay($n['updated_at']),
        ];
    }

    return [
        'patient'      => $patient,
        'owner'        => $owner,
        'appointments' => $appointments,
        'invoices'     => $invoices,
        'notes'        => $notes,
    ];
}

/** ---- Hauptlogik ---- */
try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) throw new Exception("Ungültige Eingabe.");

    $action = $data['action'] ?? null;
    switch ($action) {
        case 'get_patient_details':
            $pid = (int)($data['patient_id'] ?? 0);
            $details = loadPatientDetails($pdo, $pid);
            echo json_encode(['success' => true] + $details, JSON_UNESCAPED_UNICODE);
            break;

        case 'add_treatment':
            $stmt = $pdo->prepare("
                INSERT INTO appointments (patient_id, appointment_date, notes, created_at, sync_status)
                VALUES (:pid, NOW(), :notes, NOW(), 'local')
            ");
            $stmt->execute([':pid' => (int)$data['patient_id'], ':notes' => $data['notes'] ?? '']);
            $details = loadPatientDetails($pdo, (int)$data['patient_id']);
            echo json_encode(['success' => true, 'msg' => '✅ Behandlung hinzugefügt'] + $details, JSON_UNESCAPED_UNICODE);
            break;

        case 'edit_treatment':
            $stmt = $pdo->prepare("
                UPDATE appointments SET notes = :notes, updated_at = NOW()
                WHERE id = :id AND patient_id = :pid
            ");
            $stmt->execute([
                ':id'    => (int)$data['id'],
                ':pid'   => (int)$data['patient_id'],
                ':notes' => $data['notes'] ?? ''
            ]);
            $details = loadPatientDetails($pdo, (int)$data['patient_id']);
            echo json_encode(['success' => true, 'msg' => '✏️ Behandlung bearbeitet'] + $details, JSON_UNESCAPED_UNICODE);
            break;

        case 'delete_treatment':
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = :id AND patient_id = :pid");
            $stmt->execute([':id' => (int)$data['id'], ':pid' => (int)$data['patient_id']]);
            $details = loadPatientDetails($pdo, (int)$data['patient_id']);
            echo json_encode(['success' => true, 'msg' => '🗑️ Behandlung gelöscht'] + $details, JSON_UNESCAPED_UNICODE);
            break;

        case 'add_invoice':
            $stmt = $pdo->prepare("
                INSERT INTO invoices (patient_id, amount, status, created_at, updated_at, sync_status)
                VALUES (:pid, :amount, 'open', NOW(), NOW(), 'local')
            ");
            $stmt->execute([':pid' => (int)$data['patient_id'], ':amount' => (float)$data['amount']]);
            $details = loadPatientDetails($pdo, (int)$data['patient_id']);
            echo json_encode(['success' => true, 'msg' => '✅ Rechnung erstellt'] + $details, JSON_UNESCAPED_UNICODE);
            break;

        case 'edit_invoice_status':
            $stmt = $pdo->prepare("UPDATE invoices SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => (int)$data['id'], ':status' => $data['status']]);
            $details = loadPatientDetails($pdo, (int)$data['patient_id']);
            echo json_encode(['success' => true, 'msg' => '✅ Rechnungsstatus geändert'] + $details, JSON_UNESCAPED_UNICODE);
            break;

        case 'add_note':
            $stmt = $pdo->prepare("
                INSERT INTO notes (patient_id, content, created_at, sync_status)
                VALUES (:pid, :content, NOW(), 'local')
            ");
            $stmt->execute([':pid' => (int)$data['patient_id'], ':content' => $data['content'] ?? '']);
            $details = loadPatientDetails($pdo, (int)$data['patient_id']);
            echo json_encode(['success' => true, 'msg' => '✅ Notiz gespeichert'] + $details, JSON_UNESCAPED_UNICODE);
            break;

        case 'edit_note':
            $stmt = $pdo->prepare("
                UPDATE notes SET content = :content, updated_at = NOW()
                WHERE id = :id AND patient_id = :pid
            ");
            $stmt->execute([
                ':id'      => (int)$data['note_id'],
                ':pid'     => (int)$data['patient_id'],
                ':content' => $data['content'] ?? ''
            ]);
            $details = loadPatientDetails($pdo, (int)$data['patient_id']);
            echo json_encode(['success' => true, 'msg' => '✏️ Notiz bearbeitet'] + $details, JSON_UNESCAPED_UNICODE);
            break;

        case 'delete_note':
            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id AND patient_id = :pid");
            $stmt->execute([':id' => (int)$data['note_id'], ':pid' => (int)$data['patient_id']]);
            $details = loadPatientDetails($pdo, (int)$data['patient_id']);
            echo json_encode(['success' => true, 'msg' => '🗑️ Notiz gelöscht'] + $details, JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception("Unbekannte Aktion: ".$action);
    }
} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $t->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}