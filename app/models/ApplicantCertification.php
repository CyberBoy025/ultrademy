<?php
declare(strict_types=1);

final class ApplicantCertification
{
    private const SUBDIR = 'recruitment';
    private const MAX_BYTES = 5 * 1024 * 1024;

    public static function forUser(int $userId): array
    {
        return Database::all('SELECT * FROM applicant_certifications WHERE user_id = :u ORDER BY issued_on DESC', ['u' => $userId]);
    }

    /** @param array|null $file one entry from $_FILES, or null if no document was attached */
    public static function create(int $userId, string $name, string $issuer, ?string $issuedOn, ?string $expiresOn, ?array $file): string|int
    {
        $stored = null;
        $original = null;
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $result = Upload::store($file, self::SUBDIR, Upload::DOCUMENT_TYPES, self::MAX_BYTES);
            if (is_string($result)) {
                return $result;
            }
            $stored = $result['stored_name'];
            $original = $result['original_name'];
        }

        Database::query(
            'INSERT INTO applicant_certifications (user_id, name, issuing_organisation, issued_on, expires_on, stored_name, original_name)
             VALUES (:u,:n,:iss,:ion,:eon,:st,:orig)',
            ['u' => $userId, 'n' => $name, 'iss' => $issuer ?: null, 'ion' => $issuedOn ?: null, 'eon' => $expiresOn ?: null, 'st' => $stored, 'orig' => $original]
        );
        return Database::lastInsertId();
    }

    public static function delete(int $id, int $userId): void
    {
        $row = Database::one('SELECT * FROM applicant_certifications WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => $userId]);
        if (!$row) {
            return;
        }
        if ($row['stored_name']) {
            Upload::delete(self::SUBDIR, $row['stored_name']);
        }
        Database::query('DELETE FROM applicant_certifications WHERE id = :id', ['id' => $id]);
    }
}
