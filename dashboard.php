<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/twig.php";
require_once __DIR__ . "/includes/mail.php";

require_once __DIR__ . "/vendor/autoload.php";
use PHPMailer\PHPMailer\Exception;

// Datum heute (Monat-Tag)
$today = date("m-d");

// Geburtstagsmails senden, wenn Button geklickt
if (isset($_GET['action']) && $_GET['action'] === 'send_birthday_mails') {
    // Mail-Settings laden
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('birthday_mail_subject','birthday_mail_body')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $subjectTemplate = $settings['birthday_mail_subject'] ?? "Alles Gute zum Geburtstag!";
    $bodyTemplate = $settings['birthday_mail_body'] ?? "Liebe/r [NAME],\n\nwir wünschen Ihnen und [TIERNAME] alles Gute zum Geburtstag! 🎉🐾\n\nHerzliche Grüße\nTierphysio Eileen Wenzel";

    // Besitzer mit Geburtstag heute
    $stmt = $pdo->prepare("
        SELECT * FROM owners 
        WHERE birthdate IS NOT NULL 
          AND DATE_FORMAT(birthdate, '%m-%d') = CAST(:today AS CHAR CHARACTER SET utf8mb4)
    ");
    $stmt->execute([":today" => $today]);
    $birthdayOwners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Patienten mit Geburtstag heute
    $stmt = $pdo->prepare("
        SELECT p.*, o.firstname, o.lastname, o.email
        FROM patients p
        JOIN owners o ON p.owner_id = o.id
        WHERE p.birthdate IS NOT NULL 
          AND DATE_FORMAT(p.birthdate, '%m-%d') = CAST(:today AS CHAR CHARACTER SET utf8mb4)
    ");
    $stmt->execute([":today" => $today]);
    $birthdayPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Funktion zum Senden
    function sendBirthdayMail(array $recipient, string $subjectTemplate, string $bodyTemplate, $twig, PDO $pdo): bool {
        if (empty($recipient['email'])) {
            return false;
        }

        $subject = str_replace(
            ["[NAME]", "[TIERNAME]"],
            [$recipient['firstname'] . " " . $recipient['lastname'], $recipient['name'] ?? ""],
            $subjectTemplate
        );

        $bodyText = str_replace(
            ["[NAME]", "[TIERNAME]"],
            [$recipient['firstname'] . " " . $recipient['lastname'], $recipient['name'] ?? ""],
            $bodyTemplate
        );

        $bodyHtml = $twig->render("mail_template.twig", [
            "body" => nl2br($bodyText)
        ]);

        $mail = createMailer($pdo);

        try {
            $mail->addAddress($recipient['email'], $recipient['firstname'] . " " . $recipient['lastname']);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = $bodyText;
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    $sentCount = 0;
    foreach ($birthdayOwners as $owner) {
        if (sendBirthdayMail($owner, $subjectTemplate, $bodyTemplate, $twig, $pdo)) {
            $sentCount++;
        }
    }
    foreach ($birthdayPatients as $patient) {
        if (sendBirthdayMail($patient, $subjectTemplate, $bodyTemplate, $twig, $pdo)) {
            $sentCount++;
        }
    }

    // Erfolgsmeldung in Session speichern
    session_start();
    $_SESSION['birthday_success'] = "🎉 Es wurden {$sentCount} Geburtstagsmails gesendet.";
    header("Location: dashboard.php#birthdays");
    exit;
}

// Einnahmen/Ausgaben für diesen Monat und Jahr
$stmt = $pdo->query("SELECT SUM(amount) FROM invoices WHERE status='paid' AND YEAR(updated_at)=YEAR(CURDATE()) AND MONTH(updated_at)=MONTH(CURDATE())");
$incomeMonth = (float) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(amount) FROM invoices WHERE status='paid' AND YEAR(updated_at)=YEAR(CURDATE())");
$incomeYear = (float) $stmt->fetchColumn();

// Alle Einnahmen gesamt
$stmt = $pdo->query("SELECT SUM(amount) FROM invoices WHERE status='paid'");
$totalIncome = (float) $stmt->fetchColumn();

// Offene Rechnungen
$stmt = $pdo->query("SELECT SUM(amount) FROM invoices WHERE status='open'");
$totalExpenses = (float) $stmt->fetchColumn();

// Termine heute
$stmt = $pdo->prepare("
    SELECT a.*, p.name AS patient_name, o.firstname, o.lastname
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE DATE(a.appointment_date) = CURDATE()
    ORDER BY a.appointment_date ASC
");
$stmt->execute();
$appointmentsToday = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Geburtstage laden für Anzeige
$stmt = $pdo->prepare("
    SELECT * FROM owners 
    WHERE birthdate IS NOT NULL 
      AND DATE_FORMAT(birthdate, '%m-%d') = CAST(:today AS CHAR CHARACTER SET utf8mb4)
");
$stmt->execute([":today" => $today]);
$birthdayOwners = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT p.*, o.firstname, o.lastname
    FROM patients p
    JOIN owners o ON p.owner_id = o.id
    WHERE p.birthdate IS NOT NULL 
      AND DATE_FORMAT(p.birthdate, '%m-%d') = CAST(:today AS CHAR CHARACTER SET utf8mb4)
");
$stmt->execute([":today" => $today]);
$birthdayPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Erfolgsmeldung nach Versand
$birthdaySuccess = $_SESSION['birthday_success'] ?? null;
unset($_SESSION['birthday_success']);

// Render
echo $twig->render("dashboard.twig", [
    "title" => "Dashboard",
    "incomeMonth" => $incomeMonth,
    "incomeYear" => $incomeYear,
    "totalIncome" => $totalIncome,
    "totalExpenses" => $totalExpenses,
    "appointmentsToday" => $appointmentsToday,
    "birthdayOwners" => $birthdayOwners,
    "birthdayPatients" => $birthdayPatients,
    "birthdaySuccess" => $birthdaySuccess
]);