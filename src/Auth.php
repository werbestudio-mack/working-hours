<?php
class Auth
{
    public static function login(string $email, string $password): bool
    {
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['last_login_attempt'] ?? 0;

        if ($attempts >= 5 && (time() - $lastAttempt) < 30) {
            return false;
        }

        $user = Database::fetch(
            'SELECT * FROM users WHERE email = ? AND is_active = 1',
            [$email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_attempts']    = $attempts + 1;
            $_SESSION['last_login_attempt'] = time();
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id']             = $user['id'];
        $_SESSION['user_name']           = $user['name'];
        $_SESSION['is_admin']            = (bool) $user['is_admin'];
        $_SESSION['must_change_password']= (bool) $user['must_change_password'];
        $_SESSION['csrf_token']          = bin2hex(random_bytes(32));
        $_SESSION['login_attempts']      = 0;

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    public static function currentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        return [
            'id'                  => (int) $_SESSION['user_id'],
            'name'                => $_SESSION['user_name'],
            'is_admin'            => (bool) $_SESSION['is_admin'],
            'must_change_password'=> (bool) ($_SESSION['must_change_password'] ?? false),
        ];
    }

    public static function requireLogin(): void
    {
        if (empty($_SESSION['user_id'])) {
            redirect('/login');
        }
        if (!empty($_SESSION['must_change_password'])) {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $changePath = BASE_URL . '/password/change';
            if (rtrim($uri, '/') !== rtrim($changePath, '/')) {
                redirect('/password/change');
            }
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (empty($_SESSION['is_admin'])) {
            http_response_code(403);
            die('<h1>403 – Kein Zugriff</h1>');
        }
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return !empty($token)
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfInput(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . h(self::csrfToken()) . '">';
    }
}
