<?php
class VacationAdjustment
{
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT * FROM vacation_adjustments WHERE user_id = ? ORDER BY year DESC',
            [$userId]
        );
    }

    public static function forUserYear(int $userId, int $year): ?array
    {
        return Database::fetch(
            'SELECT * FROM vacation_adjustments WHERE user_id = ? AND year = ?',
            [$userId, $year]
        );
    }

    public static function upsert(int $userId, int $year, float $carryOver, float $bonusDays, ?string $note): void
    {
        $existing = self::forUserYear($userId, $year);
        if ($existing) {
            Database::execute(
                'UPDATE vacation_adjustments SET carry_over=?, bonus_days=?, note=? WHERE user_id=? AND year=?',
                [$carryOver, $bonusDays, $note, $userId, $year]
            );
        } else {
            Database::execute(
                'INSERT INTO vacation_adjustments (user_id, year, carry_over, bonus_days, note)
                 VALUES (?, ?, ?, ?, ?)',
                [$userId, $year, $carryOver, $bonusDays, $note]
            );
        }
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM vacation_adjustments WHERE id = ?', [$id]);
    }
}
