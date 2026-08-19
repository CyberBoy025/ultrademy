<?php
declare(strict_types=1);

final class Programme
{
    public static function all(bool $publishedOnly): array
    {
        $sql = 'SELECT p.*, pc.name AS category_name FROM programmes p
                LEFT JOIN programme_categories pc ON pc.id = p.category_id';
        if ($publishedOnly) {
            $sql .= " WHERE p.status = 'published'";
        }
        $sql .= ' ORDER BY p.title';
        return Database::all($sql);
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT p.*, pc.name AS category_name FROM programmes p
             LEFT JOIN programme_categories pc ON pc.id = p.category_id WHERE p.id = :id',
            ['id' => $id]
        );
    }

    public static function centresFor(int $programmeId): array
    {
        return Database::all(
            'SELECT c.* FROM programme_centres pcn JOIN centres c ON c.id = pcn.centre_id WHERE pcn.programme_id = :p ORDER BY c.name',
            ['p' => $programmeId]
        );
    }

    public static function countPublished(): int
    {
        return (int) Database::one("SELECT COUNT(*) c FROM programmes WHERE status='published'")['c'];
    }

    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO programmes (code, title, slug, category_id, description, duration_weeks, delivery_mode, fee_amount, currency, status, created_by)
             VALUES (:code,:title,:slug,:category_id,:description,:duration_weeks,:delivery_mode,:fee_amount,:currency,:status,:created_by)',
            $data
        );
        return Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        Database::query(
            'UPDATE programmes SET title=:title, description=:description, duration_weeks=:duration_weeks,
                delivery_mode=:delivery_mode, fee_amount=:fee_amount, category_id=:category_id
             WHERE id=:id',
            $data
        );
    }

    public static function setStatus(int $id, string $status): void
    {
        $publishedAt = $status === 'published' ? ', published_at = NOW()' : '';
        Database::query("UPDATE programmes SET status = :s $publishedAt WHERE id = :id", ['s' => $status, 'id' => $id]);
    }

    public static function setCentres(int $programmeId, array $centreIds): void
    {
        Database::query('DELETE FROM programme_centres WHERE programme_id = :p', ['p' => $programmeId]);
        foreach ($centreIds as $cid) {
            Database::query('INSERT INTO programme_centres (programme_id, centre_id) VALUES (:p,:c)', ['p' => $programmeId, 'c' => (int) $cid]);
        }
    }
}
