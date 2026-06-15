# Arbeitszeiterfassung

Webbasiertes Tool zur Erfassung von Arbeitszeiten, Urlaub und Krankheit. PHP 8.1+ / MySQL – MariaDB, kein Composer erforderlich.

## Voraussetzungen

- PHP 8.1 oder neuer (mit PDO, pdo_mysql)
- MySQL 5.7+ oder MariaDB 10.3+
- Apache mit `mod_rewrite` (oder Nginx mit entsprechender Konfiguration)

## Installation

### 1. Repository auf den Server klonen

Per SSH auf den Server verbinden und das Repository klonen:

```bash
cd /var/www
git clone https://github.com/werbestudio-mack/working-hours.git
```

Den Document Root des Webservers anschließend auf das Verzeichnis `public/` zeigen lassen, z.B.:

```
/var/www/working-hours/public
```

Alternativ direkt in ein bestehendes (leeres) Verzeichnis klonen:

```bash
cd /var/www/mein-verzeichnis
git clone https://github.com/werbestudio-mack/working-hours.git .
```

Falls das Verzeichnis bereits existiert und nicht leer ist:

```bash
cd /var/www/mein-verzeichnis
git init
git remote add origin https://github.com/werbestudio-mack/working-hours.git
git pull origin master
```

Dann liegt `public/` direkt unter `/var/www/mein-verzeichnis/public`.

**Alternativ ohne Git** – Dateien per FTP/SFTP hochladen und den Ordner entpacken.

### 2. Leere Datenbank anlegen

In MySQL / MariaDB eine leere Datenbank erstellen (z.B. über phpMyAdmin oder die Kommandozeile):

```sql
CREATE DATABASE working_hours CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Setup-Assistent aufrufen

Im Browser `http://deine-domain.de/setup` aufrufen. Der Assistent führt in drei Schritten durch die Einrichtung:

1. **Datenbank** – Zugangsdaten eingeben; Verbindung wird getestet und alle Tabellen werden automatisch angelegt
2. **Administrator** – Ersten Admin-Account anlegen
3. **Fertig** – Bestätigung und Hinweis zum Löschen der Setup-Datei

**Wichtig:** `public/setup.php` nach der Einrichtung löschen!

### 4. Anmelden

`http://deine-domain.de/login` aufrufen und mit den soeben erstellten Zugangsdaten anmelden.

---

## Nginx-Konfiguration (Beispiel)

```nginx
server {
    root /var/www/arbeitszeiterfassung/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Funktionsübersicht

### Administrator

- Benutzer anlegen, bearbeiten, deaktivieren, löschen
- Pro Benutzer: Name, E-Mail, Wochenstunden, Urlaubstage/Jahr, Admin-Rechte
- Urlaubsanpassungen pro Jahr (Resturlaub Vorjahr, Sondertage)
- Feiertage verwalten (ganzer oder halber Tag)

### Benutzer

- Zeiteinträge erfassen:
  - **Arbeit**: Datum + Startzeit / Endzeit
  - **Freizeitausgleich**: Datum + Startzeit / Endzeit
  - **Urlaub**: Datumsbereich, optional halber Tag (nur Einzeltag)
  - **Krankheit**: Datumsbereich
- Dashboard mit Stunden-Saldo und Urlaubskonto
- Passwort ändern

---

## Berechnungslogik

### Stunden (Soll / Ist / Saldo)

- **Soll** = Anzahl Werktage im Zeitraum × (Wochenstunden ÷ 5)
  - Volle Feiertage: 0 h Soll
  - Halbe Feiertage: 50 % Soll
- **Ist** = Arbeitsstunden + Freizeitausgleich-Stunden + Urlaubs-Ist + Krank-Ist
  - Urlaubs-Ist und Krank-Ist füllen das Tages-Soll für die betroffenen Werktage
- **Saldo** = Ist − Soll (positiv = Überstunden, negativ = Minusstunden)

### Urlaubskonto

- Gesamtanspruch = Jahresanspruch + Resturlaub Vorjahr + Sondertage
- Genommene Tage werden tagesweise gezählt (keine Wochenenden, keine vollen Feiertage)
- Halber Feiertag während Urlaub = 0,5 Urlaubstage

---

## Sicherheitshinweise

- Alle SQL-Abfragen werden als Prepared Statements ausgeführt (kein SQL Injection)
- CSRF-Token in allen Formularen
- Passwörter werden mit bcrypt gehasht
- Adminrechte können nicht selbst entzogen werden
- Nach dem Anlegen eines neuen Benutzers wird eine Passwortänderung erzwungen (konfigurierbar)
