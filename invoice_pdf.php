<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/includes/twig.php";
require_once __DIR__ . "/vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

// Rechnungs-ID abholen
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("❌ Ungültige Rechnungs-ID");
}

// Rechnung + Patient + Besitzer laden
$stmt = $pdo->prepare("
    SELECT i.*, 
           p.name AS patient_name, p.species, p.birthdate AS patient_birthdate,
           o.id AS owner_id, o.firstname, o.lastname, o.street, o.zipcode, o.city, o.email
    FROM invoices i
    JOIN patients p ON i.patient_id = p.id
    JOIN owners o ON p.owner_id = o.id
    WHERE i.id = :id
");
$stmt->execute([":id" => $id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    die("❌ Rechnung nicht gefunden");
}

// Rechnungs-Positionen laden
$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = :id");
$stmt->execute([":id" => $id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Laufende Nummer des Besitzers bestimmen
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM invoices i 
    JOIN patients p ON i.patient_id = p.id 
    WHERE p.owner_id = :oid AND i.id <= :iid
");
$stmt->execute([":oid" => $invoice['owner_id'], ":iid" => $id]);
$laufendeNummer = (int)$stmt->fetchColumn();

// Rechnungsnummer generieren: MMYYYY/OwnerID.LfdNr
$rechnungsNummer = date("mY") . "/" . $invoice['owner_id'] . "." . $laufendeNummer;

// Einstellungen laden (Logo, Bankdaten, Zahlungsziel)
$stmt = $pdo->query("
    SELECT setting_key, setting_value 
    FROM settings 
    WHERE setting_key IN ('invoice_logo','invoice_payment_terms','invoice_bank_details')
");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Logo-URL festlegen (immer HTTPS, damit Dompdf es laden kann)
if (!empty($settings['invoice_logo'])) {
    $logoPath = $settings['invoice_logo'];
    // Falls kein http/https dabei → relative Angabe in vollständige URL wandeln
    if (strpos($logoPath, 'http') !== 0) {
        $logoPath = "https://ew.makeit.uno/" . ltrim($logoPath, '/');
    }
} else {
    // Fallback: Standardlogo im assets-Ordner
    $logoPath = "https://ew.makeit.uno/assets/img/logo.png";
}

// Zahlungsziel und Bankdaten
$paymentTerms = $settings['invoice_payment_terms'] ?? "14 Tage nach Rechnungsdatum";
$bankDetails  = $settings['invoice_bank_details'] ?? "IBAN: DE00 0000 0000 0000 0000 00 – BIC: XXXXDE00XXX";

// Twig rendern → HTML für PDF
$html = $twig->render("invoice_pdf.twig", [
    "invoice"         => $invoice,
    "items"           => $items,
    "today"           => date("d.m.Y"),
    "rechnungsNummer" => $rechnungsNummer,
    "logoPath"        => $logoPath,
    "paymentTerms"    => $paymentTerms,
    "bankDetails"     => $bankDetails
]);

// Dompdf vorbereiten
$options = new Options();
$options->set('isRemoteEnabled', true); // wichtig für HTTPS-Logo
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Direkt im Browser ausgeben
$dompdf->stream("Rechnung_" . $rechnungsNummer . ".pdf", ["Attachment" => false]);
exit;