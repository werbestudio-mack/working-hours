<?php
class AuthController
{
    public function showLogin(array $p = []): void
    {
        if (Auth::currentUser()) {
            redirect('/');
        }
        render('auth/login', ['error' => getFlash('error')]);
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
}
