<?php
declare(strict_types=1);

/**
 * Completion certificates (README §18, §20).
 *
 * Decision 5 default: certificates are publicly verifiable by serial. That is why the
 * serial is a random, non-sequential token — a guessable serial would let anyone
 * enumerate every graduate, which is a privacy problem dressed up as a feature.
 */
final class Certificate
{
    public static function forUser(int $userId): array
    {
        return Database::all(
            'SELECT ce.*, c.title AS course_title, p.title AS programme_title
             FROM certificates ce
             LEFT JOIN courses c ON c.id = ce.course_id
             LEFT JOIN programmes p ON p.id = ce.programme_id
             WHERE ce.user_id = :u ORDER BY ce.issued_at DESC',
            ['u' => $userId]
        );
    }

    public static function findBySerial(string $serial): ?array
    {
        return Database::one(
            "SELECT ce.*, c.title AS course_title, p.title AS programme_title,
                    CONCAT(pr.first_name,' ',pr.last_name) AS holder_name
             FROM certificates ce
             LEFT JOIN courses c ON c.id = ce.course_id
             LEFT JOIN programmes p ON p.id = ce.programme_id
             LEFT JOIN user_profiles pr ON pr.user_id = ce.user_id
             WHERE ce.serial = :s",
            ['s' => $serial]
        );
    }

    public static function existsForCourse(int $userId, int $courseId): bool
    {
        return Database::one(
            'SELECT 1 FROM certificates WHERE user_id = :u AND course_id = :c AND revoked_at IS NULL',
            ['u' => $userId, 'c' => $courseId]
        ) !== null;
    }

    /** Issues a course certificate if — and only if — every lesson is complete. */
    public static function issueForCourse(int $userId, int $courseId, ?int $enrolmentId): ?int
    {
        if (self::existsForCourse($userId, $courseId)) {
            return null;
        }
        if (!Progress::isCourseComplete($userId, $courseId)) {
            return null;
        }
        $course = Course::find($courseId);
        Database::query(
            'INSERT INTO certificates (serial, user_id, course_id, enrolment_id, title, issued_by)
             VALUES (:serial,:u,:c,:e,:t,:by)',
            [
                'serial' => self::newSerial(),
                'u' => $userId, 'c' => $courseId, 'e' => $enrolmentId,
                't' => 'Certificate of Completion — ' . $course['title'],
                'by' => Auth::id(),
            ]
        );
        return Database::lastInsertId();
    }

    public static function revoke(int $id): void
    {
        Database::query('UPDATE certificates SET revoked_at = NOW() WHERE id = :id', ['id' => $id]);
    }

    private static function newSerial(): string
    {
        // UD-CERT-<year>-<random>; random, not sequential, so serials cannot be enumerated.
        return sprintf('UD-CERT-%s-%s', date('Y'), strtoupper(bin2hex(random_bytes(5))));
    }
}
