<?php
class PublicHoliday
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM public_holidays WHERE id = ?', [$id]);
    }

    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM public_holidays ORDER BY date');
    }

    public static function forYear(int $year): array
    {
        return Database::fetchAll(
            'SELECT * FROM public_holidays WHERE YEAR(date) = ? ORDER BY date',
            [$year]
        );
    }

    /** Returns array indexed by 'YYYY-MM-DD' => row */
    public static function indexedByDate(string $from, string $to): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM public_holidays WHERE date BETWEEN ? AND ?',
            [$from, $to]
        );
        $idx = [];
        foreach ($rows as $row) {
            $idx[$row['date']] = $row;
        }
        return $idx;
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO public_holidays (name, date, is_half_day) VALUES (?, ?, ?)',
            [$data['name'], $data['date'], $data['is_half_day'] ?? 0]
        );
        return Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE public_holidays SET name=?, date=?, is_half_day=? WHERE id=?',
            [$data['name'], $data['date'], $data['is_half_day'] ?? 0, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM public_holidays WHERE id = ?', [$id]);
    }
}
