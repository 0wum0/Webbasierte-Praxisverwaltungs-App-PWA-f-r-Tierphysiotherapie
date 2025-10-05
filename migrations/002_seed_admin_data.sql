-- Migration: Seed Admin Data
-- Erstellt Standard-Rollen, Permissions und einen Super-Admin

-- Standard Rollen einfügen
INSERT INTO `admin_roles` (`name`, `display_name`, `description`, `is_system`) VALUES
('super_admin', 'Super Administrator', 'Voller Zugriff auf alle Funktionen inkl. Systemeinstellungen', 1),
('admin', 'Administrator', 'Zugriff auf alle Daten-Verwaltungsfunktionen', 1),
('editor', 'Bearbeiter', 'Kann Patienten, Termine und Rechnungen bearbeiten', 1),
('viewer', 'Betrachter', 'Kann Daten nur ansehen, nicht bearbeiten', 1)
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`);

-- Standard Permissions einfügen (kategorisiert)
INSERT INTO `admin_permissions` (`permission_key`, `display_name`, `category`) VALUES
-- Dashboard
('dashboard.view', 'Dashboard anzeigen', 'Dashboard'),
('dashboard.statistics', 'Statistiken einsehen', 'Dashboard'),

-- Patienten
('patients.view', 'Patienten anzeigen', 'Patienten'),
('patients.create', 'Patienten erstellen', 'Patienten'),
('patients.edit', 'Patienten bearbeiten', 'Patienten'),
('patients.delete', 'Patienten löschen', 'Patienten'),
('patients.export', 'Patienten exportieren', 'Patienten'),

-- Besitzer
('owners.view', 'Besitzer anzeigen', 'Besitzer'),
('owners.create', 'Besitzer erstellen', 'Besitzer'),
('owners.edit', 'Besitzer bearbeiten', 'Besitzer'),
('owners.delete', 'Besitzer löschen', 'Besitzer'),

-- Termine
('appointments.view', 'Termine anzeigen', 'Termine'),
('appointments.create', 'Termine erstellen', 'Termine'),
('appointments.edit', 'Termine bearbeiten', 'Termine'),
('appointments.delete', 'Termine löschen', 'Termine'),

-- Rechnungen
('invoices.view', 'Rechnungen anzeigen', 'Rechnungen'),
('invoices.create', 'Rechnungen erstellen', 'Rechnungen'),
('invoices.edit', 'Rechnungen bearbeiten', 'Rechnungen'),
('invoices.delete', 'Rechnungen löschen', 'Rechnungen'),
('invoices.send', 'Rechnungen versenden', 'Rechnungen'),

-- Notizen
('notes.view', 'Notizen anzeigen', 'Notizen'),
('notes.create', 'Notizen erstellen', 'Notizen'),
('notes.edit', 'Notizen bearbeiten', 'Notizen'),
('notes.delete', 'Notizen löschen', 'Notizen'),

-- Admin-Benutzer
('admin_users.view', 'Admin-Benutzer anzeigen', 'Benutzerverwaltung'),
('admin_users.create', 'Admin-Benutzer erstellen', 'Benutzerverwaltung'),
('admin_users.edit', 'Admin-Benutzer bearbeiten', 'Benutzerverwaltung'),
('admin_users.delete', 'Admin-Benutzer löschen', 'Benutzerverwaltung'),

-- Rollen & Rechte
('roles.view', 'Rollen anzeigen', 'Rollen & Rechte'),
('roles.create', 'Rollen erstellen', 'Rollen & Rechte'),
('roles.edit', 'Rollen bearbeiten', 'Rollen & Rechte'),
('roles.delete', 'Rollen löschen', 'Rollen & Rechte'),
('permissions.assign', 'Rechte zuweisen', 'Rollen & Rechte'),

-- Einstellungen
('settings.view', 'Einstellungen anzeigen', 'Einstellungen'),
('settings.edit', 'Einstellungen bearbeiten', 'Einstellungen'),
('settings.smtp', 'SMTP-Einstellungen bearbeiten', 'Einstellungen'),
('settings.invoice', 'Rechnungs-Einstellungen bearbeiten', 'Einstellungen'),

-- Audit Log
('audit.view', 'Audit-Log anzeigen', 'Protokolle'),
('audit.export', 'Audit-Log exportieren', 'Protokolle'),

-- System
('system.maintenance', 'Wartungsmodus aktivieren', 'System'),
('system.cache', 'Cache leeren', 'System'),
('system.backup', 'Backup erstellen', 'System')
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`);

-- Permissions zu Rollen zuordnen

-- Super Admin: Alle Rechte
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `admin_roles` r
CROSS JOIN `admin_permissions` p
WHERE r.name = 'super_admin'
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- Admin: Fast alle Rechte (ohne System-kritische)
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `admin_roles` r
CROSS JOIN `admin_permissions` p
WHERE r.name = 'admin'
  AND p.permission_key NOT IN ('system.maintenance', 'system.backup', 'admin_users.delete')
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- Editor: Daten-Verwaltung
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `admin_roles` r
CROSS JOIN `admin_permissions` p
WHERE r.name = 'editor'
  AND p.permission_key IN (
    'dashboard.view', 'dashboard.statistics',
    'patients.view', 'patients.create', 'patients.edit',
    'owners.view', 'owners.create', 'owners.edit',
    'appointments.view', 'appointments.create', 'appointments.edit',
    'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.send',
    'notes.view', 'notes.create', 'notes.edit'
  )
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- Viewer: Nur Lese-Rechte
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `admin_roles` r
CROSS JOIN `admin_permissions` p
WHERE r.name = 'viewer'
  AND p.permission_key IN (
    'dashboard.view', 'dashboard.statistics',
    'patients.view', 'owners.view', 'appointments.view',
    'invoices.view', 'notes.view'
  )
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- Standard Super-Admin erstellen
-- Passwort: Admin123! (bitte nach erstem Login ändern!)
-- Hash: $argon2id$v=19$m=65536,t=4,p=1$...
INSERT INTO `admin_users` (`email`, `password`, `name`, `is_super_admin`, `is_active`)
VALUES (
    'admin@tierphysio.local',
    '$argon2id$v=19$m=65536,t=4,p=1$eW5RYnFLQUpLakJCb0FEZg$Uy8kqJI1F8Y3xrPvWQqVvE0nKqKqMv7hbqVvP7Y3xrE',
    'System Administrator',
    1,
    1
)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Super-Admin Rolle zuweisen
INSERT INTO `admin_user_roles` (`user_id`, `role_id`)
SELECT u.id, r.id
FROM `admin_users` u
CROSS JOIN `admin_roles` r
WHERE u.email = 'admin@tierphysio.local'
  AND r.name = 'super_admin'
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`);
