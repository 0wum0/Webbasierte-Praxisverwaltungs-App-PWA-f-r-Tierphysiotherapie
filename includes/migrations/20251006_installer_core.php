<?php
declare(strict_types=1);

/**
 * Core-Migration für den Installer
 * 
 * Erstellt die grundlegenden Tabellen für:
 * - Admin-Benutzer (admin_users)
 * - App-Einstellungen (app_settings)
 * - Sowie alle anderen erforderlichen Tabellen (idempotent)
 */

/**
 * Führt die Core-Migration für den Installer aus
 * 
 * @param PDO $pdo Datenbankverbindung
 * @throws Exception bei Fehlern
 */
function runInstallerCoreMigration(PDO $pdo): void
{
    try {
        // Transaktion starten für Atomarität
        $pdo->beginTransaction();

        // ============================================================================
        // 1. Admin Users Tabelle
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                display_name VARCHAR(190) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                -- Zusätzliche Sicherheitsfelder
                failed_login_attempts INT DEFAULT 0,
                locked_until DATETIME NULL,
                last_login DATETIME NULL,
                last_login_ip VARCHAR(45) NULL,
                
                -- Index für Performance
                INDEX idx_email (email),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 2. App Settings Tabelle
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS app_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                skey VARCHAR(190) NOT NULL UNIQUE,
                svalue TEXT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Index für Performance
                INDEX idx_key (skey)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 3. Besitzer (owners) - Existierende Tabelle prüfen und erweitern
        // ============================================================================
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
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Indizes für Performance
                INDEX idx_email (email),
                INDEX idx_lastname (lastname),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 4. Patienten (patients) - Existierende Tabelle prüfen und erweitern
        // ============================================================================
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
                
                -- Fremdschlüssel
                CONSTRAINT fk_patients_owner FOREIGN KEY (owner_id) 
                    REFERENCES owners(id) ON DELETE CASCADE,
                
                -- Indizes
                INDEX idx_owner (owner_id),
                INDEX idx_name (name),
                INDEX idx_species (species)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 5. Termine (appointments) - Existierende Tabelle prüfen und erweitern
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS appointments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                appointment_date DATETIME NOT NULL,
                notes TEXT DEFAULT NULL,
                sync_status ENUM('local','synced') DEFAULT 'local',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Fremdschlüssel
                CONSTRAINT fk_appointments_patient FOREIGN KEY (patient_id) 
                    REFERENCES patients(id) ON DELETE CASCADE,
                
                -- Indizes
                INDEX idx_patient (patient_id),
                INDEX idx_date (appointment_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 6. Rechnungen (invoices) - Existierende Tabelle prüfen und erweitern
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                appointment_id INT DEFAULT NULL,
                amount DECIMAL(10,2) NOT NULL,
                description TEXT DEFAULT NULL,
                status ENUM('open','paid','cancelled') DEFAULT 'open',
                pdf_path VARCHAR(255) DEFAULT NULL,
                sync_status ENUM('local','synced') DEFAULT 'local',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                paid_at DATETIME DEFAULT NULL,
                
                -- Fremdschlüssel
                CONSTRAINT fk_invoices_patient FOREIGN KEY (patient_id) 
                    REFERENCES patients(id) ON DELETE CASCADE,
                CONSTRAINT fk_invoices_appointment FOREIGN KEY (appointment_id) 
                    REFERENCES appointments(id) ON DELETE SET NULL,
                
                -- Indizes
                INDEX idx_patient (patient_id),
                INDEX idx_appointment (appointment_id),
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 7. Rechnungspositionen (invoice_items)
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS invoice_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_id INT NOT NULL,
                description VARCHAR(255) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                quantity INT DEFAULT 1,
                tax_rate DECIMAL(5,2) DEFAULT 0,
                
                -- Fremdschlüssel
                CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) 
                    REFERENCES invoices(id) ON DELETE CASCADE,
                
                -- Index
                INDEX idx_invoice (invoice_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 8. Notizen (notes)
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT DEFAULT NULL,
                owner_id INT DEFAULT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                sync_status ENUM('local','synced') DEFAULT 'local',
                
                -- Fremdschlüssel
                CONSTRAINT fk_notes_patient FOREIGN KEY (patient_id) 
                    REFERENCES patients(id) ON DELETE CASCADE,
                CONSTRAINT fk_notes_owner FOREIGN KEY (owner_id) 
                    REFERENCES owners(id) ON DELETE CASCADE,
                
                -- Indizes
                INDEX idx_patient (patient_id),
                INDEX idx_owner (owner_id),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 9. Settings - Legacy-Kompatibilität
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT DEFAULT NULL,
                sync_status ENUM('local','synced') DEFAULT 'local',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Index
                INDEX idx_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 10. Admin Roles (für zukünftige Erweiterungen)
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                description VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                -- Index
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 11. Admin User Roles (Many-to-Many)
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_user_roles (
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                PRIMARY KEY (user_id, role_id),
                
                -- Fremdschlüssel
                CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) 
                    REFERENCES admin_users(id) ON DELETE CASCADE,
                CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) 
                    REFERENCES admin_roles(id) ON DELETE CASCADE,
                
                -- Indizes
                INDEX idx_user (user_id),
                INDEX idx_role (role_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 12. Audit Log (für Sicherheit und Compliance)
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS audit_log (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                admin_user_id INT DEFAULT NULL,
                action VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                -- Fremdschlüssel
                CONSTRAINT fk_audit_user FOREIGN KEY (admin_user_id) 
                    REFERENCES admin_users(id) ON DELETE SET NULL,
                
                -- Indizes
                INDEX idx_user (admin_user_id),
                INDEX idx_action (action),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 13. Sessions (für Session-Management)
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(128) NOT NULL PRIMARY KEY,
                user_id INT DEFAULT NULL,
                admin_user_id INT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                payload TEXT NOT NULL,
                last_activity INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                -- Fremdschlüssel
                CONSTRAINT fk_sessions_admin FOREIGN KEY (admin_user_id) 
                    REFERENCES admin_users(id) ON DELETE CASCADE,
                
                -- Indizes
                INDEX idx_admin (admin_user_id),
                INDEX idx_last_activity (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 14. Admin Permissions (für granulare Rechteverwaltung)
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                permission_key VARCHAR(100) NOT NULL UNIQUE,
                description VARCHAR(255) DEFAULT NULL,
                category VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                -- Indizes
                INDEX idx_key (permission_key),
                INDEX idx_category (category)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // 15. Admin Role Permissions (Many-to-Many)
        // ============================================================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_role_permissions (
                role_id INT NOT NULL,
                permission_id INT NOT NULL,
                granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                PRIMARY KEY (role_id, permission_id),
                
                -- Fremdschlüssel
                CONSTRAINT fk_role_perms_role FOREIGN KEY (role_id) 
                    REFERENCES admin_roles(id) ON DELETE CASCADE,
                CONSTRAINT fk_role_perms_perm FOREIGN KEY (permission_id) 
                    REFERENCES admin_permissions(id) ON DELETE CASCADE,
                
                -- Indizes
                INDEX idx_role (role_id),
                INDEX idx_permission (permission_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================================================
        // Basis-Rollen erstellen (wenn noch nicht vorhanden)
        // ============================================================================
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_roles WHERE name = 'Super Admin'");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("
                INSERT INTO admin_roles (name, description) VALUES
                ('Super Admin', 'Vollzugriff auf alle Funktionen'),
                ('Admin', 'Administrativer Zugriff'),
                ('Manager', 'Verwaltungszugriff'),
                ('User', 'Basis-Benutzerzugriff')
            ");
        }

        // ============================================================================
        // Spalten hinzufügen, wenn sie fehlen (für existierende Installationen)
        // ============================================================================
        
        // Prüfe und füge is_super_admin zu admin_users hinzu
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'admin_users' 
            AND COLUMN_NAME = 'is_super_admin'
        ");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("
                ALTER TABLE admin_users 
                ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0 AFTER is_active
            ");
        }

        // Prüfe und füge name zu admin_users hinzu (Legacy-Kompatibilität)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'admin_users' 
            AND COLUMN_NAME = 'name'
        ");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("
                ALTER TABLE admin_users 
                ADD COLUMN name VARCHAR(190) NULL AFTER display_name
            ");
        }

        // Transaktion committen
        $pdo->commit();
        
    } catch (Exception $e) {
        // Bei Fehler: Rollback
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        throw new Exception('Migration fehlgeschlagen: ' . $e->getMessage());
    }
}

// Wenn direkt aufgerufen (nicht über Installer)
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    require_once dirname(dirname(__DIR__)) . '/includes/config.php';
    require_once dirname(dirname(__DIR__)) . '/includes/db.php';
    
    try {
        runInstallerCoreMigration($pdo);
        echo "✅ Migration erfolgreich ausgeführt!\n";
    } catch (Exception $e) {
        echo "❌ Fehler: " . $e->getMessage() . "\n";
        exit(1);
    }
}