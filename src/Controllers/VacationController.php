<?php
class VacationController
{
    public function detail(array $p = []): void
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
        $year       = (int) ($_GET['year'] ?? date('Y'));

        // Load vacation entries that overlap the selected year
        $entries = Database::fetchAll(
            "SELECT * FROM time_entries
              WHERE user_id = ?
                AND type = 'vacation'
                AND date_start <= ?
                AND date_end   >= ?
              ORDER BY date_start ASC",
            [$userId, "$year-12-31", "$year-01-01"]
        );

        // All public holidays indexed by date
        $allHolidays = [];
        foreach (PublicHoliday::all() as $h) {
            $allHolidays[$h['date']] = $h;
        }

        // Per-entry breakdown (clipped to the selected year)
        $entryDetails = [];
        foreach ($entries as $entry) {
            $clipFrom = max($entry['date_start'], "$year-01-01");
            $clipTo   = min($entry['date_end'],   "$year-12-31");

            $vacDays = 0.0;
            $holList = [];
            $isFirst = ($clipFrom === $entry['date_start']);

            $d    = new DateTime($clipFrom);
            $dEnd = new DateTime($clipTo);

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

            $entryDetails[] = [
                'entry'    => $entry,
                'vac_days' => $vacDays,
                'holidays' => $holList,
            ];
        }

        $vacationData = VacationCalculator::forUser($userId, $year);
        $userRecord   = User::find($userId);
        $allUsers     = $me['is_admin'] ? User::all() : [];

        render('vacation/detail', [
            'entryDetails' => $entryDetails,
            'vacationData' => $vacationData,
            'year'         => $year,
            'userRecord'   => $userRecord,
            'viewUserId'   => $viewUserId,
            'allUsers'     => $allUsers,
            'isAdmin'      => (bool) $me['is_admin'],
        ]);
    }
}
