<?php
declare(strict_types=1);

final class Equipment
{
    public static function allForCentre(int $centreId): array
    {
        return Database::all(
            'SELECT e.*, r.name AS room_name FROM equipment e LEFT JOIN rooms r ON r.id = e.room_id
             WHERE e.centre_id = :c ORDER BY e.name',
            ['c' => $centreId]
        );
    }

    public static function create(int $centreId, ?int $roomId, string $assetTag, string $name): int
    {
        Database::query(
            'INSERT INTO equipment (centre_id, room_id, asset_tag, name) VALUES (:c,:r,:tag,:n)',
            ['c' => $centreId, 'r' => $roomId, 'tag' => $assetTag, 'n' => $name]
        );
        return Database::lastInsertId();
    }
}
