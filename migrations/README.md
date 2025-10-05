# Database Migrations

Diese Migrations müssen in der angegebenen Reihenfolge ausgeführt werden.

## Ausführen der Migrations

```bash
# Alle Migrations auf einmal (in richtiger Reihenfolge)
mysql -u root -p tierphysio < migrations/001_create_admin_tables.sql
mysql -u root -p tierphysio < migrations/002_seed_admin_data.sql
```

Oder über PHP Script:

```bash
php migrations/run.php
```

## Migrations-Übersicht

### 001_create_admin_tables.sql
Erstellt alle Tabellen für das Admin RBAC-System:
- `admin_users` - Admin-Benutzer
- `admin_roles` - Rollen (z.B. Super Admin, Editor)
- `admin_permissions` - Rechte/Permissions
- `admin_user_roles` - Zuordnung User <-> Rolle
- `admin_role_permissions` - Zuordnung Rolle <-> Permission
- `audit_log` - Audit-Protokoll für alle Aktionen
- `app_settings` - Anwendungseinstellungen (Admin-verwaltbar)

### 002_seed_admin_data.sql
Befüllt das System mit:
- Standard-Rollen (Super Admin, Admin, Editor, Viewer)
- Standard-Permissions (kategorisiert nach Modulen)
- Zuordnung von Permissions zu Rollen
- Standard Super-Admin User:
  - **Email**: admin@tierphysio.local
  - **Passwort**: Admin123!
  - **⚠️ WICHTIG**: Passwort nach erstem Login ändern!

## Standard-Admin Login

Nach der Migration können Sie sich mit folgenden Daten einloggen:

- **URL**: `/admin/login.php`
- **Email**: `admin@tierphysio.local`
- **Passwort**: `Admin123!`

**🔒 Sicherheitshinweis**: Ändern Sie das Passwort sofort nach dem ersten Login!

## Rollen-Übersicht

### Super Administrator
- Voller Zugriff auf alle Funktionen
- Kann System-Einstellungen ändern
- Kann andere Admins verwalten
- Kann Rollen und Rechte verwalten

### Administrator
- Zugriff auf alle Daten-Verwaltungsfunktionen
- Kann Benutzer verwalten (außer löschen)
- Keine System-kritischen Funktionen

### Bearbeiter (Editor)
- Kann Patienten, Besitzer, Termine, Rechnungen und Notizen verwalten
- Kann Daten erstellen und bearbeiten
- Kann Rechnungen versenden

### Betrachter (Viewer)
- Nur Lese-Zugriff auf alle Daten
- Kann nichts erstellen, bearbeiten oder löschen
- Ideal für Praktikanten oder Rezeption

## Permissions-Kategorien

- **Dashboard** - Dashboard-Ansicht und Statistiken
- **Patienten** - CRUD für Patienten
- **Besitzer** - CRUD für Besitzer
- **Termine** - CRUD für Termine
- **Rechnungen** - CRUD + Versand für Rechnungen
- **Notizen** - CRUD für Notizen
- **Benutzerverwaltung** - Admin-User verwalten
- **Rollen & Rechte** - RBAC verwalten
- **Einstellungen** - Anwendungseinstellungen
- **Protokolle** - Audit-Log einsehen
- **System** - Wartungsmodus, Cache, Backup
