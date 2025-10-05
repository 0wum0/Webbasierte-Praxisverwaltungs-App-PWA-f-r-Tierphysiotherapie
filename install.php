<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/db.php";

// Dompdf laden
require_once __DIR__ . "/vendor/autoload.php";
use Dompdf\Dompdf;

echo "<h2>🚀 Installer läuft...</h2>";

try {
    // Besitzer
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS owners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            firstname VARCHAR(100) NOT NULL,
            lastname VARCHAR(100) NOT NULL,
            email VARCHAR(150) DEFAULT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            street VARCHAR(150) DEFAULT NULL,
            zipcode VARCHAR(20) DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            birthdate DATE DEFAULT NULL,
            sync_status ENUM('local','synced') DEFAULT 'local',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Patienten
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            species VARCHAR(50) DEFAULT NULL,
            breed VARCHAR(50) DEFAULT NULL,
            birthdate DATE DEFAULT NULL,
            findings TEXT DEFAULT NULL,
            medications TEXT DEFAULT NULL,
            therapies TEXT DEFAULT NULL,
            symptoms TEXT DEFAULT NULL,
            extras TEXT DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            sync_status ENUM('local','synced') DEFAULT 'local',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_patients_owner FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Termine
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            appointment_date DATETIME NOT NULL,
            notes TEXT DEFAULT NULL,
            sync_status ENUM('local','synced') DEFAULT 'local',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_appointments_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Rechnungen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            appointment_id INT DEFAULT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT DEFAULT NULL,
            status ENUM('open','paid') DEFAULT 'open',
            pdf_path VARCHAR(255) DEFAULT NULL,
            sync_status ENUM('local','synced') DEFAULT 'local',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_invoices_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            CONSTRAINT fk_invoices_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Rechnungspositionen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoice_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            description VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Notizen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT DEFAULT NULL,
            owner_id INT DEFAULT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sync_status ENUM('local','synced') DEFAULT 'local',
            CONSTRAINT fk_notes_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            CONSTRAINT fk_notes_owner FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Einstellungen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT DEFAULT NULL,
            sync_status ENUM('local','synced') DEFAULT 'local',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Dummy-Daten
    $count = $pdo->query("SELECT COUNT(*) FROM owners")->fetchColumn();
    if ((int)$count === 0) {
        // Besitzer
        $pdo->exec("
            INSERT INTO owners (firstname, lastname, email, phone, street, zipcode, city, birthdate)
            VALUES ('Max', 'Mustermann', 'max@example.com', '01234-56789', 'Musterstraße 1', '12345', 'Musterstadt', '1980-03-15')
        ");
        $ownerId = (int)$pdo->lastInsertId();

        // Patient
        $pdo->exec("
            INSERT INTO patients (owner_id, name, species, breed, birthdate, findings, medications, therapies, symptoms, extras, notes, image)
            VALUES ($ownerId, 'Bello', 'Hund', 'Labrador', '2018-05-10', 'Leichte Lahmheit', 'Schmerzmittel', 'Physiotherapie', 'Humpelt beim Laufen', 'Zusatz: Massage', 'Sehr zutraulich', NULL)
        ");
        $patientId = (int)$pdo->lastInsertId();

        // Termin
        $pdo->exec("
            INSERT INTO appointments (patient_id, appointment_date, notes)
            VALUES ($patientId, NOW() + INTERVAL 3 DAY, 'Erstuntersuchung')
        ");
        $appointmentId = (int)$pdo->lastInsertId();

        // Rechnung
        $pdo->exec("
            INSERT INTO invoices (patient_id, appointment_id, amount, status)
            VALUES ($patientId, $appointmentId, 75.00, 'open')
        ");
        $invoiceId = (int)$pdo->lastInsertId();

        // Rechnungsposition
        $pdo->exec("
            INSERT INTO invoice_items (invoice_id, description, amount)
            VALUES ($invoiceId, 'Physiotherapie 60 Minuten', 75.00)
        ");

        // PDF-Rechnung erzeugen
        $html = "
            <h1>Rechnung #$invoiceId</h1>
            <p><strong>Patient:</strong> Bello</p>
            <p><strong>Besitzer:</strong> Max Mustermann</p>
            <p><strong>Leistung:</strong> Physiotherapie 60 Minuten</p>
            <p><strong>Betrag:</strong> 75,00 €</p>
        ";
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfDir = __DIR__ . "/invoices/";
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0777, true);
        }
        $pdfPath = $pdfDir . "demo_invoice_" . $invoiceId . ".pdf";
        file_put_contents($pdfPath, $dompdf->output());

        // Pfad in DB speichern
        $stmt = $pdo->prepare("UPDATE invoices SET pdf_path = :pdf WHERE id = :id");
        $stmt->execute([
            ":pdf" => "invoices/demo_invoice_" . $invoiceId . ".pdf",
            ":id"  => $invoiceId
        ]);

        // Notiz
        $pdo->exec("
            INSERT INTO notes (patient_id, content)
            VALUES ($patientId, 'Patient wirkt freundlich und entspannt.')
        ");

        // Einstellungen
        $pdo->exec("
            INSERT INTO settings (setting_key, setting_value)
            VALUES 
            ('praxis_name', 'Tierphysio Eileen Wenzel'),
            ('currency', 'EUR'),
            ('hourly_rate', '75'),
            ('smtp_host', 'smtp.example.com'),
            ('smtp_user', 'info@example.com'),
            ('smtp_pass', 'geheim'),
            ('smtp_port', '587')
        ");
    }

    echo "<p style='color:lime'>✅ Installation erfolgreich abgeschlossen! Dummy-Daten und Demo-Rechnung erstellt.</p>";

} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}