<?php
class VacationCalculator
{
    /**
     * Berechnet das Urlaubskonto eines Benutzers für ein Jahr.
     */
    public static function forUser(int $userId, int $year): array
    {
        $user = User::find($userId);
        if (!$user) {
            return self::emptyResult($year);
        }

        $adjustment     = VacationAdjustment::forUserYear($userId, $year);
        $carryOver      = $adjustment ? (float) $adjustment['carry_over'] : 0.0;
        $bonusDays      = $adjustment ? (float) $adjustment['bonus_days']  : 0.0;
        $annualAllowance = (float) $user['vacation_days'];
        $totalEntitlement = $annualAllowance + $carryOver + $bonusDays;

        $yearStart = $year . '-01-01';
        $yearEnd   = $year . '-12-31';

        // Feiertage des Jahres (nach Datum indiziert)
        $fullHolidays = [];
        $halfHolidays = [];
        $rows = Database::fetchAll(
            'SELECT date, is_half_day FROM public_holidays WHERE date BETWEEN ? AND ?',
            [$yearStart, $yearEnd]
        );
        foreach ($rows as $row) {
            if ($row['is_half_day']) {
                $halfHolidays[$row['date']] = true;
            } else {
                $fullHolidays[$row['date']] = true;
            }
        }

        // Urlaubseinträge des Jahres
        $entries = Database::fetchAll(
            'SELECT * FROM time_entries
             WHERE user_id = ? AND type = "vacation"
               AND date_start <= ? AND date_end >= ?',
            [$userId, $yearEnd, $yearStart]
        );

        $taken = 0.0;
        foreach ($entries as $entry) {
            $clipStart = max($entry['date_start'], $yearStart);
            $clipEnd   = min($entry['date_end'],   $yearEnd);
            $day       = new DateTime($clipStart);
            $lastDay   = new DateTime($clipEnd);
            $isFirst   = true;

            while ($day <= $lastDay) {
                $dow     = (int) $day->format('N');
                $dateStr = $day->format('Y-m-d');

                if ($dow <= 5) {
                    if (isset($fullHolidays[$dateStr])) {
                        // Gesetzlicher Feiertag → kein Urlaubstag
                    } elseif (isset($halfHolidays[$dateStr])) {
                        // Halber Feiertag → 0,5 Urlaubstage
                        $taken += 0.5;
                    } elseif ($entry['half_day'] != 0 && $isFirst) {
                        $taken += 0.5;
                    } else {
                        $taken += 1.0;
                    }
                }
                $isFirst = false;
                $day->modify('+1 day');
            }
        }

        return [
            'year'              => $year,
            'carry_over'        => $carryOver,
            'bonus_days'        => $bonusDays,
            'annual_allowance'  => $annualAllowance,
            'total_entitlement' => $totalEntitlement,
            'taken'             => round($taken, 1),
            'remaining'         => round($totalEntitlement - $taken, 1),
        ];
    }

    private static function emptyResult(int $year): array
    {
        return [
            'year' => $year, 'carry_over' => 0.0, 'bonus_days' => 0.0,
            'annual_allowance' => 0.0, 'total_entitlement' => 0.0,
            'taken' => 0.0, 'remaining' => 0.0,
        ];
    }
}
