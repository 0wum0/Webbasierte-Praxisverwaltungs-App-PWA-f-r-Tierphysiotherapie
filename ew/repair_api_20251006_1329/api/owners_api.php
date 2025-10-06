<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . "/../includes/db.php"; // Pfad angepasst: api/ liegt im Router drunter

header('Content-Type: application/json; charset=utf-8');

/**
 * JSON-Ausgabe + Logging
 */
function json_out(array $arr, int $code = 200): void {
    http_response_code($code);

    if (($arr['success'] ?? true) === false) {
        error_log("[owners_api.php] " . ($arr['error'] ?? 'Unbekannter Fehler'));
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
$action   = $input['action']    ?? ($_GET['action']    ?? '');
$owner_id = (int)($input['owner_id'] ?? ($_GET['owner_id'] ?? 0));

if ($action === '') {
    json_out(['success' => false, 'error' => '❌ Kein action-Parameter übergeben.'], 400);
}

// ---------- Besitzer bearbeiten ----------
if ($action === 'edit_owner') {
    if ($owner_id <= 0) json_out(['success' => false, 'error' => '❌ Ungültige Owner-ID.'], 400);

    $email  = trim((string)($input['email']   ?? ''));
    $phone  = trim((string)($input['phone']   ?? ''));
    $street = trim((string)($input['street']  ?? ''));
    $zip    = trim((string)($input['zipcode'] ?? ''));
    $city   = trim((string)($input['city']    ?? ''));

    try {
        $stmt = $pdo->prepare("
            UPDATE owners
               SET email = :email,
                   phone = :phone,
                   street = :street,
                   zipcode = :zip,
                   city = :city,
                   sync_status = 'local'
             WHERE id = :id
        ");
        $stmt->execute([
            ':email'  => $email,
            ':phone'  => $phone,
            ':street' => $street,
            ':zip'    => $zip,
            ':city'   => $city,
            ':id'     => $owner_id,
        ]);

        $stmt = $pdo->prepare("SELECT * FROM owners WHERE id = :id");
        $stmt->execute([':id' => $owner_id]);
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);

        json_out([
            'success' => true,
            'msg'     => '✅ Besitzer aktualisiert.',
            'owner'   => [
                'id'       => (int)$owner['id'],
                'email'    => (string)($owner['email'] ?? ''),
                'phone'    => (string)($owner['phone'] ?? ''),
                'street'   => (string)($owner['street'] ?? ''),
                'zipcode'  => (string)($owner['zipcode'] ?? ''),
                'city'     => (string)($owner['city'] ?? ''),
            ],
        ]);
    } catch (Throwable $e) {
        json_out(['success' => false, 'error' => 'DB: '.$e->getMessage()], 500);
    }
}

// ---------- Besitzer löschen ----------
if ($action === 'delete_owner') {
    if ($owner_id <= 0) json_out(['success' => false, 'error' => '❌ Ungültige Owner-ID.'], 400);

    try {
        $stmt = $pdo->prepare("DELETE FROM owners WHERE id = :id");
        $stmt->execute([':id' => $owner_id]);

        json_out(['success' => true, 'msg' => '🗑️ Besitzer gelöscht.']);
    } catch (Throwable $e) {
        json_out(['success' => false, 'error' => 'DB: '.$e->getMessage()], 500);
    }
}

// ---------- Notiz hinzufügen ----------
if ($action === 'add_note') {
    if ($owner_id <= 0) json_out(['success' => false, 'error' => '❌ Ungültige Owner-ID.'], 400);

    $content = trim((string)($input['note_content'] ?? ''));
    if ($content === '') {
        json_out(['success' => false, 'error' => '❌ Inhalt darf nicht leer sein.'], 400);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO notes (owner_id, content, sync_status, created_at)
            VALUES (:oid, :content, 'local', NOW())
        ");
        $stmt->execute([
            ':oid'     => $owner_id,
            ':content' => $content
        ]);

        $note_id = (int)$pdo->lastInsertId();
        $created = date('d.m.Y H:i');

        json_out([
            'success' => true,
            'note'    => [
                'id'         => $note_id,
                'content'    => $content,
                'created_at' => $created
            ]
        ]);
    } catch (Throwable $e) {
        json_out(['success' => false, 'error' => 'DB: '.$e->getMessage()], 500);
    }
}

// ---------- Notiz löschen ----------
if ($action === 'delete_note') {
    if ($owner_id <= 0) json_out(['success' => false, 'error' => '❌ Ungültige Owner-ID.'], 400);
    $note_id = (int)($input['note_id'] ?? ($_GET['note_id'] ?? 0));
    if ($note_id <= 0) json_out(['success' => false, 'error' => '❌ Ungültige Notiz-ID.'], 400);

    try {
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id AND owner_id = :oid");
        $stmt->execute([':id' => $note_id, ':oid' => $owner_id]);

        json_out(['success' => true, 'msg' => '🗑️ Notiz gelöscht.']);
    } catch (Throwable $e) {
        json_out(['success' => false, 'error' => 'DB: '.$e->getMessage()], 500);
    }
}

// ---------- Neuer Besitzer + Patient ----------
if ($action === 'create_owner_patient') {
    $owner = $input['owner'] ?? [];
    $patient = $input['patient'] ?? [];

    if (empty($owner['firstname']) || empty($owner['lastname'])) {
        json_out(['success' => false, 'error' => '❌ Vor- und Nachname des Besitzers sind Pflichtfelder.'], 400);
    }
    if (empty($patient['name'])) {
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
            ':firstname' => trim((string)($owner['firstname'] ?? '')),
            ':lastname'  => trim((string)($owner['lastname'] ?? '')),
            ':email'     => trim((string)($owner['email'] ?? '')),
            ':phone'     => trim((string)($owner['phone'] ?? '')),
            ':street'    => trim((string)($owner['street'] ?? '')),
            ':zipcode'   => trim((string)($owner['zipcode'] ?? '')),
            ':city'      => trim((string)($owner['city'] ?? '')),
            ':birthdate' => !empty($owner['birthdate']) ? $owner['birthdate'] : null,
        ]);
        $newOwnerId = (int)$pdo->lastInsertId();

        // Patient speichern
        $stmt = $pdo->prepare("
            INSERT INTO patients (owner_id, name, species, breed, birthdate, symptoms, medications, therapies, notes, sync_status)
            VALUES (:owner_id, :name, :species, :breed, :birthdate, :symptoms, :medications, :therapies, :notes, 'local')
        ");
        $stmt->execute([
            ':owner_id'   => $newOwnerId,
            ':name'       => trim((string)($patient['name'] ?? '')),
            ':species'    => trim((string)($patient['species'] ?? '')),
            ':breed'      => trim((string)($patient['breed'] ?? '')),
            ':birthdate'  => !empty($patient['birthdate']) ? $patient['birthdate'] : null,
            ':symptoms'   => trim((string)($patient['symptoms'] ?? '')),
            ':medications'=> trim((string)($patient['medications'] ?? '')),
            ':therapies'  => trim((string)($patient['therapies'] ?? '')),
            ':notes'      => trim((string)($patient['notes'] ?? '')),
        ]);
        $newPatientId = (int)$pdo->lastInsertId();

        $pdo->commit();

        json_out([
            'success' => true,
            'msg'     => '✅ Besitzer & Patient erfolgreich angelegt.',
            'owner_id'=> $newOwnerId,
            'patient_id'=> $newPatientId
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_out(['success' => false, 'error' => 'DB: '.$e->getMessage()], 500);
    }
}

// ---------- Fallback ----------
json_out(['success' => false, 'error' => '❌ Unbekannte Aktion.'], 400);