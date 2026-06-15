<?php
/**
 * Ersteinrichtung: Admin-Benutzer anlegen.
 * Diese Datei nach der Einrichtung löschen!
 */
require_once dirname(__DIR__) . '/src/bootstrap.php';

$error   = '';
$success = '';

// Prüfen ob bereits Benutzer existieren
try {
    $count = Database::fetch('SELECT COUNT(*) AS cnt FROM users');
    if ($count && $count['cnt'] > 0) {
        die('<p style="font-family:sans-serif;color:red;padding:2em">
            Bereits eingerichtet. Bitte diese Datei löschen (<code>public/setup.php</code>).</p>');
    }
} catch (PDOException $e) {
    die('<p style="font-family:sans-serif;color:red;padding:2em">Datenbankfehler: '
        . htmlspecialchars($e->getMessage()) . '<br>Bitte zuerst <code>schema.sql</code> importieren.</p>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$name || !$email || !$password) {
        $error = 'Alle Felder sind Pflichtfelder.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ungültige E-Mail-Adresse.';
    } elseif (strlen($password) < 8) {
        $error = 'Passwort muss mindestens 8 Zeichen lang sein.';
    } elseif ($password !== $confirm) {
        $error = 'Passwörter stimmen nicht überein.';
    } else {
        Database::execute(
            'INSERT INTO users (name, email, password_hash, is_admin, weekly_hours, vacation_days, must_change_password)
             VALUES (?, ?, ?, 1, 40.00, 30.0, 0)',
            [$name, $email, password_hash($password, PASSWORD_BCRYPT)]
        );
        $success = 'Administrator erfolgreich angelegt! Bitte jetzt <strong>diese Datei löschen</strong> und dann <a href="' . BASE_URL . '/login">anmelden</a>.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Einrichtung – <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container" style="max-width:480px;margin-top:60px">
    <h2 class="mb-4">Ersteinrichtung</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php else: ?>
    <div class="alert alert-warning">
        <strong>Achtung:</strong> Diese Datei nach der Einrichtung bitte löschen!
    </div>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?= h($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">E-Mail</label>
            <input type="email" name="email" class="form-control" value="<?= h($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Passwort</label>
            <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="mb-3">
            <label class="form-label">Passwort bestätigen</label>
            <input type="password" name="confirm" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Administrator anlegen</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
