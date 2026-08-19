<?php
declare(strict_types=1);

final class Department
{
    public static function all(): array
    {
        return Database::all('SELECT * FROM departments ORDER BY name');
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM departments WHERE id = :id', ['id' => $id]);
    }

    public static function create(string $name): int
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        Database::query('INSERT INTO departments (name, slug) VALUES (:n,:s)', ['n' => $name, 's' => $slug]);
        return Database::lastInsertId();
    }
}
