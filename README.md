# Arbeitszeiterfassung

Webbasiertes Tool zur Erfassung von Arbeitszeiten, Urlaub und Krankheit. PHP 8.1+ / MySQL – MariaDB, kein Composer erforderlich.

## Voraussetzungen

- PHP 8.1 oder neuer (mit PDO, pdo_mysql)
- MySQL 5.7+ oder MariaDB 10.3+
- Apache mit `mod_rewrite` (oder Nginx mit entsprechender Konfiguration)

## Installation

### 1. Dateien hochladen

Den gesamten Projektordner auf den Server kopieren. Den Document Root des Webservers auf `public/` zeigen lassen.

### 2. Datenbank einrichten

```sql
-- In MySQL / MariaDB ausführen:
source /pfad/zur/schema.sql
```

Oder über phpMyAdmin: `schema.sql` importieren.

### 3. Konfiguration anpassen

`src/config.php` öffnen und die Datenbankzugangsdaten anpassen:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'working_hours');
define('DB_USER', 'dein_benutzer');
define('DB_PASS', 'dein_passwort');
define('APP_TIMEZONE', 'Europe/Berlin');

// Falls die App in einem Unterverzeichnis läuft, z.B.:
// define('BASE_URL', '/arbeit');
define('BASE_URL', '');
```

### 4. Administrator anlegen

Im Browser `http://deine-domain.de/setup` aufrufen und den ersten Administrator-Account erstellen.

**Wichtig:** `public/setup.php` nach der Einrichtung löschen!

### 5. Anmelden

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
