<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Holt die gespeicherten SMTP-Einstellungen aus der Datenbank.
 */
function getMailSettings(PDO $pdo): array {
    $settings = [
        "smtp_host" => "",
        "smtp_port" => "587",
        "smtp_user" => "",
        "smtp_pass" => "",
        "smtp_from" => "",
        "smtp_from_name" => "Tierphysio"
    ];

    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    return $settings;
}

/**
 * Erstellt und konfiguriert ein PHPMailer-Objekt auf Basis der gespeicherten Einstellungen.
 */
function createMailer(PDO $pdo): PHPMailer {
    $mailSettings = getMailSettings($pdo);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $mailSettings['smtp_host'] ?: "localhost";
    $mail->SMTPAuth = true;
    $mail->Username = $mailSettings['smtp_user'];
    $mail->Password = $mailSettings['smtp_pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int)($mailSettings['smtp_port'] ?: 587);

    $mail->CharSet = "UTF-8";

    // Absender
    $fromAddress = $mailSettings['smtp_from'] ?: $mailSettings['smtp_user'];
    $fromName    = $mailSettings['smtp_from_name'] ?: "Tierphysio";

    $mail->setFrom($fromAddress, $fromName);
    $mail->addReplyTo($fromAddress, $fromName);

    return $mail;
}