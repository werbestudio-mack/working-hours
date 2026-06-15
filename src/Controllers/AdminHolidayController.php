<?php
class AdminHolidayController
{
    public function index(array $p = []): void
    {
        Auth::requireAdmin();
        render('admin/holidays/index', [
            'holidays' => PublicHoliday::all(),
            'success'  => getFlash('success'),
            'error'    => getFlash('error'),
        ]);
    }

    public function create(array $p = []): void
    {
        Auth::requireAdmin();
        render('admin/holidays/form', [
            'holiday' => null,
            'error'   => getFlash('error'),
        ]);
    }

    public function store(array $p = []): void
    {
        Auth::requireAdmin();

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/admin/holidays/create');
        }

        $errors = $this->validate($_POST);
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('/admin/holidays/create');
        }

        PublicHoliday::create([
            'name'        => trim($_POST['name']),
            'date'        => $_POST['date'],
            'is_half_day' => isset($_POST['is_half_day']) ? 1 : 0,
        ]);

        flash('success', 'Feiertag gespeichert.');
        redirect('/admin/holidays');
    }

    public function edit(array $p): void
    {
        Auth::requireAdmin();
        $holiday = PublicHoliday::find((int) $p[0]);
        if (!$holiday) {
            http_response_code(404);
            die('Nicht gefunden.');
        }
        render('admin/holidays/form', [
            'holiday' => $holiday,
            'error'   => getFlash('error'),
        ]);
    }

    public function update(array $p): void
    {
        Auth::requireAdmin();
        $id = (int) $p[0];

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/admin/holidays/' . $id . '/edit');
        }

        $errors = $this->validate($_POST);
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('/admin/holidays/' . $id . '/edit');
        }

        PublicHoliday::update($id, [
            'name'        => trim($_POST['name']),
            'date'        => $_POST['date'],
            'is_half_day' => isset($_POST['is_half_day']) ? 1 : 0,
        ]);

        flash('success', 'Feiertag aktualisiert.');
        redirect('/admin/holidays');
    }

    public function destroy(array $p): void
    {
        Auth::requireAdmin();

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/admin/holidays');
        }

        PublicHoliday::delete((int) $p[0]);
        flash('success', 'Feiertag gelöscht.');
        redirect('/admin/holidays');
    }

    private function validate(array $post): array
    {
        $errors = [];
        if (empty(trim($post['name'] ?? ''))) {
            $errors[] = 'Name ist erforderlich.';
        }
        if (empty($post['date']) || !strtotime($post['date'])) {
            $errors[] = 'Ungültiges Datum.';
        }
        return $errors;
    }
}
