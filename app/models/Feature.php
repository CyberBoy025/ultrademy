<?php
declare(strict_types=1);

final class Feature
{
    public static function all(): array
    {
        return Database::all('SELECT * FROM features ORDER BY module, name');
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM features WHERE id = :id', ['id' => $id]);
    }

    public static function findByCode(string $code): ?array
    {
        return Database::one('SELECT * FROM features WHERE code = :c', ['c' => $code]);
    }

    /** Human-readable limit. null = unlimited; bytes render as MB/GB. */
    public static function formatLimit(string $limitType, ?int $limit): string
    {
        if ($limit === null) {
            return 'Unlimited';
        }
        if ($limitType === 'bytes') {
            return $limit >= 1073741824
                ? rtrim(rtrim(number_format($limit / 1073741824, 1), '0'), '.') . ' GB'
                : round($limit / 1048576) . ' MB';
        }
        return (string) $limit;
    }

    /** @return array<string,array<int,array<string,mixed>>> module => features */
    public static function groupedByModule(): array
    {
        $out = [];
        foreach (self::all() as $f) {
            $out[$f['module']][] = $f;
        }
        return $out;
    }
}
