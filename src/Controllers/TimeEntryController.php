<?php
class TimeEntryController
{
    public function index(array $p = []): void
    {
        Auth::requireLogin();
        $me     = Auth::currentUser();
        $userId = (int) $me['id'];

        if ($me['is_admin'] && isset($_GET['view_user_id'])) {
            $vu = User::find((int) $_GET['view_user_id']);
            if ($vu) {
                $userId = (int) $vu['id'];
            }
        }
        $viewUserId = $userId;

        $year     = (int) ($_GET['year']  ?? date('Y'));
        $month    = (int) ($_GET['month'] ?? date('m'));
        $view     = in_array($_GET['view'] ?? '', ['list', 'kw']) ? $_GET['view'] : 'list';
        $allUsers = $me['is_admin'] ? User::all() : [];

        if ($view === 'kw') {
            $weekData = HoursCalculator::byWeek($userId, $year);
            render('entries/index', [
                'view'        => 'kw',
                'weekData'    => $weekData,
                'dateGroups'  => [],
                'year'        => $year,
                'month'       => $month,
                'from'        => "$year-01-01",
                'to'          => "$year-12-31",
                'success'     => getFlash('success'),
                'error'       => getFlash('error'),
                'viewUserId'  => $viewUserId,
                'allUsers'    => $allUsers,
                'isAdmin'     => (bool) $me['is_admin'],
            ]);
            return;
        }

        $from    = sprintf('%04d-%02d-01', $year, $month);
        $to      = date('Y-m-t', strtotime($from));
        $entries = TimeEntry::forUser($userId, $from, $to);

        $dateGroups = $this->buildDateGroups($entries);

        render('entries/index', [
            'view'       => 'list',
            'weekData'   => [],
            'dateGroups' => $dateGroups,
            'year'       => $year,
            'month'      => $month,
            'from'       => $from,
            'to'         => $to,
            'success'    => getFlash('success'),
            'error'      => getFlash('error'),
            'viewUserId' => $viewUserId,
            'allUsers'   => $allUsers,
            'isAdmin'    => (bool) $me['is_admin'],
        ]);
    }

    public function create(array $p = []): void
    {
        Auth::requireLogin();
        render('entries/form', [
            'entry'   => null,
            'error'   => getFlash('error'),
            'backUrl' => BASE_URL . '/entries',
        ]);
    }

    public function store(array $p = []): void
    {
        Auth::requireLogin();

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/entries/create');
        }

        $data   = $this->buildEntryData($_POST);
        $errors = $this->validateEntry($data);

        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('/entries/create');
        }

        $data['user_id'] = Auth::currentUser()['id'];
        TimeEntry::create($data);
        flash('success', 'Eintrag gespeichert.');
        redirect('/entries');
    }

    public function edit(array $p): void
    {
        Auth::requireLogin();
        $entry  = $this->accessibleEntry((int) $p[0]);
        $me     = Auth::currentUser();

        $backUrl = BASE_URL . '/entries';
        if ($me['is_admin'] && isset($_GET['view_user_id']) && (int)$_GET['view_user_id'] !== (int)$me['id']) {
            $backUrl .= '?view_user_id=' . (int) $_GET['view_user_id'];
        }

        render('entries/form', [
            'entry'   => $entry,
            'error'   => getFlash('error'),
            'backUrl' => $backUrl,
        ]);
    }

    public function update(array $p): void
    {
        Auth::requireLogin();
        $entry = $this->accessibleEntry((int) $p[0]);

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/entries/' . $p[0] . '/edit');
        }

        $data   = $this->buildEntryData($_POST);
        $errors = $this->validateEntry($data);

        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('/entries/' . $p[0] . '/edit');
        }

        TimeEntry::update((int) $p[0], $data);
        flash('success', 'Eintrag aktualisiert.');
        redirect($this->entriesRedirectPath($entry));
    }

    public function destroy(array $p): void
    {
        Auth::requireLogin();
        $entry = $this->accessibleEntry((int) $p[0]);

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Ungültige Anfrage.');
            redirect('/entries');
        }

        TimeEntry::delete((int) $p[0]);
        flash('success', 'Eintrag gelöscht.');
        redirect($this->entriesRedirectPath($entry));
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Gruppiert Einträge nach Datum, berechnet Tages-Soll/Ist und
     * reichert Urlaubseinträge mit Urlaubstage-Infos an.
     */
    private function buildDateGroups(array $entries): array
    {
        // Alle Feiertage einmalig laden
        $allHolidays = [];
        foreach (PublicHoliday::all() as $h) {
            $allHolidays[$h['date']] = $h;
        }

        // Tages-Soll des aktuell betrachteten Benutzers
        $me         = Auth::currentUser();
        $userId     = $me['is_admin'] && isset($_GET['view_user_id'])
            ? (int) $_GET['view_user_id'] : (int) $me['id'];
        $userRecord  = User::find($userId);
        $dailyTarget = ($userRecord && (float)$userRecord['weekly_hours'] > 0)
            ? (float)$userRecord['weekly_hours'] / 5.0
            : 0.0;

        $dateGroups = [];

        foreach ($entries as $entry) {
            $entryDate = in_array($entry['type'], ['work', 'compensatory'])
                ? substr($entry['started_at'], 0, 10)
                : $entry['date_start'];

            if (!isset($dateGroups[$entryDate])) {
                $dow = (int)(new DateTime($entryDate))->format('N');
                if ($dow <= 5) {
                    $h = $allHolidays[$entryDate] ?? null;
                    if ($h && !$h['is_half_day']) {
                        $daySoll = 0.0;
                    } elseif ($h && $h['is_half_day']) {
                        $daySoll = $dailyTarget * 0.5;
                    } else {
                        $daySoll = $dailyTarget;
                    }
                } else {
                    $daySoll = 0.0;
                }
                $dateGroups[$entryDate] = [
                    'date'     => $entryDate,
                    'soll'     => $daySoll,
                    'work_ist' => 0.0,
                    'has_time' => false,
                    'has_date' => false,
                    'entries'  => [],
                ];
            }

            $enriched = $entry;

            if (in_array($entry['type'], ['work', 'compensatory'])) {
                $s = strtotime($entry['started_at']);
                $e = strtotime($entry['ended_at']);
                $dateGroups[$entryDate]['work_ist'] += max(0.0, ($e - $s) / 3600.0);
                $dateGroups[$entryDate]['has_time']  = true;
            } else {
                $dateGroups[$entryDate]['has_date'] = true;

                if ($entry['type'] === 'vacation') {
                    $d       = new DateTime($entry['date_start']);
                    $dEnd    = new DateTime($entry['date_end']);
                    $vacDays = 0.0;
                    $holList = [];
                    $isFirst = true;

                    while ($d <= $dEnd) {
                        $n  = (int) $d->format('N');
                        $ds = $d->format('Y-m-d');
                        if ($n <= 5) {
                            $hol = $allHolidays[$ds] ?? null;
                            if ($hol && !$hol['is_half_day']) {
                                $holList[] = $hol;
                            } elseif ($hol && $hol['is_half_day']) {
                                $vacDays += 0.5;
                            } elseif ($entry['half_day'] != 0 && $isFirst) {
                                $vacDays += 0.5;
                            } else {
                                $vacDays += 1.0;
                            }
                        }
                        $isFirst = false;
                        $d->modify('+1 day');
                    }
                    $enriched['_vac_days'] = $vacDays;
                    $enriched['_vac_hols'] = $holList;
                }
            }

            $dateGroups[$entryDate]['entries'][] = $enriched;
        }

        // Einträge innerhalb einer Gruppe chronologisch sortieren
        foreach ($dateGroups as &$group) {
            usort($group['entries'], static function (array $a, array $b): int {
                $tsA = strtotime($a['started_at'] ?? $a['date_start']);
                $tsB = strtotime($b['started_at'] ?? $b['date_start']);
                return $tsA <=> $tsB;
            });
        }
        unset($group);

        // Gruppen absteigend nach Datum sortieren (neueste oben)
        krsort($dateGroups);

        return $dateGroups;
    }

    private function accessibleEntry(int $id): array
    {
        $me    = Auth::currentUser();
        $entry = TimeEntry::find($id);
        if (!$entry) {
            http_response_code(404);
            die('Eintrag nicht gefunden.');
        }
        if ((int) $entry['user_id'] !== (int) $me['id'] && !$me['is_admin']) {
            http_response_code(403);
            die('Zugriff verweigert.');
        }
        return $entry;
    }

    private function entriesRedirectPath(array $entry): string
    {
        $me = Auth::currentUser();
        if ((int) $entry['user_id'] !== (int) $me['id']) {
            return '/entries?view_user_id=' . $entry['user_id'];
        }
        return '/entries';
    }

    private function buildEntryData(array $post): array
    {
        $type = $post['type'] ?? '';
        $data = [
            'type'  => $type,
            'notes' => trim($post['notes'] ?? '') ?: null,
        ];

        if (in_array($type, ['work', 'compensatory'], true)) {
            $startDate = $post['started_date'] ?? '';
            $startTime = $post['started_time'] ?? '';
            $endDate   = $post['ended_date']   ?? '';
            $endTime   = $post['ended_time']   ?? '';

            $data['started_at'] = $startDate && $startTime ? "$startDate $startTime:00" : null;
            $data['ended_at']   = $endDate   && $endTime   ? "$endDate $endTime:00"     : null;
            $data['date_start'] = null;
            $data['date_end']   = null;
            $data['half_day']   = 0;
        } else {
            $data['started_at'] = null;
            $data['ended_at']   = null;
            $data['date_start'] = $post['date_start'] ?? '';
            $data['date_end']   = $post['date_end']   ?? '';
            $data['half_day']   = (int) ($post['half_day'] ?? 0);
        }

        return $data;
    }

    private function validateEntry(array $data): array
    {
        $errors     = [];
        $validTypes = ['work', 'vacation', 'sick', 'compensatory'];

        if (!in_array($data['type'], $validTypes, true)) {
            return ['Ungültiger Typ.'];
        }

        if (in_array($data['type'], ['work', 'compensatory'], true)) {
            if (!$data['started_at'] || !strtotime($data['started_at'])) {
                $errors[] = 'Ungültiges Startdatum / -uhrzeit.';
            }
            if (!$data['ended_at'] || !strtotime($data['ended_at'])) {
                $errors[] = 'Ungültiges Enddatum / -uhrzeit.';
            }
            if (!$errors && strtotime($data['started_at']) >= strtotime($data['ended_at'])) {
                $errors[] = 'Die Endzeit muss nach der Startzeit liegen.';
            }
        } else {
            if (empty($data['date_start'])) {
                $errors[] = 'Startdatum fehlt.';
            }
            if (empty($data['date_end'])) {
                $errors[] = 'Enddatum fehlt.';
            }
            if (!$errors && $data['date_start'] > $data['date_end']) {
                $errors[] = 'Das Enddatum muss nach dem Startdatum liegen.';
            }
            if ($data['type'] === 'vacation'
                && $data['half_day'] != 0
                && $data['date_start'] !== $data['date_end']) {
                $errors[] = 'Halber Urlaubstag ist nur bei einem Einzeltag möglich.';
            }
        }

        return $errors;
    }
}
