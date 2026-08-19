<?php
declare(strict_types=1);

final class Setting
{
    public static function all(): array
    {
        $rows = Database::all('SELECT * FROM settings ORDER BY `group`, `key`');
        foreach ($rows as &$r) {
            $r['value'] = json_decode((string) $r['value'], true);
        }
        return $rows;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = Database::one('SELECT value FROM settings WHERE `key` = :k', ['k' => $key]);
        return $row ? json_decode((string) $row['value'], true) : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        Database::query(
            'INSERT INTO settings (`key`, value) VALUES (:k,:v) ON DUPLICATE KEY UPDATE value = VALUES(value)',
            ['k' => $key, 'v' => json_encode($value)]
        );
    }
}
