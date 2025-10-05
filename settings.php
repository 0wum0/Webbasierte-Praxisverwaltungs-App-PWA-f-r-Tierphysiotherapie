<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/includes/twig.php";

$errors = [];
$success = false;

// CSRF-Schutz für POST-Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_validate();
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
        // Keine weitere Verarbeitung wenn CSRF fehlschlägt
        goto render;
    }
}

// Vorhandene Einstellungen laden
$stmt = $pdo->query("SELECT * FROM settings ORDER BY setting_key ASC");
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
$settingsMap = [];
foreach ($settings as $s) {
    $settingsMap[$s['setting_key']] = $s['setting_value'];
}

// Formular: Eigene Key/Value Settings
if (isset($_POST['setting_key']) && isset($_POST['setting_value'])) {
    $key = trim($_POST['setting_key']);
    $value = trim($_POST['setting_value']);

    if ($key === '') {
        $errors[] = "Bitte einen Schlüssel (Name) eingeben.";
    }
    if ($value === '') {
        $errors[] = "Bitte einen Wert eingeben.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = :key");
        $stmt->execute([":key" => $key]);
        $exists = (bool)$stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value=:value, sync_status='local' WHERE setting_key=:key");
            $stmt->execute([":value" => $value, ":key" => $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, sync_status) VALUES (:key, :value, 'local')");
            $stmt->execute([":key" => $key, ":value" => $value]);
        }

        header("Location: settings.php");
        exit;
    }
}

// Formular: SMTP Einstellungen
if (isset($_POST['smtp_host'])) {
    $smtpFields = ["smtp_host", "smtp_port", "smtp_user", "smtp_pass", "smtp_from", "smtp_from_name"];

    foreach ($smtpFields as $field) {
        $value = trim($_POST[$field] ?? "");
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = :key");
        $stmt->execute([":key" => $field]);
        $exists = (bool)$stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value=:value, sync_status='local' WHERE setting_key=:key");
            $stmt->execute([":value" => $value, ":key" => $field]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, sync_status) VALUES (:key, :value, 'local')");
            $stmt->execute([":key" => $field, ":value" => $value]);
        }
    }

    header("Location: settings.php");
    exit;
}

// Formular: Rechnungs-Einstellungen (inkl. Logo-Upload)
if (isset($_POST['invoice_mail_subject']) || isset($_POST['invoice_bank_details']) || isset($_FILES['invoice_logo_file'])) {
    $invoiceFields = ["invoice_bank_details", "invoice_payment_terms", "invoice_mail_subject", "invoice_mail_body"];

    foreach ($invoiceFields as $field) {
        $value = trim($_POST[$field] ?? "");
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = :key");
        $stmt->execute([":key" => $field]);
        $exists = (bool)$stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value=:value, sync_status='local' WHERE setting_key=:key");
            $stmt->execute([":value" => $value, ":key" => $field]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, sync_status) VALUES (:key, :value, 'local')");
            $stmt->execute([":key" => $field, ":value" => $value]);
        }
    }

    // Logo-Upload
    if (!empty($_FILES['invoice_logo_file']['name'])) {
        $uploadDir = __DIR__ . "/assets/img/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $fileTmp  = $_FILES['invoice_logo_file']['tmp_name'];
        $fileName = basename($_FILES['invoice_logo_file']['name']);
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowed = ['png', 'jpg', 'jpeg'];
        if (!in_array($fileExt, $allowed)) {
            $errors[] = "Nur PNG oder JPG erlaubt.";
        } elseif ($_FILES['invoice_logo_file']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Die Datei darf maximal 2 MB groß sein.";
        } else {
            $newName = "logo." . $fileExt;
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($fileTmp, $dest)) {
                $publicPath = "assets/img/" . $newName;

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = 'invoice_logo'");
                $stmt->execute();
                $exists = (bool)$stmt->fetchColumn();

                if ($exists) {
                    $stmt = $pdo->prepare("UPDATE settings SET setting_value=:value, sync_status='local' WHERE setting_key='invoice_logo'");
                    $stmt->execute([":value" => $publicPath]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, sync_status) VALUES ('invoice_logo', :value, 'local')");
                    $stmt->execute([":value" => $publicPath]);
                }
            } else {
                $errors[] = "Fehler beim Hochladen des Logos.";
            }
        }
    }

    if (empty($errors)) {
        header("Location: settings.php");
        exit;
    }
}

// Formular: Geburtstagsmail-Einstellungen
if (isset($_POST['birthday_mail_subject'])) {
    $birthdayFields = ["birthday_mail_subject", "birthday_mail_body"];

    foreach ($birthdayFields as $field) {
        $value = trim($_POST[$field] ?? "");
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = :key");
        $stmt->execute([":key" => $field]);
        $exists = (bool)$stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value=:value, sync_status='local' WHERE setting_key=:key");
            $stmt->execute([":value" => $value, ":key" => $field]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, sync_status) VALUES (:key, :value, 'local')");
            $stmt->execute([":key" => $field, ":value" => $value]);
        }
    }

    header("Location: settings.php");
    exit;
}

// Render
render:
echo $twig->render("settings.twig", [
    "settings"     => $settings,
    "settingsMap"  => $settingsMap,
    "errors"       => $errors,
    "success"      => $success
]);