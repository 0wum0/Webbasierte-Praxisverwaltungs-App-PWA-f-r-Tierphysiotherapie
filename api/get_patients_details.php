<?php
declare(strict_types=1);

/**
 * get_patients_details.php
 *
 * Liefert detaillierte Informationen zu einem Patienten als JSON:
 *  - Patient + zugeordneter Besitzer
 *  - Termine
 *  - Rechnungen (inkl. Positionen)
 *  - Notizen
 *
 * Aufruf: get_patients_details.php?id=123
 *
 * Response (Beispiel):
 * {
 *   "success": true,
 *   "patient": {...},
 *   "owner": {...},
 *   "appointments": [...],
 *   "invoices": [
 *      {
 *        "id": 1,
 *        "amount": "75.00",
 *        "status": "open",
 *        "pdf_path": "invoices/demo_invoice_1.pdf",
 *        "items": [
 *          {"id": 9, "description": "...", "amount": "75.00"}
 *        ]
 *      }
 *   ],
 *   "notes": [...]
 * }
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

// ---------- Helper ----------

function calcAge(?string $date): ?int {
    if (!$date) return null;
    try {
        $bd = new DateTime($date);
        $now = new DateTime('today');
        return (int)$bd->diff($now)->y;
    } catch (Exception $e) {
        return null;
    }
}

function toIso(?string $datetime): ?string {
    if (!$datetime) return null;
    try {
        $d = new DateTime($datetime);
        return $d->format('c'); // 2025-01-23T14:30:00+01:00
    } catch (Exception $e) {
        return $datetime;
    }
}

function moneyStr($val): string {
    if ($val === null) return "0.00";
    return number_format((float)$val, 2, '.', '');
}

/**
 * Wandelt Text (Zeilenumbrüche oder Kommas) in Array um
 */
function textToArray(?string $txt): array {
    if (!$txt) return [];
    $parts = preg_split('/\r\n|\r|\n|,/', $txt);
    $clean = array_map('trim', $parts);
    return array_values(array_filter($clean, fn($v) => $v !== ''));
}

// ---------- Input prüfen ----------

$patientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($patientId <= 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Ungültige oder fehlende Patienten-ID (Parameter "id").'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- Daten abrufen ----------

try {
    // Patient + Owner
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.owner_id,
            p.name,
            p.species,
            p.breed,
            p.birthdate,
            p.findings,
            p.medications,
            p.therapies,
            p.symptoms,
            p.extras,
            p.notes,
            p.image,
            p.created_at,
            p.updated_at,
            p.sync_status,
            o.firstname,
            o.lastname,
            o.email,
            o.phone,
            o.street,
            o.zipcode,
            o.city
        FROM patients p
        JOIN owners o ON p.owner_id = o.id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $patientId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode([
            'success' => false,
            'error'   => 'Patient nicht gefunden.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Patient Objekt
    $patient = [
        'id'              => (int)$row['id'],
        'owner_id'        => (int)$row['owner_id'],
        'name'            => $row['name'],
        'species'         => $row['species'],
        'breed'           => $row['breed'],
        'birthdate'       => $row['birthdate'],
        'age_years'       => calcAge($row['birthdate']),
        'findings'        => $row['findings'],
        'findings_arr'    => textToArray($row['findings']),
        'medications'     => $row['medications'],
        'medications_arr' => textToArray($row['medications']),
        'therapies'       => $row['therapies'],
        'therapies_arr'   => textToArray($row['therapies']),
        'symptoms'        => $row['symptoms'],
        'symptoms_arr'    => textToArray($row['symptoms']),
        'extras'          => $row['extras'],
        'extras_arr'      => textToArray($row['extras']),
        'notes'           => $row['notes'],
        'image'           => $row['image'],
        'created_at'      => $row['created_at'],
        'updated_at'      => $row['updated_at'] ?? null,
        'sync_status'     => $row['sync_status'],
    ];

    // Owner Objekt
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

    // Termine
    $stmt = $pdo->prepare("
        SELECT id, appointment_date, notes, created_at, updated_at
        FROM appointments
        WHERE patient_id = :pid
        ORDER BY appointment_date DESC
    ");
    $stmt->execute([':pid' => $patientId]);
    $appointments = [];
    while ($a = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $appointments[] = [
            'id'               => (int)$a['id'],
            'appointment_date' => $a['appointment_date'],
            'appointment_iso'  => toIso($a['appointment_date']),
            'notes'            => $a['notes'],
            'created_at'       => $a['created_at'],
            'updated_at'       => $a['updated_at'] ?? null,
        ];
    }

    // Rechnungen
    $stmt = $pdo->prepare("
        SELECT 
            i.id, i.patient_id, i.appointment_id, i.amount, i.status, i.pdf_path,
            i.sync_status, i.created_at, i.updated_at, i.last_updated
        FROM invoices i
        WHERE i.patient_id = :pid
        ORDER BY i.id DESC
    ");
    $stmt->execute([':pid' => $patientId]);

    $invoices = [];
    while ($inv = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $invoiceId = (int)$inv['id'];

        $stmtItems = $pdo->prepare("
            SELECT id, invoice_id, description, amount
            FROM invoice_items
            WHERE invoice_id = :iid
            ORDER BY id ASC
        ");
        $stmtItems->execute([':iid' => $invoiceId]);

        $items = [];
        $sumFromItems = 0.0;
        while ($it = $stmtItems->fetch(PDO::FETCH_ASSOC)) {
            $amt = (float)$it['amount'];
            $sumFromItems += $amt;
            $items[] = [
                'id'          => (int)$it['id'],
                'invoice_id'  => (int)$it['invoice_id'],
                'description' => $it['description'],
                'amount'      => moneyStr($amt),
            ];
        }

        $invoices[] = [
            'id'            => $invoiceId,
            'patient_id'    => (int)$inv['patient_id'],
            'appointment_id'=> $inv['appointment_id'] !== null ? (int)$inv['appointment_id'] : null,
            'amount'        => moneyStr($inv['amount']),
            'amount_items'  => moneyStr($sumFromItems),
            'status'        => $inv['status'],
            'pdf_path'      => $inv['pdf_path'],
            'sync_status'   => $inv['sync_status'],
            'created_at'    => $inv['created_at'],
            'updated_at'    => $inv['updated_at'] ?? null,
            'last_updated'  => $inv['last_updated'] ?? null,
            'items'         => $items,
        ];
    }

    // Notizen
    $stmt = $pdo->prepare("
        SELECT id, content, created_at, sync_status
        FROM notes
        WHERE patient_id = :pid
        ORDER BY created_at DESC
    ");
    $stmt->execute([':pid' => $patientId]);

    $notes = [];
    while ($n = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $notes[] = [
            'id'         => (int)$n['id'],
            'content'    => $n['content'],
            'created_at' => $n['created_at'],
            'sync_status'=> $n['sync_status'],
        ];
    }

    echo json_encode([
        'success'      => true,
        'patient'      => $patient,
        'owner'        => $owner,
        'appointments' => $appointments,
        'invoices'     => $invoices,
        'notes'        => $notes,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Fehler: ' . $t->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}