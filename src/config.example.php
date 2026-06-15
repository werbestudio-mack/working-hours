<?php
// Kopiere diese Datei zu config.php und trage deine Werte ein.
define('DB_HOST',     'localhost');
define('DB_NAME',     'working_hours');
define('DB_USER',     'dein_datenbankbenutzer');
define('DB_PASS',     'dein_passwort');
define('APP_NAME',    'Arbeitszeiterfassung');
define('APP_TIMEZONE','Europe/Berlin');
define('BASE_PATH',   dirname(__DIR__));

// Leer lassen wenn die App in der Web-Root läuft.
// Beispiel für Unterverzeichnis: '/arbeit'
define('BASE_URL', '');
