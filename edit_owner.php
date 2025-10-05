<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/includes/twig.php";
require_once __DIR__ . "/includes/csrf.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success = false;

// Besitzer laden, falls Bearbeitung
$owner = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM owners WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Formular abgesendet?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_validate();
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
        goto render;
    }
    
    $firstname  = trim($_POST['firstname'] ?? '');
    $lastname   = trim($_POST['lastname'] ?? '');
    $street     = trim($_POST['street'] ?? '');
    $zipcode    = trim($_POST['zipcode'] ?? '');
    $city       = trim($_POST['city'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $notes      = trim($_POST['notes'] ?? '');

    if ($firstname === '' || $lastname === '') {
        $errors[] = "Bitte Vor- und Nachname eingeben.";
    }

    if (empty($errors)) {
        if ($id > 0) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE owners
                SET firstname=:firstname, lastname=:lastname, street=:street, zipcode=:zipcode,
                    city=:city, phone=:phone, email=:email, notes=:notes, sync_status='local'
                WHERE id=:id
            ");
            $stmt->execute([
                ":firstname" => $firstname,
                ":lastname"  => $lastname,
                ":street"    => $street,
                ":zipcode"   => $zipcode,
                ":city"      => $city,
                ":phone"     => $phone,
                ":email"     => $email,
                ":notes"     => $notes,
                ":id"        => $id
            ]);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO owners (firstname, lastname, street, zipcode, city, phone, email, notes, sync_status)
                VALUES (:firstname, :lastname, :street, :zipcode, :city, :phone, :email, :notes, 'local')
            ");
            $stmt->execute([
                ":firstname" => $firstname,
                ":lastname"  => $lastname,
                ":street"    => $street,
                ":zipcode"   => $zipcode,
                ":city"      => $city,
                ":phone"     => $phone,
                ":email"     => $email,
                ":notes"     => $notes
            ]);
            $id = (int)$pdo->lastInsertId();
        }

        $success = true;
        // Nach Speichern zurück zur Patientenbearbeitung oder Liste
        if (isset($_GET['return']) && $_GET['return'] === 'patient') {
            header("Location: edit_patient.php");
        } else {
            header("Location: patients.php");
        }
        exit;
    }
}

// Render
render:
echo $twig->render("edit_owner.twig", [
    "owner" => $owner,
    "errors" => $errors,
    "success" => $success
]);