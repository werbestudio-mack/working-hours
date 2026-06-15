<?php
class AdminUserController
{
    public function index(array $p = []): void
    {
        Auth::requireAdmin();
        render('admin/users/index', [
            'users'   => User::all(),
            'success' => getFlash('success'),
            'error'   => getFlash('error'),
        ]);
    }

    public function create(array $p = []): void
    {
        Auth::requireAdmin();
        render('admin/users/form', [
            'user'        => null,
            'adjustments' => [],
            'error'       => getFlash('error'),
        ]);
    }

    public function store(array $p = []): void
    {
        Auth::requireAdmin();

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/admin/users/create');
        }

        $errors = $this->validateUser($_POST, null);
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('/admin/users/create');
        }

        $password = $_POST['password'] ?? '';
        if (strlen($password) < 8) {
            flash('error', 'Passwort muss mindestens 8 Zeichen lang sein.');
            redirect('/admin/users/create');
        }

        User::create([
            'name'                 => trim($_POST['name']),
            'email'                => strtolower(trim($_POST['email'])),
            'password_hash'        => password_hash($password, PASSWORD_BCRYPT),
            'is_admin'             => isset($_POST['is_admin'])  ? 1 : 0,
            'weekly_hours'         => (float) ($_POST['weekly_hours']  ?? 40),
            'vacation_days'        => (float) ($_POST['vacation_days'] ?? 30),
            'must_change_password' => isset($_POST['must_change_password']) ? 1 : 0,
        ]);

        flash('success', 'Benutzer angelegt.');
        redirect('/admin/users');
    }

    public function edit(array $p): void
    {
        Auth::requireAdmin();
        $user = $this->findUser((int) $p[0]);
        render('admin/users/form', [
            'user'        => $user,
            'adjustments' => VacationAdjustment::forUser($user['id']),
            'error'       => getFlash('error'),
            'success'     => getFlash('success'),
        ]);
    }

    public function update(array $p): void
    {
        Auth::requireAdmin();
        $userId = (int) $p[0];
        $this->findUser($userId);

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/admin/users/' . $userId . '/edit');
        }

        $errors = $this->validateUser($_POST, $userId);
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('/admin/users/' . $userId . '/edit');
        }

        $updateData = [
            'name'         => trim($_POST['name']),
            'email'        => strtolower(trim($_POST['email'])),
            'weekly_hours' => (float) ($_POST['weekly_hours']  ?? 40),
            'vacation_days'=> (float) ($_POST['vacation_days'] ?? 30),
            'is_active'    => isset($_POST['is_active']) ? 1 : 0,
        ];

        // Admin-Rechte nur ändern wenn nicht der eigene Account
        $me = Auth::currentUser();
        if ($userId !== $me['id']) {
            $updateData['is_admin'] = isset($_POST['is_admin']) ? 1 : 0;
        }

        // Passwort nur überschreiben wenn ein neues eingegeben wurde
        $password = $_POST['password'] ?? '';
        if ($password !== '') {
            if (strlen($password) < 8) {
                flash('error', 'Passwort muss mindestens 8 Zeichen lang sein.');
                redirect('/admin/users/' . $userId . '/edit');
            }
            $updateData['password_hash']        = password_hash($password, PASSWORD_BCRYPT);
            $updateData['must_change_password']  = isset($_POST['must_change_password']) ? 1 : 0;
        }

        User::update($userId, $updateData);
        flash('success', 'Benutzer aktualisiert.');
        redirect('/admin/users/' . $userId . '/edit');
    }

    public function destroy(array $p): void
    {
        Auth::requireAdmin();
        $userId = (int) $p[0];

        if ($userId === Auth::currentUser()['id']) {
            flash('error', 'Sie können sich selbst nicht löschen.');
            redirect('/admin/users');
        }

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/admin/users');
        }

        User::delete($userId);
        flash('success', 'Benutzer gelöscht.');
        redirect('/admin/users');
    }

    public function adjustmentSave(array $p): void
    {
        Auth::requireAdmin();
        $userId = (int) $p[0];
        $this->findUser($userId);

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/admin/users/' . $userId . '/edit');
        }

        $year      = (int)   ($_POST['year']       ?? date('Y'));
        $carryOver = (float) ($_POST['carry_over'] ?? 0);
        $bonusDays = (float) ($_POST['bonus_days'] ?? 0);
        $note      = trim($_POST['note'] ?? '') ?: null;

        if ($year < 2000 || $year > 2100) {
            flash('error', 'Ungültiges Jahr.');
            redirect('/admin/users/' . $userId . '/edit');
        }

        VacationAdjustment::upsert($userId, $year, $carryOver, $bonusDays, $note);
        flash('success', 'Urlaubsanpassung gespeichert.');
        redirect('/admin/users/' . $userId . '/edit');
    }

    public function adjustmentDelete(array $p): void
    {
        Auth::requireAdmin();
        $userId = (int) $p[0];
        $adjId  = (int) $p[1];
        $this->findUser($userId);

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/admin/users/' . $userId . '/edit');
        }

        VacationAdjustment::delete($adjId);
        flash('success', 'Urlaubsanpassung gelöscht.');
        redirect('/admin/users/' . $userId . '/edit');
    }

    public function dump(array $p = []): void
    {
        Auth::requireAdmin();

        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $pdo    = Database::get();
        $tables = ['users', 'public_holidays', 'time_entries', 'vacation_adjustments'];

        echo "-- Arbeitszeiterfassung Datenbankdump\n";
        echo "-- Erstellt: " . date('Y-m-d H:i:s') . "\n\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            echo "DROP TABLE IF EXISTS `$table`;\n";
            echo $create[1] . ";\n\n";

            $rows = Database::fetchAll("SELECT * FROM `$table`");
            if ($rows) {
                $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $valueRows = [];
                foreach ($rows as $row) {
                    $vals = array_map(static function ($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote((string) $v);
                    }, array_values($row));
                    $valueRows[] = '(' . implode(', ', $vals) . ')';
                }
                echo "INSERT INTO `$table` ($cols) VALUES\n";
                echo implode(",\n", $valueRows) . ";\n\n";
            }
        }

        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        exit;
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function findUser(int $id): array
    {
        $user = User::find($id);
        if (!$user) {
            http_response_code(404);
            die('Benutzer nicht gefunden.');
        }
        return $user;
    }

    private function validateUser(array $post, ?int $excludeId): array
    {
        $errors = [];
        if (empty(trim($post['name'] ?? ''))) {
            $errors[] = 'Name ist erforderlich.';
        }
        $email = strtolower(trim($post['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ungültige E-Mail-Adresse.';
        } else {
            $existing = User::findByEmail($email);
            if ($existing && (int) $existing['id'] !== $excludeId) {
                $errors[] = 'Diese E-Mail-Adresse ist bereits vergeben.';
            }
        }
        $wh = (float) ($post['weekly_hours'] ?? 0);
        if ($wh <= 0 || $wh > 168) {
            $errors[] = 'Ungültige Wochenstunden.';
        }
        $vd = (float) ($post['vacation_days'] ?? -1);
        if ($vd < 0 || $vd > 365) {
            $errors[] = 'Ungültige Urlaubstage.';
        }
        return $errors;
    }
}
