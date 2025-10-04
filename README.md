# 🐾 Tierphysio – Webbasierte Praxisverwaltungs-App für Tierphysiotherapeut:innen

**Tierphysio** ist eine moderne, webbasierte Anwendung (Progressive Web App, PWA) zur effizienten Verwaltung von **Patienten, Terminen, Behandlungen und Einnahmen** in der Tierphysiotherapie.  
Ursprünglich entwickelt für eine mobile Tierphysiotherapeutin, wurde das System so erweitert, dass es für **jede Praxis oder selbständige Tierphysiotherapeut:in** individuell einsetzbar ist.

---

## 🌍 Projektübersicht

Die Anwendung dient als leichtgewichtiges, intuitives **Praxisverwaltungssystem**, das vollständig im Browser läuft.  
Alle Funktionen sind **mobilfreundlich**, offline nutzbar und benötigen **keine Installation** – sie kann direkt auf PC, Tablet oder Smartphone verwendet werden.

---

## ✨ Hauptfunktionen

### 🗓️ Terminverwaltung
- Tagesaktuelle Übersicht über alle geplanten Behandlungen  
- Erfassung von Datum, Uhrzeit, Tiername und Tierart  
- Automatische Kennzeichnung des heutigen Termins  
- Optional: Synchronisation mit Kalenderdiensten (Google Calendar, iCal)

### 🐾 Patientenverwaltung
- Anlegen, Bearbeiten und Löschen von Patienten  
- Speicherung von:
  - Tiername, Tierart, Rasse, Geburtsdatum  
  - Besitzername und Kontaktdaten  
  - Krankengeschichte / Anmerkungen  
- Verknüpfung mit Behandlungseinträgen  

### 💬 Behandlungsnotizen
- Erfasse nach jeder Sitzung die Behandlung, Besonderheiten oder Fortschritte  
- Freitextfeld für Notizen oder empfohlene Nachbehandlungen  
- Automatische Verknüpfung mit Patient und Datum  

### 💰 Einnahmenverwaltung
- Automatische Berechnung basierend auf dem Standard-Stundensatz (75 € / Stunde)  
- Summierung nach:
  - Aktueller Monat  
  - Aktuelles Jahr  
  - Gesamteinnahmen  
- Anpassbarer Stundensatz  

### 🧾 Zusatzfunktionen *(optional planbar)*
- Export als **Excel- oder PDF-Bericht**  
- Cloud-Synchronisation via Google Drive oder Firebase  
- Login-System für mehrere Benutzer  
- Backup- und Restore-Funktion  

---

## ⚙️ Technische Details

| Komponente | Beschreibung |
|-------------|---------------|
| **Frontend** | HTML5, CSS3 (Bootstrap 5), Vanilla JavaScript |
| **Backend (optional)** | Google Drive / Firebase oder PHP + MySQL |
| **Speicherung (Standard)** | LocalStorage im Browser (offlinefähig) |
| **App-Typ** | Progressive Web App (PWA) |
| **Design** | Dunkles, modernes UI mit klarer Struktur |
| **Kompatibilität** | 💻 PC   📱 Tablet   📞 Smartphone (responsive) |
| **Sprache** | Deutsch 🇩🇪 (auf Englisch 🇬🇧 erweiterbar) |

---

## 🧩 Projektstruktur

```
tierphysio/
├── index.html            # Hauptseite (Dashboard)
├── css/
│   └── style.css         # Globales Design
├── js/
│   └── main.js           # Hauptlogik (Datenverwaltung)
├── assets/
│   └── icons/            # App-Icons, Logos, Bilder
├── includes/
│   └── db.php            # (optional) PHP-Backend / DB-Anbindung
└── README.md             # Projektbeschreibung
```

---

## 🚀 Installation / Nutzung

1. Repository herunterladen oder klonen  
   ```bash
   git clone https://github.com/<dein-nutzername>/tierphysio.git
   ```

2. Öffne die Datei **index.html** im Browser  
   → Die Anwendung startet sofort, kein Server erforderlich.

3. Daten werden automatisch lokal im Browser gespeichert (`localStorage`).

4. Optional:  
   - Stundensatz, Design und Datenfelder in `main.js` anpassen  
   - Cloud-Anbindung (Google Drive / Firebase) aktivieren  

---

## 🧠 Roadmap / Geplante Erweiterungen
- 🔐 Benutzerverwaltung mit Login / Rollen  
- ☁️ Synchronisation mit Google Sheets / Drive API  
- 📅 Kalenderansicht mit Drag & Drop  
- 📊 Statistik-Dashboard (Einnahmen pro Tierart oder Monat)  
- 💾 Exportfunktionen (CSV, Excel, PDF)  
- 💼 Rechnungs- und Mahnwesen-Modul  

---

## 🔒 Datenschutz & Sicherheit
- Lokale Speicherung der Patientendaten – keine externen Server erforderlich  
- Optionale Cloud-Synchronisierung nur nach Einwilligung  
- DSGVO-konforme Nutzung möglich, da die Daten vollständig unter eigener Kontrolle bleiben  

---

## 🐕 Zielgruppe
- Selbständige Tierphysiotherapeut:innen  
- Tierheilpraktiker:innen  
- Mobile Tiertherapeut:innen  
- Kleine Tierphysio-Praxen ohne teure Verwaltungssoftware  

---

## 💡 Vorteile
- ✅ Keine Installation – läuft direkt im Browser  
- 📱 Plattformübergreifend (PC, Tablet, Smartphone)  
- 🧭 Intuitive Bedienung ohne Einarbeitung  
- 🔋 Offline-fähig  
- ⚙️ Anpassbar an individuelle Bedürfnisse  
- 💸 Keine laufenden Lizenzkosten  

---

## 🧰 Lizenz
Dieses Projekt steht unter der **MIT-Lizenz**.  
Du darfst es frei verwenden, anpassen und erweitern – auch für kommerzielle Zwecke.

---

## 🙌 Credits
Ursprünglich entwickelt als interne Anwendung für eine mobile Tierphysiotherapeutin.  
Später als Open-Source-Projekt veröffentlicht, um anderen Therapeut:innen eine einfach bedienbare und flexible Lösung für ihren Praxisalltag anzubieten.  

---

## ❤️ Support
Fragen, Ideen oder Verbesserungsvorschläge?  
👉 Erstelle ein **Issue** auf GitHub oder kontaktiere den Entwickler direkt.

---
