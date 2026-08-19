<?php
declare(strict_types=1);

/** Assignments and their submissions (README §23). */
final class Assignment
{
    public const SUBDIR = 'submissions';
    private const MAX_BYTES = 20 * 1024 * 1024;

    public static function forCourse(int $courseId, bool $publishedOnly = false): array
    {
        $sql = 'SELECT a.*, (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id) AS submission_count,
                       (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.status = \'submitted\') AS ungraded_count
                FROM assignments a WHERE a.course_id = :c';
        if ($publishedOnly) {
            $sql .= " AND a.status = 'published'";
        }
        return Database::all($sql . ' ORDER BY a.due_at IS NULL, a.due_at, a.id', ['c' => $courseId]);
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT a.*, c.title AS course_title FROM assignments a JOIN courses c ON c.id = a.course_id WHERE a.id = :id',
            ['id' => $id]
        );
    }

    public static function create(int $courseId, array $data): int
    {
        Database::query(
            'INSERT INTO assignments (course_id, title, instructions, due_at, max_score, allows_file, allows_text, allows_resubmission, status, created_by)
             VALUES (:c,:title,:instructions,:due,:max,:af,:at,:ar,:status,:by)',
            [
                'c' => $courseId, 'title' => $data['title'], 'instructions' => $data['instructions'],
                'due' => $data['due_at'], 'max' => $data['max_score'],
                'af' => $data['allows_file'], 'at' => $data['allows_text'], 'ar' => $data['allows_resubmission'],
                'status' => $data['status'], 'by' => Auth::id(),
            ]
        );
        return Database::lastInsertId();
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::query('UPDATE assignments SET status = :s WHERE id = :id', ['s' => $status, 'id' => $id]);
    }

    // ------------------------------------------------------------- submissions

    public static function submissionsFor(int $assignmentId): array
    {
        return Database::all(
            "SELECT s.*, CONCAT(p.first_name,' ',p.last_name) AS student_name, u.email, e.student_no
             FROM assignment_submissions s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             LEFT JOIN enrolments e ON e.id = s.enrolment_id
             WHERE s.assignment_id = :a ORDER BY s.submitted_at",
            ['a' => $assignmentId]
        );
    }

    public static function submissionFor(int $assignmentId, int $userId): ?array
    {
        return Database::one(
            'SELECT * FROM assignment_submissions WHERE assignment_id = :a AND user_id = :u',
            ['a' => $assignmentId, 'u' => $userId]
        );
    }

    public static function findSubmission(int $id): ?array
    {
        return Database::one(
            "SELECT s.*, a.title AS assignment_title, a.max_score, a.course_id,
                    CONCAT(p.first_name,' ',p.last_name) AS student_name
             FROM assignment_submissions s
             JOIN assignments a ON a.id = s.assignment_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             WHERE s.id = :id",
            ['id' => $id]
        );
    }

    /**
     * Creates or replaces a submission. The unique key on (assignment_id, user_id) means
     * resubmission overwrites rather than accumulating rows a grader would have to
     * disambiguate — so the old file is deleted here to avoid orphaning it on disk.
     *
     * @return string|null error message, or null on success
     */
    public static function submit(int $assignmentId, int $userId, ?int $enrolmentId, string $body, array $file): ?string
    {
        $existing = self::submissionFor($assignmentId, $userId);
        $stored = null;

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $result = Upload::store($file, self::SUBDIR, Upload::MATERIAL_TYPES, self::MAX_BYTES);
            if (is_string($result)) {
                return $result;
            }
            $stored = $result;
            if ($existing && $existing['stored_name']) {
                Upload::delete(self::SUBDIR, $existing['stored_name']);
            }
        }

        if ($body === '' && $stored === null && !$existing) {
            return 'Add some text or attach a file.';
        }

        Database::query(
            "INSERT INTO assignment_submissions
                (assignment_id, user_id, enrolment_id, body, stored_name, original_name, mime_type, size_bytes, status, submitted_at)
             VALUES (:a,:u,:e,:body,:stored,:orig,:mime,:size,'submitted',NOW())
             ON DUPLICATE KEY UPDATE
                body = VALUES(body),
                stored_name   = COALESCE(VALUES(stored_name), stored_name),
                original_name = COALESCE(VALUES(original_name), original_name),
                mime_type     = COALESCE(VALUES(mime_type), mime_type),
                size_bytes    = GREATEST(VALUES(size_bytes), 0),
                status = 'submitted', score = NULL, feedback = NULL, graded_by = NULL, graded_at = NULL,
                submitted_at = NOW()",
            [
                'a' => $assignmentId, 'u' => $userId, 'e' => $enrolmentId, 'body' => $body,
                'stored' => $stored['stored_name'] ?? null,
                'orig' => $stored['original_name'] ?? null,
                'mime' => $stored['mime_type'] ?? null,
                'size' => $stored['size_bytes'] ?? 0,
            ]
        );
        return null;
    }

    public static function grade(int $submissionId, int $score, string $feedback): void
    {
        Database::query(
            "UPDATE assignment_submissions
             SET score = :s, feedback = :f, status = 'graded', graded_by = :by, graded_at = NOW()
             WHERE id = :id",
            ['s' => $score, 'f' => $feedback, 'by' => Auth::id(), 'id' => $submissionId]
        );
    }

    /** Everything awaiting a grade across the courses this instructor teaches. */
    public static function gradingQueue(?array $courseIds): array
    {
        if ($courseIds !== null && empty($courseIds)) {
            return [];
        }
        $sql = "SELECT s.*, a.title AS assignment_title, a.max_score, c.title AS course_title,
                       CONCAT(p.first_name,' ',p.last_name) AS student_name
                FROM assignment_submissions s
                JOIN assignments a ON a.id = s.assignment_id
                JOIN courses c ON c.id = a.course_id
                JOIN users u ON u.id = s.user_id
                LEFT JOIN user_profiles p ON p.user_id = u.id
                WHERE s.status = 'submitted'";
        $params = [];
        if ($courseIds !== null) {
            $ph = implode(',', array_fill(0, count($courseIds), '?'));
            $sql .= " AND a.course_id IN ($ph)";
            $params = array_values($courseIds);
        }
        return Database::query($sql . ' ORDER BY s.submitted_at', $params)->fetchAll();
    }

    /** A student's own submissions with grades — README §20 "view results, view feedback". */
    public static function forUser(int $userId): array
    {
        return Database::all(
            'SELECT s.*, a.title AS assignment_title, a.max_score, a.due_at, c.title AS course_title
             FROM assignment_submissions s
             JOIN assignments a ON a.id = s.assignment_id
             JOIN courses c ON c.id = a.course_id
             WHERE s.user_id = :u ORDER BY s.submitted_at DESC',
            ['u' => $userId]
        );
    }
}
