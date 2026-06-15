<?php
class HoursCalculator
{
    /**
     * Berechnet Soll, Ist und Saldo für einen Benutzer in einem Datumsbereich.
     *
     * @param int    $userId
     * @param string $from   'YYYY-MM-DD'
     * @param string $to     'YYYY-MM-DD'
     * @return array{soll:float,ist:float,work:float,compensatory:float,vacation_ist:float,sick_ist:float,balance:float}
     */
    public static function calculate(int $userId, string $from, string $to): array
    {
        $user = User::find($userId);
        if (!$user) {
            return self::emptyResult();
        }

        $dailyTarget = (float) $user['weekly_hours'] / 5.0;
        $holidays    = PublicHoliday::indexedByDate($from, $to);

        // ── Soll berechnen ───────────────────────────────────────────────
        $soll    = 0.0;
        $current = new DateTime($from);
        $endDt   = new DateTime($to);

        while ($current <= $endDt) {
            $dow     = (int) $current->format('N'); // 1=Mo … 7=So
            $dateStr = $current->format('Y-m-d');

            if ($dow <= 5) {
                if (isset($holidays[$dateStr])) {
                    if ($holidays[$dateStr]['is_half_day']) {
                        $soll += $dailyTarget * 0.5;
                    }
                    // Voller Feiertag → 0h Soll
                } else {
                    $soll += $dailyTarget;
                }
            }
            $current->modify('+1 day');
        }

        // ── Zeiteinträge laden ───────────────────────────────────────────
        $entries = Database::fetchAll(
            'SELECT * FROM time_entries
             WHERE user_id = ?
               AND (
                   (started_at IS NOT NULL AND DATE(started_at) <= ? AND DATE(ended_at) >= ?)
                   OR
                   (date_start IS NOT NULL AND date_start <= ? AND date_end >= ?)
               )',
            [$userId, $to, $from, $to, $from]
        );

        $workHours        = 0.0;
        $compensHours     = 0.0;
        $vacationIst      = 0.0;
        $sickIst          = 0.0;

        foreach ($entries as $entry) {
            switch ($entry['type']) {
                case 'work':
                case 'compensatory':
                    $start   = new DateTime($entry['started_at']);
                    $end     = new DateTime($entry['ended_at']);
                    $seconds = $end->getTimestamp() - $start->getTimestamp();
                    $hours   = max(0.0, $seconds / 3600.0);

                    if ($entry['type'] === 'work') {
                        $workHours += $hours;
                    } else {
                        $compensHours += $hours;
                    }
                    break;

                case 'vacation':
                    $clipStart = max($entry['date_start'], $from);
                    $clipEnd   = min($entry['date_end'],   $to);
                    $day       = new DateTime($clipStart);
                    $lastDay   = new DateTime($clipEnd);
                    $isFirst   = true;

                    while ($day <= $lastDay) {
                        $dow     = (int) $day->format('N');
                        $dateStr = $day->format('Y-m-d');

                        if ($dow <= 5 && !isset($holidays[$dateStr])) {
                            if ($entry['half_day'] != 0 && $isFirst) {
                                $vacationIst += $dailyTarget * 0.5;
                            } elseif (isset($holidays[$dateStr]) && $holidays[$dateStr]['is_half_day']) {
                                // Halber Feiertag – halber Urlaub anrechnen
                                $vacationIst += $dailyTarget * 0.5;
                            } else {
                                $vacationIst += $dailyTarget;
                            }
                        }
                        $isFirst = false;
                        $day->modify('+1 day');
                    }
                    break;

                case 'sick':
                    $clipStart = max($entry['date_start'], $from);
                    $clipEnd   = min($entry['date_end'],   $to);
                    $day       = new DateTime($clipStart);
                    $lastDay   = new DateTime($clipEnd);

                    while ($day <= $lastDay) {
                        $dow     = (int) $day->format('N');
                        $dateStr = $day->format('Y-m-d');

                        if ($dow <= 5 && !isset($holidays[$dateStr])) {
                            $sickIst += $dailyTarget;
                        }
                        $day->modify('+1 day');
                    }
                    break;
            }
        }

        $ist     = $workHours + $compensHours + $vacationIst + $sickIst;
        $balance = $ist - $soll;

        return [
            'soll'          => round($soll,         2),
            'ist'           => round($ist,          2),
            'work'          => round($workHours,    2),
            'compensatory'  => round($compensHours, 2),
            'vacation_ist'  => round($vacationIst,  2),
            'sick_ist'      => round($sickIst,      2),
            'balance'       => round($balance,      2),
        ];
    }

    private static function emptyResult(): array
    {
        return [
            'soll' => 0.0, 'ist' => 0.0, 'work' => 0.0,
            'compensatory' => 0.0, 'vacation_ist' => 0.0,
            'sick_ist' => 0.0, 'balance' => 0.0,
        ];
    }
}
