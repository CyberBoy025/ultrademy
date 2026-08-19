<?php
declare(strict_types=1);

final class Role
{
    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        return Database::all('SELECT * FROM roles ORDER BY name');
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM roles WHERE id = :id', ['id' => $id]);
    }
}
