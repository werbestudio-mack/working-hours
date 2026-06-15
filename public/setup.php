<?php
/**
 * Setup-Assistent – Datenbankeinrichtung und ersten Administrator anlegen.
 * Diese Datei nach der Einrichtung löschen!
 */
session_start();

define('CONFIG_PATH', dirname(__DIR__) . '/src/config.php');
define('SCHEMA_PATH', dirname(__DIR__) . '/schema.sql');

// ── Bereits eingerichtet? ────────────────────────────────────────────
if (file_exists(CONFIG_PATH)) {
    @include CONFIG_PATH;
    if (defined('DB_HOST')) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $cnt = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ($cnt > 0) {
                die(page('Bereits eingerichtet',
                    '<div class="alert alert-danger">'
                    . '<strong>Zugriff verweigert.</strong> Die Anwendung ist bereits eingerichtet.<br>'
                    . 'Bitte diese Datei löschen: <code>public/setup.php</code>'
                    . '</div>'
                ));
            }
        } catch (PDOException) {
            // config existiert, aber DB nicht erreichbar → Setup erlauben
        }
    }
}

// ── Schritt bestimmen ───────────────────────────────────────────────
$step  = (int) ($_SESSION['setup_step'] ?? 1);
$error = '';

// ── POST verarbeiten ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === 1) {
        $dbHost     = trim($_POST['db_host']   ?? 'localhost');
        $dbName     = trim($_POST['db_name']   ?? '');
        $dbUser     = trim($_POST['db_user']   ?? '');
        $dbPass     = $_POST['db_pass']        ?? '';
        $timezone   = $_POST['timezone']       ?? 'Europe/Berlin';
        $baseUrl    = rtrim(trim($_POST['base_url'] ?? ''), '/');
        $mailFrom   = trim($_POST['mail_from'] ?? '');

        if (!$dbName || !$dbUser) {
            $error = 'Datenbankname und Benutzername sind Pflichtfelder.';
        } else {
            try {
                $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);

                // Tabellen anlegen
                runSchema($pdo);

                // config.php schreiben
                writeConfig($dbHost, $dbName, $dbUser, $dbPass, $timezone, $baseUrl, $mailFrom);

                $_SESSION['setup_db']   = compact('dbHost', 'dbName', 'dbUser', 'dbPass');
                $_SESSION['setup_step'] = 2;
                header('Location: setup.php');
                exit;

            } catch (PDOException $e) {
                $error = 'Datenbankfehler: ' . htmlspecialchars($e->getMessage());
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }
    }

    elseif ($step === 2) {
        $db = $_SESSION['setup_db'] ?? null;
        if (!$db) { $_SESSION['setup_step'] = 1; header('Location: setup.php'); exit; }

        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $pass    = $_POST['password']     ?? '';
        $confirm = $_POST['confirm']      ?? '';

        if (!$name || !$email || !$pass) {
            $error = 'Alle Felder sind Pflichtfelder.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ungültige E-Mail-Adresse.';
        } elseif (strlen($pass) < 8) {
            $error = 'Passwort muss mindestens 8 Zeichen lang sein.';
        } elseif ($pass !== $confirm) {
            $error = 'Passwörter stimmen nicht überein.';
        } else {
            try {
                $pdo = dbConnect($db);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password_hash, is_admin, weekly_hours, vacation_days, must_change_password)
                     VALUES (?, ?, ?, 1, 40.00, 30.0, 0)'
                );
                $stmt->execute([$name, $email, password_hash($pass, PASSWORD_BCRYPT)]);

                $_SESSION['setup_step'] = 3;
                header('Location: setup.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Fehler beim Anlegen des Benutzers: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// ── HTML ausgeben ───────────────────────────────────────────────────
$steps = ['Datenbank', 'Administrator', 'Fertig'];

if ($step === 3) {
    echo page('Einrichtung abgeschlossen', '
        <div class="alert alert-success">
            <h5 class="alert-heading">Einrichtung erfolgreich!</h5>
            <p>Die Datenbank wurde eingerichtet und der Administrator-Account wurde angelegt.</p>
        </div>
        <div class="alert alert-warning">
            <strong>Wichtig:</strong> Bitte jetzt die Datei <code>public/setup.php</code> löschen,
            da sie sonst ein Sicherheitsrisiko darstellt.
        </div>
        <a href="' . htmlspecialchars(str_replace('/setup.php', '/login', $_SERVER['PHP_SELF'])) . '"
           class="btn btn-primary">Zur Anmeldung</a>
    ', $steps, $step);
    exit;
}

if ($step === 1) {
    // Werte aus bestehender config.php vorbelegen (falls vorhanden)
    $pre = ['db_host' => 'localhost', 'db_name' => 'working_hours',
            'db_user' => '', 'db_pass' => '', 'timezone' => 'Europe/Berlin',
            'base_url' => '', 'mail_from' => ''];
    if (file_exists(CONFIG_PATH)) {
        @include CONFIG_PATH;
        if (defined('DB_HOST')) {
            $pre = ['db_host' => DB_HOST, 'db_name' => DB_NAME, 'db_user' => DB_USER,
                    'db_pass' => DB_PASS, 'timezone' => defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Europe/Berlin',
                    'base_url' => defined('BASE_URL') ? BASE_URL : '',
                    'mail_from' => defined('MAIL_FROM') ? MAIL_FROM : ''];
        }
    }
    // Bei Fehler nach POST: eingegebene Werte behalten
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pre = [
            'db_host'   => $_POST['db_host']   ?? $pre['db_host'],
            'db_name'   => $_POST['db_name']   ?? $pre['db_name'],
            'db_user'   => $_POST['db_user']   ?? $pre['db_user'],
            'db_pass'   => $_POST['db_pass']   ?? $pre['db_pass'],
            'timezone'  => $_POST['timezone']  ?? $pre['timezone'],
            'base_url'  => $_POST['base_url']  ?? $pre['base_url'],
            'mail_from' => $_POST['mail_from'] ?? $pre['mail_from'],
        ];
    }

    $timezones = [
        'Europe/Berlin'   => 'Europe/Berlin (Deutschland)',
        'Europe/Vienna'   => 'Europe/Vienna (Österreich)',
        'Europe/Zurich'   => 'Europe/Zurich (Schweiz)',
        'Europe/London'   => 'Europe/London',
        'UTC'             => 'UTC',
    ];

    $body = '
    <p class="text-muted mb-4">Schritt 1: Geben Sie die Datenbankverbindungsdaten ein.
       Die Datenbank muss bereits existieren; die Tabellen werden automatisch angelegt.</p>'
    . ($error ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Datenbank-Host</label>
            <input type="text" name="db_host" class="form-control"
                   value="' . hv($pre['db_host']) . '" required placeholder="localhost">
        </div>
        <div class="mb-3">
            <label class="form-label">Datenbankname <span class="text-danger">*</span></label>
            <input type="text" name="db_name" class="form-control"
                   value="' . hv($pre['db_name']) . '" required placeholder="working_hours">
        </div>
        <div class="mb-3">
            <label class="form-label">Datenbankbenutzer <span class="text-danger">*</span></label>
            <input type="text" name="db_user" class="form-control"
                   value="' . hv($pre['db_user']) . '" required autocomplete="username">
        </div>
        <div class="mb-3">
            <label class="form-label">Datenbankpasswort</label>
            <input type="password" name="db_pass" class="form-control"
                   autocomplete="current-password">
            <div class="form-text">Leer lassen, falls kein Passwort gesetzt ist.</div>
        </div>
        <hr>
        <div class="mb-3">
            <label class="form-label">Zeitzone</label>
            <select name="timezone" class="form-select">';
    foreach ($timezones as $tz => $label) {
        $sel  = ($pre['timezone'] === $tz) ? ' selected' : '';
        $body .= '<option value="' . hv($tz) . '"' . $sel . '>' . hv($label) . '</option>';
    }
    $body .= '
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Basis-URL</label>
            <input type="text" name="base_url" class="form-control"
                   value="' . hv($pre['base_url']) . '" placeholder="Leer = Web-Root">
            <div class="form-text">
                Nur ausfüllen, wenn die App in einem Unterverzeichnis läuft,
                z.B. <code>/arbeit</code>. Sonst leer lassen.
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Absender-E-Mail (für Passwort-Reset)</label>
            <input type="email" name="mail_from" class="form-control"
                   value="' . hv($pre['mail_from']) . '" placeholder="noreply@ihre-domain.de">
            <div class="form-text">
                Von dieser Adresse werden Reset-E-Mails versendet. Kann leer gelassen und später
                in <code>src/config.php</code> als <code>MAIL_FROM</code> eingetragen werden.
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
            Verbindung testen &amp; Tabellen anlegen →
        </button>
    </form>';

    echo page('Einrichtung – Schritt 1', $body, $steps, $step);
    exit;
}

if ($step === 2) {
    $body = '
    <p class="text-muted mb-4">Schritt 2: Legen Sie den ersten Administrator-Account an.</p>'
    . ($error ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required
                   value="' . hv($_POST['name'] ?? '') . '">
        </div>
        <div class="mb-3">
            <label class="form-label">E-Mail-Adresse <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required
                   value="' . hv($_POST['email'] ?? '') . '" autocomplete="email">
        </div>
        <div class="mb-3">
            <label class="form-label">Passwort <span class="text-danger">*</span>
                <small class="text-muted">(min. 8 Zeichen)</small></label>
            <input type="password" name="password" class="form-control" required
                   minlength="8" autocomplete="new-password">
        </div>
        <div class="mb-4">
            <label class="form-label">Passwort bestätigen <span class="text-danger">*</span></label>
            <input type="password" name="confirm" class="form-control" required
                   autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Administrator anlegen →</button>
    </form>';

    echo page('Einrichtung – Schritt 2', $body, $steps, $step);
    exit;
}

// Schritt unbekannt → zurück zu 1
$_SESSION['setup_step'] = 1;
header('Location: setup.php');
exit;


// ── Hilfsfunktionen ─────────────────────────────────────────────────

function hv(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function dbConnect(array $db): PDO
{
    return new PDO(
        "mysql:host={$db['dbHost']};dbname={$db['dbName']};charset=utf8mb4",
        $db['dbUser'], $db['dbPass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

function runSchema(PDO $pdo): void
{
    $sql = file_get_contents(SCHEMA_PATH);
    if ($sql === false) {
        throw new RuntimeException('schema.sql konnte nicht gelesen werden.');
    }

    // Nach Semikolons aufteilen, dann Statement-weise filtern.
    // CREATE DATABASE und USE werden als ganzes Statement herausgefiltert,
    // da CREATE DATABASE mehrzeilig ist und zeilenweises Filtern Reste hinterlässt.
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        static function (string $s): bool {
            if ($s === '') return false;
            // Kommentarzeilen (--) am Anfang entfernen, dann Typ prüfen
            $stripped = ltrim(preg_replace('/^[ \t]*--[^\n]*\n?/m', '', $s));
            return !preg_match('/^(CREATE\s+DATABASE|USE\s+\w+)/i', $stripped);
        }
    );

    foreach ($statements as $stmt) {
        $pdo->exec($stmt);
    }
}

function writeConfig(
    string $host, string $name, string $user, string $pass,
    string $timezone, string $baseUrl, string $mailFrom = ''
): void {
    $e = static fn(string $s): string => str_replace(["\\", "'"], ["\\\\", "\\'"], $s);

    $content = "<?php\n"
        . "define('DB_HOST',     '" . $e($host)     . "');\n"
        . "define('DB_NAME',     '" . $e($name)     . "');\n"
        . "define('DB_USER',     '" . $e($user)     . "');\n"
        . "define('DB_PASS',     '" . $e($pass)     . "');\n"
        . "define('APP_NAME',    'Arbeitszeiterfassung');\n"
        . "define('APP_TIMEZONE','" . $e($timezone) . "');\n"
        . "define('BASE_PATH',   dirname(__DIR__));\n"
        . "define('BASE_URL',    '" . $e($baseUrl)  . "');\n"
        . ($mailFrom !== '' ? "define('MAIL_FROM',   '" . $e($mailFrom) . "');\n" : '');

    if (file_put_contents(CONFIG_PATH, $content) === false) {
        throw new RuntimeException(
            'config.php konnte nicht geschrieben werden. '
            . 'Bitte Schreibrechte auf <code>src/</code> prüfen.'
        );
    }
}

function page(string $title, string $body, array $steps = [], int $currentStep = 0): string
{
    $progress = '';
    if ($steps) {
        $items = '';
        foreach ($steps as $i => $label) {
            $n      = $i + 1;
            $active = $n === $currentStep ? ' fw-bold text-primary' : '';
            $done   = $n < $currentStep  ? ' text-success'          : '';
            $icon   = $n < $currentStep  ? '✓ '                     : "$n. ";
            $items .= "<span class='me-4{$active}{$done}'>{$icon}{$label}</span>";
        }
        $progress = "<div class='mb-4 pb-3 border-bottom text-sm'>{$items}</div>";
    }

    return '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Einrichtung – Arbeitszeiterfassung</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container" style="max-width:560px;padding-top:48px;padding-bottom:48px">
    <h2 class="mb-1">Arbeitszeiterfassung</h2>
    <p class="text-muted mb-4">Ersteinrichtung</p>
    <div class="card shadow-sm">
        <div class="card-body p-4">
            ' . $progress . '
            <h5 class="mb-3">' . hv($title) . '</h5>
            ' . $body . '
        </div>
    </div>
    <p class="text-muted text-center mt-3" style="font-size:.8rem">
        Bitte <code>public/setup.php</code> nach der Einrichtung löschen.
    </p>
</div>
</body>
</html>';
}
