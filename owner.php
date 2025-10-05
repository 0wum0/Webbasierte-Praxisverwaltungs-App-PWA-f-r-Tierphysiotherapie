<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/includes/twig.php";

// -------------------------------
// Besitzer laden (Übersicht)
// -------------------------------
$stmt = $pdo->query("
    SELECT o.*,
           (SELECT COUNT(*) FROM patients p WHERE p.owner_id = o.id) AS patient_count,
           (SELECT COUNT(*) FROM invoices i JOIN patients p ON i.patient_id = p.id WHERE p.owner_id = o.id) AS invoice_count,
           (SELECT COUNT(*) FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE p.owner_id = o.id) AS appointment_count
    FROM owners o
    ORDER BY o.lastname ASC, o.firstname ASC
");
$owners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------
// Details laden (pro Besitzer)
// -------------------------------
$details = [];

foreach ($owners as $o) {
    $oid = (int)$o['id'];

    // Patienten
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE owner_id = :oid ORDER BY name ASC");
    $stmt->execute([":oid" => $oid]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rechnungen
    $stmt = $pdo->prepare("
        SELECT i.*, p.name AS patient_name
        FROM invoices i
        JOIN patients p ON i.patient_id = p.id
        WHERE p.owner_id = :oid
        ORDER BY i.id DESC
    ");
    $stmt->execute([":oid" => $oid]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Termine
    $stmt = $pdo->prepare("
        SELECT a.*, p.name AS patient_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE p.owner_id = :oid
        ORDER BY a.appointment_date DESC
    ");
    $stmt->execute([":oid" => $oid]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Notizen
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE owner_id = :oid ORDER BY created_at DESC");
    $stmt->execute([":oid" => $oid]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $details[$oid] = [
        "patients"     => $patients,
        "invoices"     => $invoices,
        "appointments" => $appointments,
        "notes"        => $notes
    ];
}

// -------------------------------
// Aktionen: Notiz oder Bearbeiten
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $owner_id = (int)($_POST['owner_id'] ?? 0);

    $isAjax = (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    );

    $response = ["success" => false, "msg" => "❌ Unbekannte Aktion"];

    if ($action === 'add_note') {
        $content = trim($_POST['note_content'] ?? '');
        if ($content !== '') {
            $stmt = $pdo->prepare("INSERT INTO notes (owner_id, content, sync_status) VALUES (:oid, :content, 'local')");
            $stmt->execute([":oid" => $owner_id, ":content" => $content]);
            $response = ["success" => true, "msg" => "✅ Notiz hinzugefügt."];
        } else {
            $response = ["success" => false, "msg" => "❌ Bitte Inhalt eingeben."];
        }
    }

    if ($action === 'delete_note') {
        $note_id = (int)($_POST['note_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id AND owner_id = :oid");
        $stmt->execute([":id" => $note_id, ":oid" => $owner_id]);
        $response = ["success" => true, "msg" => "🗑️ Notiz gelöscht."];
    }

    if ($action === 'edit_owner') {
        $email  = $_POST['email'] ?? '';
        $phone  = $_POST['phone'] ?? '';
        $street = $_POST['street'] ?? '';
        $zip    = $_POST['zipcode'] ?? '';
        $city   = $_POST['city'] ?? '';

        $stmt = $pdo->prepare("
            UPDATE owners 
            SET email = :email, phone = :phone, street = :street, zipcode = :zip, city = :city, sync_status = 'local'
            WHERE id = :id
        ");
        $stmt->execute([
            ":email" => $email,
            ":phone" => $phone,
            ":street"=> $street,
            ":zip"   => $zip,
            ":city"  => $city,
            ":id"    => $owner_id
        ]);

        $response = ["success" => true, "msg" => "✅ Besitzer aktualisiert."];
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    } else {
        // Session Notification für klassischen Redirect
        $_SESSION['notify'][] = [
            "type" => $response['success'] ? "success" : "error",
            "msg"  => $response['msg']
        ];
        header("Location: owners.php");
        exit;
    }
}

// -------------------------------
// Notifications
// -------------------------------
$notifications = $_SESSION['notify'] ?? [];
unset($_SESSION['notify']);

// -------------------------------
// Render
// -------------------------------
echo $twig->render("owners.twig", [
    "title"        => "Besitzerverwaltung",
    "owners"       => $owners,
    "details"      => $details,
    "notifications"=> $notifications
]);