<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/twig.php";
require_once __DIR__ . "/includes/mail.php";

require_once __DIR__ . "/vendor/autoload.php"; // Dompdf + PHPMailer
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\Exception;

// Rechnungs-ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['notify'][] = [
        "type" => "error",
        "msg"  => "❌ Ungültige Rechnungs-ID."
    ];
    header("Location: invoices.php");
    exit;
}

// Rechnung laden
$stmt = $pdo->prepare("
    SELECT i.*, 
           p.name AS patient_name, p.species, p.birthdate AS patient_birthdate,
           o.id AS owner_id, o.firstname, o.lastname, o.email, o.street, o.zipcode, o.city
    FROM invoices i
    JOIN patients p ON i.patient_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE i.id = :id
");
$stmt->execute([":id" => $id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    $_SESSION['notify'][] = [
        "type" => "error",
        "msg"  => "❌ Rechnung wurde nicht gefunden."
    ];
    header("Location: invoices.php");
    exit;
}
if (empty($invoice['email'])) {
    $_SESSION['notify'][] = [
        "type" => "warning",
        "msg"  => "⚠️ Besitzer von Rechnung #$id hat keine E-Mail-Adresse."
    ];
    header("Location: invoices.php");
    exit;
}

// Positionen laden (robust gegen alte Spaltennamen)
$stmt = $pdo->prepare("
    SELECT 
        id,
        invoice_id,
        position,
        COALESCE(title, description)  AS title,
        COALESCE(price, amount)       AS price
    FROM invoice_items
    WHERE invoice_id = :id
    ORDER BY position IS NULL, position, id
");
$stmt->execute([":id" => $id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Laufende Nummer pro Besitzer bestimmen
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM invoices i
    JOIN patients p ON i.patient_id = p.id
    WHERE p.owner_id = :oid AND i.id <= :iid
");
$stmt->execute([":oid" => $invoice['owner_id'], ":iid" => $id]);
$laufendeNummer = (int)$stmt->fetchColumn();

// Rechnungsnummer generieren -> MMYYYY/OwnerID.LfdNr
$rechnungsNummer = date("mY") . "/" . $invoice['owner_id'] . "." . $laufendeNummer;

// Einstellungen laden (Bankdaten, Texte)
$stmt = $pdo->query("
    SELECT setting_key, setting_value 
    FROM settings 
    WHERE setting_key IN ('invoice_payment_terms','invoice_bank_details','invoice_mail_subject','invoice_mail_body')
");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Logo-Pfad → fest unser transparentes Logo
$logoPath = __DIR__ . "/assets/img/logo_transparent.png";
if (file_exists($logoPath)) {
    $logoPath = "file:///" . str_replace("\\", "/", realpath($logoPath));
} else {
    $logoPath = null;
}

// Zahlungsziel und Bankdaten
$paymentTerms = $settings['invoice_payment_terms'] ?? "14 Tage nach Rechnungsdatum";
$bankDetails  = $settings['invoice_bank_details'] ?? "IBAN: DE00 0000 0000 0000 0000 00 – BIC: XXXXDE00XXX";

// PDF erzeugen
$html = $twig->render("invoice_pdf.twig", [
    "invoice"         => $invoice,
    "items"           => $items,
    "today"           => date("d.m.Y"),
    "rechnungsNummer" => $rechnungsNummer,
    "logoPath"        => $logoPath,
    "paymentTerms"    => $paymentTerms,
    "bankDetails"     => $bankDetails
]);

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfContent = $dompdf->output();

// Mailer erstellen
$mail = createMailer($pdo);

// Mail-Texte
$subjectTemplate = $settings['invoice_mail_subject'] ?? "Ihre Rechnung Nr. [RECHNUNGSNUMMER]";
$bodyTemplate    = $settings['invoice_mail_body'] ??
    "Sehr geehrte/r [NAME],\n\nim Anhang finden Sie Ihre Rechnung Nr. [RECHNUNGSNUMMER].\n\nMit freundlichen Grüßen\nTierphysio Eileen Wenzel";

// Platzhalter ersetzen
$subject = str_replace("[RECHNUNGSNUMMER]", $rechnungsNummer, $subjectTemplate);
$bodyText = str_replace(
    ["[NAME]", "[RECHNUNGSNUMMER]"],
    [$invoice['firstname'] . " " . $invoice['lastname'], $rechnungsNummer],
    $bodyTemplate
);

// HTML-Version über globale Mail-Vorlage
$bodyHtml = $twig->render("mail_template.twig", [
    "body" => nl2br($bodyText)
]);

try {
    // Empfänger
    $mail->addAddress($invoice['email'], $invoice['firstname'] . " " . $invoice['lastname']);

    // Betreff & Nachricht
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $bodyHtml;
    $mail->AltBody = $bodyText;

    // PDF anhängen
    $mail->addStringAttachment($pdfContent, "Rechnung_" . $rechnungsNummer . ".pdf");

    // Absenden
    $mail->send();

    // DB-Update
    $stmt = $pdo->prepare("UPDATE invoices SET updated_at = NOW(), sync_status = 'local' WHERE id = :id");
    $stmt->execute([":id" => $id]);

    $_SESSION['notify'][] = [
        "type" => "success",
        "msg"  => "✅ Rechnung $rechnungsNummer wurde erfolgreich an " . $invoice['email'] . " gesendet."
    ];

} catch (Exception $e) {
    $_SESSION['notify'][] = [
        "type" => "error",
        "msg"  => "❌ Fehler beim Senden von Rechnung $rechnungsNummer: " . $e->getMessage()
    ];
}

// Immer zurück zur Übersicht
header("Location: invoices.php");
exit;