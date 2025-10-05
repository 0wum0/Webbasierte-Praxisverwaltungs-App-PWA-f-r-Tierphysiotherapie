<?php
declare(strict_types=1);

$host = "localhost";
$db   = "u772175418_ew";   // Datenbankname
$user = "u772175418_ew";         // Datenbank-Benutzer
$pass = ":oxpJgE2*";             // Datenbank-Passwort
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Fehler als Exception
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Standard: assoziatives Array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // echte Prepared Statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("❌ DB-Verbindung fehlgeschlagen: " . htmlspecialchars($e->getMessage()));
}