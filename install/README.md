# Installation Guide - Tierphysio Praxis PWA

## Voraussetzungen

### System-Anforderungen
- **PHP**: Version 8.2 oder höher
- **MySQL/MariaDB**: Version 5.7+ / 10.3+
- **Webserver**: Apache 2.4+ oder Nginx

### PHP-Erweiterungen
**Erforderlich:**
- `pdo_mysql` - MySQL Datenbankverbindung
- `mbstring` - Multibyte String Support
- `json` - JSON Verarbeitung
- `openssl` - Verschlüsselung
- `curl` - HTTP Requests
- `fileinfo` - Datei-Uploads

**Optional:**
- `gd` - Bildverarbeitung
- `intl` - Internationalisierung

### Verzeichnis-Berechtigungen
Folgende Verzeichnisse müssen beschreibbar sein (775):
- `/includes/` - Für config.php
- `/install/` - Für installed.lock
- `/logs/` - Für Log-Dateien

## Installation

### Schritt 1: Dateien hochladen
Laden Sie alle Dateien auf Ihren Webserver hoch.

### Schritt 2: Datenbank erstellen
```sql
CREATE DATABASE tierphysio_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tierphysio_user'@'localhost' IDENTIFIED BY 'sicheres_passwort';
GRANT ALL PRIVILEGES ON tierphysio_db.* TO 'tierphysio_user'@'localhost';
FLUSH PRIVILEGES;
```

### Schritt 3: Installer aufrufen
Öffnen Sie in Ihrem Browser:
```
https://ihre-domain.de/install/installer.php
```

### Schritt 4: Installer-Wizard durchgehen

1. **Willkommen**: Übersicht über die Installation
2. **Systemprüfung**: Automatische Prüfung aller Anforderungen
3. **Datenbank**: Eingabe der MySQL-Zugangsdaten
4. **Konfiguration**: Automatisches Erstellen der config.php
5. **Schema**: Datenbanktabellen werden erstellt
6. **Einstellungen**: Praxis-Informationen eingeben
7. **Admin**: Ersten Administrator-Account erstellen
8. **Fertig**: Installation abgeschlossen

## Nach der Installation

### Wichtige Sicherheitshinweise

1. **Installer-Verzeichnis schützen**:
   - Der Installer ist automatisch gesperrt durch `installed.lock`
   - Optional: Löschen Sie `/install/` nach erfolgreicher Installation

2. **Dateiberechtigungen anpassen**:
   ```bash
   chmod 644 includes/config.php
   chmod 755 includes/
   ```

3. **SSL/HTTPS aktivieren**:
   - Stellen Sie sicher, dass Ihre Website über HTTPS erreichbar ist

### Erste Anmeldung
Nach erfolgreicher Installation:
1. Gehen Sie zu: `https://ihre-domain.de/login.php`
2. Melden Sie sich mit dem erstellten Admin-Account an

### Wartung

#### Neuinstallation
Falls eine Neuinstallation nötig ist:
1. Löschen Sie `/install/installed.lock`
2. Sichern Sie ggf. `/includes/config.php`
3. Rufen Sie den Installer erneut auf

#### Datenbank-Backup
Regelmäßige Backups empfohlen:
```bash
mysqldump -u tierphysio_user -p tierphysio_db > backup_$(date +%Y%m%d).sql
```

## Fehlerbehebung

### Häufige Probleme

**"Anforderungen nicht erfüllt"**
- Prüfen Sie PHP-Version: `php -v`
- Prüfen Sie PHP-Module: `php -m`
- Kontaktieren Sie ggf. Ihren Hosting-Provider

**"Datenbankverbindung fehlgeschlagen"**
- Prüfen Sie die Zugangsdaten
- Stellen Sie sicher, dass die Datenbank existiert
- Prüfen Sie die MySQL-Berechtigungen

**"Fehler beim Schreiben der Konfigurationsdatei"**
- Prüfen Sie Verzeichnis-Berechtigungen
- `chmod 775 includes/` ausführen

**"System im Wartungsmodus"**
- Config.php fehlt aber System ist installiert
- Löschen Sie `installed.lock` für Neuinstallation
- Oder stellen Sie config.php aus Backup wieder her

## Support

Bei Problemen wenden Sie sich an:
- Technischer Support: admin@ihre-domain.de
- Dokumentation: /docs/

## Lizenz

Copyright © 2024 Tierphysio Praxis PWA
Alle Rechte vorbehalten.