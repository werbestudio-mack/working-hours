<?php
class TimeEntry
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM time_entries WHERE id = ?', [$id]);
    }

    public static function forUser(int $userId, ?string $from = null, ?string $to = null): array
    {
        $sql    = 'SELECT * FROM time_entries WHERE user_id = ?';
        $params = [$userId];

        if ($from !== null && $to !== null) {
            $sql .= ' AND (
                (started_at  IS NOT NULL AND DATE(started_at)  <= ? AND DATE(ended_at)  >= ?)
                OR
                (date_start  IS NOT NULL AND date_start        <= ? AND date_end         >= ?)
            )';
            $params = array_merge($params, [$to, $from, $to, $from]);
        }

        $sql .= ' ORDER BY COALESCE(started_at, CAST(date_start AS DATETIME)) DESC';
        return Database::fetchAll($sql, $params);
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO time_entries
                (user_id, type, started_at, ended_at, date_start, date_end, half_day, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['type'],
                $data['started_at']  ?? null,
                $data['ended_at']    ?? null,
                $data['date_start']  ?? null,
                $data['date_end']    ?? null,
                $data['half_day']    ?? 0,
                $data['notes']       ?? null,
            ]
        );
        return Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE time_entries
             SET type=?, started_at=?, ended_at=?, date_start=?, date_end=?, half_day=?, notes=?
             WHERE id = ?',
            [
                $data['type'],
                $data['started_at']  ?? null,
                $data['ended_at']    ?? null,
                $data['date_start']  ?? null,
                $data['date_end']    ?? null,
                $data['half_day']    ?? 0,
                $data['notes']       ?? null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM time_entries WHERE id = ?', [$id]);
    }
}
