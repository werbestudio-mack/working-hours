<?php
class AuthController
{
    public function showLogin(array $p = []): void
    {
        if (Auth::currentUser()) {
            redirect('/');
        }
        render('auth/login', ['error' => getFlash('error'), 'success' => getFlash('success')]);
    }

    public function login(array $p = []): void
    {
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/login');
        }

        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password'] ?? '';

        if (Auth::login($email, $password)) {
            redirect('/');
        } else {
            flash('error', 'E-Mail oder Passwort falsch, oder zu viele Fehlversuche (30s Sperre).');
            redirect('/login');
        }
    }

    public function logout(array $p = []): void
    {
        Auth::logout();
    }

    public function showChangePassword(array $p = []): void
    {
        Auth::requireLogin();
        render('auth/change_password', [
            'error'   => getFlash('error'),
            'success' => getFlash('success'),
        ]);
    }

    public function changePassword(array $p = []): void
    {
        Auth::requireLogin();

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/password/change');
        }

        $currentPw  = $_POST['current_password']  ?? '';
        $newPw      = $_POST['new_password']       ?? '';
        $confirmPw  = $_POST['confirm_password']   ?? '';

        $userId = Auth::currentUser()['id'];
        $user   = Database::fetch('SELECT * FROM users WHERE id = ?', [$userId]);

        if (!password_verify($currentPw, $user['password_hash'])) {
            flash('error', 'Aktuelles Passwort ist falsch.');
            redirect('/password/change');
        }
        if (strlen($newPw) < 8) {
            flash('error', 'Das neue Passwort muss mindestens 8 Zeichen lang sein.');
            redirect('/password/change');
        }
        if ($newPw !== $confirmPw) {
            flash('error', 'Die neuen Passwörter stimmen nicht überein.');
            redirect('/password/change');
        }

        User::update($userId, [
            'password_hash'        => password_hash($newPw, PASSWORD_BCRYPT),
            'must_change_password' => 0,
        ]);
        $_SESSION['must_change_password'] = false;

        flash('success', 'Passwort erfolgreich geändert.');
        redirect('/');
    }

    // ── Passwort vergessen ────────────────────────────────────────────

    public function showForgot(array $p = []): void
    {
        if (Auth::currentUser()) {
            redirect('/');
        }
        render('auth/forgot', [
            'error'   => getFlash('error'),
            'success' => getFlash('success'),
        ]);
    }

    public function forgot(array $p = []): void
    {
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/password/forgot');
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        $user  = User::findByEmail($email);

        if ($user && $user['is_active']) {
            // Alte Token löschen
            Database::execute('DELETE FROM password_resets WHERE user_id = ?', [$user['id']]);

            // Neuen Token erzeugen (1 Stunde gültig)
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            Database::execute(
                'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)',
                [$user['id'], $token, $expiresAt]
            );

            $this->sendResetMail($user, $token);
        }

        // Gleiche Meldung unabhängig davon, ob E-Mail existiert (Security)
        flash('success', 'Falls ein Konto mit dieser E-Mail-Adresse existiert, wurde ein Reset-Link verschickt. Bitte prüfen Sie auch Ihren Spam-Ordner.');
        redirect('/password/forgot');
    }

    public function showReset(array $p = []): void
    {
        $token = $_GET['token'] ?? '';

        $reset = $token
            ? Database::fetch(
                'SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()',
                [$token]
              )
            : null;

        if (!$reset) {
            flash('error', 'Dieser Link ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.');
            redirect('/password/forgot');
        }

        render('auth/reset', [
            'token' => $token,
            'error' => getFlash('error'),
        ]);
    }

    public function resetPassword(array $p = []): void
    {
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/login');
        }

        $token   = $_POST['token']            ?? '';
        $newPw   = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $reset = $token
            ? Database::fetch(
                'SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()',
                [$token]
              )
            : null;

        if (!$reset) {
            flash('error', 'Dieser Link ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.');
            redirect('/password/forgot');
        }

        if (strlen($newPw) < 8) {
            flash('error', 'Das Passwort muss mindestens 8 Zeichen lang sein.');
            redirect('/password/reset?token=' . urlencode($token));
        }
        if ($newPw !== $confirm) {
            flash('error', 'Die Passwörter stimmen nicht überein.');
            redirect('/password/reset?token=' . urlencode($token));
        }

        User::update((int) $reset['user_id'], [
            'password_hash'        => password_hash($newPw, PASSWORD_BCRYPT),
            'must_change_password' => 0,
        ]);

        // Alle Token dieses Benutzers ungültig machen
        Database::execute('DELETE FROM password_resets WHERE user_id = ?', [$reset['user_id']]);

        flash('success', 'Passwort erfolgreich zurückgesetzt. Sie können sich jetzt anmelden.');
        redirect('/login');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function sendResetMail(array $user, string $token): void
    {
        $appName   = defined('APP_NAME')   ? APP_NAME   : 'Arbeitszeiterfassung';
        $fromEmail = defined('MAIL_FROM')  ? MAIL_FROM  : 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $baseUrl   = defined('BASE_URL')   ? BASE_URL   : '';

        $resetUrl = $baseUrl . '/password/reset?token=' . $token;

        $subject = '=?UTF-8?B?' . base64_encode('Passwort zurücksetzen – ' . $appName) . '?=';

        $body = "Hallo " . $user['name'] . ",\r\n\r\n"
              . "Sie haben eine Passwort-Zurücksetzung für Ihr Konto angefordert.\r\n\r\n"
              . "Klicken Sie auf folgenden Link, um ein neues Passwort zu vergeben:\r\n"
              . $resetUrl . "\r\n\r\n"
              . "Der Link ist 1 Stunde gültig.\r\n\r\n"
              . "Falls Sie keine Zurücksetzung angefordert haben, ignorieren Sie diese E-Mail.\r\n"
              . "Ihr Passwort bleibt dann unverändert.\r\n\r\n"
              . "-- " . $appName;

        $fromHeader = '=?UTF-8?B?' . base64_encode($appName) . '?= <' . $fromEmail . '>';
        $headers    = "From: $fromHeader\r\n"
                    . "Reply-To: $fromEmail\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: 8bit\r\n";

        $sent = mail($user['email'], $subject, $body, $headers);
        if (!$sent) {
            error_log('Passwort-Reset: mail() an ' . $user['email'] . ' fehlgeschlagen (From: ' . $fromEmail . ')');
        }
    }
}
