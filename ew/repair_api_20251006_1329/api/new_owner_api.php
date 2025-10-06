<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . "/../includes/db.php";

header('Content-Type: application/json; charset=utf-8');

/**
 * JSON-Ausgabe + Logging
 */
function json_out(array $arr, int $code = 200): void {
    http_response_code($code);

    if (($arr['success'] ?? true) === false) {
        $msg = "[new_owner_api.php] " . ($arr['error'] ?? 'Unbekannter Fehler');
        error_log($msg);
    }

    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

// ----------------- Input lesen -----------------
$raw = file_get_contents('php://input');
$input = [];
if (!empty($raw)) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}
if (!$input && !empty($_POST)) {
    $input = $_POST;
}
$action = $input['action'] ?? ($_GET['action'] ?? '');

if ($action === '') {
    json_out(['success' => false, 'error' => '❌ Kein action-Parameter übergeben.'], 400);
}

// ---------- Besitzer + Patient anlegen ----------
if ($action === 'add_owner_patient') {
    $firstname  = trim((string)($input['owner_firstname'] ?? ''));
    $lastname   = trim((string)($input['owner_lastname'] ?? ''));
    $phone      = trim((string)($input['owner_phone'] ?? ''));
    $email      = trim((string)($input['owner_email'] ?? ''));
    $birthdate  = trim((string)($input['owner_birthdate'] ?? ''));
    $street     = trim((string)($input['owner_street'] ?? ''));
    $zipcode    = trim((string)($input['owner_zipcode'] ?? ''));
    $city       = trim((string)($input['owner_city'] ?? ''));

    $patientName     = trim((string)($input['patient_name'] ?? ''));
    $patientSpecies  = trim((string)($input['patient_species'] ?? ''));
    $patientBreed    = trim((string)($input['patient_breed'] ?? ''));
    $patientBirth    = trim((string)($input['patient_birthdate'] ?? ''));
    $patientSymptoms = trim((string)($input['patient_symptoms'] ?? ''));
    $patientMeds     = trim((string)($input['patient_medications'] ?? ''));
    $patientTherapies= trim((string)($input['patient_therapies'] ?? ''));
    $patientNotes    = trim((string)($input['patient_notes'] ?? ''));

    if ($firstname === '' || $lastname === '') {
        json_out(['success' => false, 'error' => '❌ Besitzer Vor- und Nachname sind Pflichtfelder.'], 400);
    }
    if ($patientName === '') {
        json_out(['success' => false, 'error' => '❌ Patientenname ist Pflichtfeld.'], 400);
    }

    try {
        $pdo->beginTransaction();

        // Besitzer speichern
        $stmt = $pdo->prepare("
            INSERT INTO owners (firstname, lastname, email, phone, street, zipcode, city, birthdate, sync_status)
            VALUES (:firstname, :lastname, :email, :phone, :street, :zipcode, :city, :birthdate, 'local')
        ");
        $stmt->execute([
            ':firstname' => $firstname,
            ':lastname'  => $lastname,
            ':email'     => $email,
            ':phone'     => $phone,
            ':street'    => $street,
            ':zipcode'   => $zipcode,
            ':city'      => $city,
            ':birthdate' => ($birthdate ?: null),
        ]);
        $newOwnerId = (int)$pdo->lastInsertId();

        // Patient speichern
        $stmt = $pdo->prepare("
            INSERT INTO patients (owner_id, name, species, breed, birthdate, symptoms, medications, therapies, notes, sync_status)
            VALUES (:oid, :name, :species, :breed, :birthdate, :symptoms, :medications, :therapies, :notes, 'local')
        ");
        $stmt->execute([
            ':oid'         => $newOwnerId,
            ':name'        => $patientName,
            ':species'     => $patientSpecies,
            ':breed'       => $patientBreed,
            ':birthdate'   => ($patientBirth ?: null),
            ':symptoms'    => $patientSymptoms,
            ':medications' => $patientMeds,
            ':therapies'   => $patientTherapies,
            ':notes'       => $patientNotes,
        ]);
        $newPatientId = (int)$pdo->lastInsertId();

        $pdo->commit();

        json_out([
            'success' => true,
            'msg' => "✅ Neuer Besitzer <b>$firstname $lastname</b> und Patient <b>$patientName</b> erfolgreich angelegt.",
            'owner_id' => $newOwnerId,
            'patient_id' => $newPatientId
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_out(['success' => false, 'error' => 'DB: ' . $e->getMessage()], 500);
    }
}

// ---------- Fallback ----------
json_out(['success' => false, 'error' => '❌ Unbekannte Aktion.'], 400);