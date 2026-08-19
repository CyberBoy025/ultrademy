<?php
declare(strict_types=1);

final class Centre
{
    /** @param array<int,int>|null $ids null = no restriction */
    public static function all(?array $ids = null): array
    {
        if ($ids === null) {
            return Database::all('SELECT * FROM centres ORDER BY name');
        }
        if (empty($ids)) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        return Database::query("SELECT * FROM centres WHERE id IN ($ph) ORDER BY name", array_values($ids))->fetchAll();
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT c.*, CONCAT(p.first_name, " ", p.last_name) AS manager_name
             FROM centres c LEFT JOIN users u ON u.id = c.manager_user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             WHERE c.id = :id',
            ['id' => $id]
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM centres WHERE slug = :s', ['s' => $slug]);
    }

    public static function counts(int $id): array
    {
        $rooms = Database::one('SELECT COUNT(*) c FROM rooms WHERE centre_id=:id', ['id' => $id])['c'];
        $staff = Database::one('SELECT COUNT(*) c FROM staff_centres WHERE centre_id=:id', ['id' => $id])['c'];
        $students = Database::one('SELECT COUNT(*) c FROM enrolments WHERE centre_id=:id AND status="active"', ['id' => $id])['c'];
        $cohorts = Database::one('SELECT COUNT(*) c FROM cohorts WHERE centre_id=:id AND status IN ("open","running")', ['id' => $id])['c'];
        return compact('rooms', 'staff', 'students', 'cohorts');
    }

    public static function create(string $code, string $name, string $slug, string $city, string $state, string $status): int
    {
        Database::query(
            'INSERT INTO centres (code, name, slug, city, state, status) VALUES (:code,:name,:slug,:city,:state,:status)',
            compact('code', 'name', 'slug', 'city', 'state', 'status')
        );
        return Database::lastInsertId();
    }

    public static function update(int $id, string $name, string $city, string $state, string $phone, string $email, string $status): void
    {
        Database::query(
            'UPDATE centres SET name=:name, city=:city, state=:state, phone=:phone, email=:email, status=:status WHERE id=:id',
            compact('name', 'city', 'state', 'phone', 'email', 'status', 'id')
        );
    }
}
