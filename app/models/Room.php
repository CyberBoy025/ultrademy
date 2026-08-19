<?php
declare(strict_types=1);

final class Room
{
    public static function allForCentre(int $centreId): array
    {
        return Database::all('SELECT * FROM rooms WHERE centre_id = :c ORDER BY name', ['c' => $centreId]);
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM rooms WHERE id = :id', ['id' => $id]);
    }

    public static function create(int $centreId, string $name, string $type, int $capacity): int
    {
        Database::query('INSERT INTO rooms (centre_id, name, type, capacity) VALUES (:c,:n,:t,:cap)', [
            'c' => $centreId, 'n' => $name, 't' => $type, 'cap' => $capacity,
        ]);
        return Database::lastInsertId();
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::query('UPDATE rooms SET status = :s WHERE id = :id', ['s' => $status, 'id' => $id]);
    }
}
