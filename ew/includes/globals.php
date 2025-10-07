<?php
// Globale Variablen wie Geburtstagszähler laden
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM (
            SELECT id, birthdate 
            FROM patients 
            WHERE birthdate IS NOT NULL 
              AND DATE_FORMAT(birthdate, '%m-%d') BETWEEN DATE_FORMAT(NOW(), '%m-%d')
              AND DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 7 DAY), '%m-%d')
            UNION ALL
            SELECT id, birthdate 
            FROM owners 
            WHERE birthdate IS NOT NULL 
              AND DATE_FORMAT(birthdate, '%m-%d') BETWEEN DATE_FORMAT(NOW(), '%m-%d')
              AND DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 7 DAY), '%m-%d')
        ) as all_birthdays
    ");
    $stmt->execute();
    $birthdayCount = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $birthdayCount = 0;
}

// In Twig verfügbar machen
$twig->addGlobal("birthdayCount", $birthdayCount);