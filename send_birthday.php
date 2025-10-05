<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/includes/twig.php";
require_once __DIR__ . "/includes/mail.php";

require_once __DIR__ . "/vendor/autoload.php";
use PHPMailer\PHPMailer\Exception;

// Datum heute (MM-TT)
$today = date("m-d");

// Besitzer mit Geburtstag heute
$stmt = $pdo->prepare("SELECT * FROM owners WHERE birthdate IS NOT NULL AND DATE_FORMAT(birthdate, '%m-%d') = :today");
$stmt->execute([":today" => $today]);
$owners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Patienten mit Geburtstag heute (Mail geht an Besitzer)
$stmt = $pdo->prepare("
    SELECT p.*, o.firstname, o.lastname, o.email
    FROM patients p
    JOIN owners o ON p.owner_id = o.id
    WHERE p.birthdate IS NOT NULL AND DATE_FORMAT(p.birthdate, '%m-%d') = :today
");
$stmt->execute([":today" => $today]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mail-Settings laden
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('birthday_mail_subject','birthday_mail_body')");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$subjectTemplate = $settings['birthday_mail_subject'] ?? "Alles Gute zum Geburtstag!";
$bodyTemplate = $settings['birthday_mail_body'] ?? "Liebe/r [NAME],\n\nwir wünschen Ihnen und [TIERNAME] alles Gute zum Geburtstag! 🎉🐾\n\nHerzliche Grüße\nTierphysio Eileen Wenzel";

/**
 * Mail versenden
 */
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

    // HTML über Twig
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

        echo "✅ Geburtstagsmail an " . htmlspecialchars($recipient['email']) . " gesendet.<br>";
        return true;
    } catch (Exception $e) {
        echo "❌ Fehler bei " . htmlspecialchars($recipient['email']) . ": " . $e->getMessage() . "<br>";
        return false;
    }
}

// Besitzer-Mails senden
foreach ($owners as $owner) {
    sendBirthdayMail($owner, $subjectTemplate, $bodyTemplate, $twig, $pdo);
}

// Patienten-Mails senden (gehen an Besitzer)
foreach ($patients as $patient) {
    sendBirthdayMail($patient, $subjectTemplate, $bodyTemplate, $twig, $pdo);
}

echo "<p>🎂 Alle Geburtstagsmails verarbeitet.</p>";