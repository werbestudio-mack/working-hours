<?php
class HoursCalculator
{
    /**
     * Berechnet Soll, Ist und Saldo für einen Benutzer in einem Datumsbereich.
     */
    public static function calculate(int $userId, string $from, string $to): array
    {
        $user = User::find($userId);
        if (!$user) {
            return self::emptyResult();
        }

        $dailyTarget = (float) $user['weekly_hours'] / 5.0;
        $holidays    = PublicHoliday::indexedByDate($from, $to);

        // ── Soll ────────────────────────────────────────────────────────
        $soll    = 0.0;
        $current = new DateTime($from);
        $endDt   = new DateTime($to);

        while ($current <= $endDt) {
            $dow     = (int) $current->format('N');
            $dateStr = $current->format('Y-m-d');

            if ($dow <= 5) {
                if (isset($holidays[$dateStr])) {
                    if ($holidays[$dateStr]['is_half_day']) {
                        $soll += $dailyTarget * 0.5;
                    }
                } else {
                    $soll += $dailyTarget;
                }
            }
            $current->modify('+1 day');
        }

        // ── Zeiteinträge ─────────────────────────────────────────────────
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

        $workHours    = 0.0;
        $compensHours = 0.0;
        $vacationIst  = 0.0;
        $sickIst      = 0.0;

        foreach ($entries as $entry) {
            switch ($entry['type']) {
                case 'work':
                case 'compensatory':
                    $start   = new DateTime($entry['started_at']);
                    $end     = new DateTime($entry['ended_at']);
                    $hours   = max(0.0, ($end->getTimestamp() - $start->getTimestamp()) / 3600.0);
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

                    while ($day <= $lastDay) {
                        $dow     = (int) $day->format('N');
                        $dateStr = $day->format('Y-m-d');

                        if ($dow <= 5 && !isset($holidays[$dateStr])) {
                            if ($entry['half_day'] != 0 && $dateStr === $entry['date_start']) {
                                $vacationIst += $dailyTarget * 0.5;
                            } elseif (isset($holidays[$dateStr]) && $holidays[$dateStr]['is_half_day']) {
                                $vacationIst += $dailyTarget * 0.5;
                            } else {
                                $vacationIst += $dailyTarget;
                            }
                        }
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
            'soll'         => round($soll,         2),
            'ist'          => round($ist,           2),
            'work'         => round($workHours,     2),
            'compensatory' => round($compensHours,  2),
            'vacation_ist' => round($vacationIst,   2),
            'sick_ist'     => round($sickIst,       2),
            'balance'      => round($balance,        2),
        ];
    }

    /**
     * Berechnet Soll/Ist/Saldo pro Kalenderwoche für ein Jahr.
     * Wochen in der Zukunft werden nicht ausgegeben.
     */
    public static function byWeek(int $userId, int $year): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        $dailyTarget = (float) $user['weekly_hours'] / 5.0;
        $today       = date('Y-m-d');

        // Ersten Montag der Woche ermitteln, die den 1. Januar enthält
        $cursor = new DateTime("$year-01-01");
        $dow    = (int) $cursor->format('N');
        if ($dow > 1) {
            $cursor->modify('-' . ($dow - 1) . ' days');
        }

        $yearEndStr = "$year-12-31";

        // Gesamten benötigten Datumsbereich bestimmen
        $loadFrom = $cursor->format('Y-m-d');
        $loadTo   = (new DateTime($yearEndStr))->modify('+6 days')->format('Y-m-d');
        $loadTo   = min($loadTo, $today);

        if ($loadFrom > $today) {
            return [];
        }

        $holidays = PublicHoliday::indexedByDate($loadFrom, $loadTo);
        $entries  = Database::fetchAll(
            'SELECT * FROM time_entries
             WHERE user_id = ?
               AND (
                   (started_at IS NOT NULL AND DATE(started_at) BETWEEN ? AND ?)
                   OR
                   (date_start IS NOT NULL AND date_start <= ? AND date_end >= ?)
               )',
            [$userId, $loadFrom, $loadTo, $loadTo, $loadFrom]
        );

        $weeks = [];

        while ($cursor->format('Y-m-d') <= $yearEndStr) {
            $weekMon = $cursor->format('Y-m-d');
            $weekSun = (clone $cursor)->modify('+6 days')->format('Y-m-d');
            $kw      = (int) $cursor->format('W');

            // Auf Jahr und heutiges Datum clippen
            $wFrom = max($weekMon, "$year-01-01");
            $wTo   = min($weekSun, $yearEndStr, $today);

            if ($wFrom > $wTo) {
                $cursor->modify('+7 days');
                continue;
            }

            // Soll berechnen
            $soll = 0.0;
            $d    = new DateTime($wFrom);
            $dEnd = new DateTime($wTo);
            while ($d <= $dEnd) {
                $n  = (int) $d->format('N');
                $ds = $d->format('Y-m-d');
                if ($n <= 5) {
                    if (isset($holidays[$ds])) {
                        if ($holidays[$ds]['is_half_day']) {
                            $soll += $dailyTarget * 0.5;
                        }
                    } else {
                        $soll += $dailyTarget;
                    }
                }
                $d->modify('+1 day');
            }

            // Ist berechnen
            $workH = $compH = $vacH = $sickH = 0.0;

            foreach ($entries as $entry) {
                if ($entry['type'] === 'work' || $entry['type'] === 'compensatory') {
                    $eDate = substr($entry['started_at'], 0, 10);
                    if ($eDate >= $wFrom && $eDate <= $wTo) {
                        $s = new DateTime($entry['started_at']);
                        $e = new DateTime($entry['ended_at']);
                        $h = max(0.0, ($e->getTimestamp() - $s->getTimestamp()) / 3600.0);
                        if ($entry['type'] === 'work') {
                            $workH += $h;
                        } else {
                            $compH += $h;
                        }
                    }
                } else {
                    $eFrom = max($entry['date_start'], $wFrom);
                    $eTo   = min($entry['date_end'],   $wTo);
                    if ($eFrom > $eTo) {
                        continue;
                    }
                    $d    = new DateTime($eFrom);
                    $dEnd = new DateTime($eTo);
                    while ($d <= $dEnd) {
                        $n  = (int) $d->format('N');
                        $ds = $d->format('Y-m-d');
                        if ($n <= 5 && !isset($holidays[$ds])) {
                            if ($entry['type'] === 'vacation') {
                                if ($entry['half_day'] != 0 && $ds === $entry['date_start']) {
                                    $vacH += $dailyTarget * 0.5;
                                } else {
                                    $vacH += $dailyTarget;
                                }
                            } else {
                                $sickH += $dailyTarget;
                            }
                        }
                        $d->modify('+1 day');
                    }
                }
            }

            $ist = $workH + $compH + $vacH + $sickH;

            $weeks[] = [
                'kw'           => $kw,
                'from'         => $wFrom,
                'to'           => $wTo,
                'soll'         => round($soll,    2),
                'ist'          => round($ist,     2),
                'work'         => round($workH,   2),
                'compensatory' => round($compH,   2),
                'vacation_ist' => round($vacH,    2),
                'sick_ist'     => round($sickH,   2),
                'balance'      => round($ist - $soll, 2),
            ];

            $cursor->modify('+7 days');
        }

        return $weeks;
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
