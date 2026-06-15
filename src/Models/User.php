<?php
class User
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM users ORDER BY name');
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO users
                (name, email, password_hash, is_admin, weekly_hours, vacation_days, must_change_password, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
            [
                $data['name'],
                $data['email'],
                $data['password_hash'],
                $data['is_admin']             ?? 0,
                $data['weekly_hours']         ?? 40.00,
                $data['vacation_days']        ?? 30.0,
                $data['must_change_password'] ?? 1,
            ]
        );
        return Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $allowed = ['name','email','password_hash','is_admin','weekly_hours',
                    'vacation_days','must_change_password','is_active'];
        $sets   = [];
        $params = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]   = "$field = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($sets)) {
            return;
        }
        $params[] = $id;
        Database::execute('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM users WHERE id = ?', [$id]);
    }
}
