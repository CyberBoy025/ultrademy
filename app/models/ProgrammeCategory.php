<?php
declare(strict_types=1);

final class ProgrammeCategory
{
    public static function all(): array
    {
        return Database::all('SELECT * FROM programme_categories ORDER BY name');
    }
}
