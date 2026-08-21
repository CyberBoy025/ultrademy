<?php
declare(strict_types=1);

/**
 * Quizzes and examinations — README §18-§20, the assessment half of the LMS.
 *
 * Three rules this class exists to hold:
 *
 *   1. Correct answers never leave the server before grading. `questionsForTaking()`
 *      is the only query the take-page may use, and it does not select `is_correct`.
 *   2. The clock is server-side. `started_at` plus the assessment's duration decides
 *      whether a submission is late; the browser countdown is decoration.
 *   3. A graded attempt is immutable history. `max_points` is snapshotted at submission
 *      so editing the paper afterwards cannot rewrite marks already awarded.
 */
final class Assessment
{
    public const TYPES = ['quiz' => 'Quiz', 'exam' => 'Examination'];

    public const QUESTION_TYPES = [
        'single_choice' => 'Single choice',
        'multi_choice'  => 'Multiple choice',
        'true_false'    => 'True / false',
        'short_text'    => 'Short answer',
        'essay'         => 'Essay (marked by hand)',
    ];

    /** Question types the server can mark without a human. */
    private const AUTO_TYPES = ['single_choice', 'multi_choice', 'true_false', 'short_text'];

    // ----------------------------------------------------------------- assessments

    public static function forCourse(int $courseId, bool $publishedOnly = false): array
    {
        $sql = 'SELECT a.*,
                       (SELECT COUNT(*) FROM assessment_questions q WHERE q.assessment_id = a.id) AS question_count,
                       (SELECT COALESCE(SUM(q.points),0) FROM assessment_questions q WHERE q.assessment_id = a.id) AS max_points,
                       (SELECT COUNT(*) FROM assessment_attempts t WHERE t.assessment_id = a.id AND t.status <> \'in_progress\') AS attempt_count,
                       (SELECT COUNT(*) FROM assessment_attempts t WHERE t.assessment_id = a.id AND t.status = \'submitted\' AND t.needs_manual_grade = 1) AS awaiting_marking
                FROM assessments a WHERE a.course_id = :c';
        if ($publishedOnly) {
            $sql .= " AND a.status = 'published'";
        }
        return Database::all($sql . ' ORDER BY a.opens_at IS NULL, a.opens_at, a.id', ['c' => $courseId]);
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT a.*, c.title AS course_title, m.title AS module_title,
                    (SELECT COALESCE(SUM(q.points),0) FROM assessment_questions q WHERE q.assessment_id = a.id) AS max_points,
                    (SELECT COUNT(*) FROM assessment_questions q WHERE q.assessment_id = a.id) AS question_count
             FROM assessments a
             JOIN courses c ON c.id = a.course_id
             LEFT JOIN modules m ON m.id = a.module_id
             WHERE a.id = :id',
            ['id' => $id]
        );
    }

    public static function create(int $courseId, array $d): int
    {
        Database::query(
            'INSERT INTO assessments
                (course_id, module_id, title, instructions, type, opens_at, closes_at, duration_minutes,
                 max_attempts, pass_mark, shuffle_questions, show_results, status, created_by)
             VALUES (:c,:m,:t,:i,:type,:opens,:closes,:dur,:att,:pass,:shuf,:show,:status,:by)',
            [
                'c' => $courseId,
                'm' => $d['module_id'] ?: null,
                't' => $d['title'],
                'i' => $d['instructions'] ?: null,
                'type' => $d['type'],
                'opens' => $d['opens_at'] ?: null,
                'closes' => $d['closes_at'] ?: null,
                'dur' => $d['duration_minutes'] ?: null,
                'att' => $d['max_attempts'],
                'pass' => $d['pass_mark'],
                'shuf' => $d['shuffle_questions'],
                'show' => $d['show_results'],
                'status' => $d['status'] ?? 'draft',
                'by' => Auth::id(),
            ]
        );
        return Database::lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE assessments SET module_id=:m, title=:t, instructions=:i, type=:type,
                    opens_at=:opens, closes_at=:closes, duration_minutes=:dur, max_attempts=:att,
                    pass_mark=:pass, shuffle_questions=:shuf, show_results=:show
             WHERE id=:id',
            [
                'm' => $d['module_id'] ?: null, 't' => $d['title'], 'i' => $d['instructions'] ?: null,
                'type' => $d['type'], 'opens' => $d['opens_at'] ?: null, 'closes' => $d['closes_at'] ?: null,
                'dur' => $d['duration_minutes'] ?: null, 'att' => $d['max_attempts'], 'pass' => $d['pass_mark'],
                'shuf' => $d['shuffle_questions'], 'show' => $d['show_results'], 'id' => $id,
            ]
        );
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::query('UPDATE assessments SET status = :s WHERE id = :id', ['s' => $status, 'id' => $id]);
    }

    /**
     * Publishing is refused for a paper nobody could pass — an assessment with no
     * questions, or one whose questions are all worth zero. Better to block it here than
     * to let a student sit a paper that divides by zero.
     *
     * @return string|null error message, or null if publishing is allowed
     */
    public static function publishBlocker(int $id): ?string
    {
        $a = self::find($id);
        if (!$a) {
            return 'Assessment not found.';
        }
        if ((int) $a['question_count'] === 0) {
            return 'Add at least one question before publishing.';
        }
        if ((int) $a['max_points'] === 0) {
            return 'Every question is worth zero points — the paper cannot be marked.';
        }
        return null;
    }

    /** Open for sitting right now? Status plus the opens/closes window. */
    public static function isOpen(array $a): bool
    {
        if ($a['status'] !== 'published') {
            return false;
        }
        $now = time();
        if ($a['opens_at'] && strtotime((string) $a['opens_at']) > $now) {
            return false;
        }
        if ($a['closes_at'] && strtotime((string) $a['closes_at']) < $now) {
            return false;
        }
        return true;
    }

    /** Human-readable reason an assessment cannot be started, or null if it can. */
    public static function closedReason(array $a): ?string
    {
        if ($a['status'] === 'draft') {
            return 'Not yet published.';
        }
        if ($a['status'] === 'closed') {
            return 'This assessment has been closed.';
        }
        if ($a['opens_at'] && strtotime((string) $a['opens_at']) > time()) {
            return 'Opens ' . date('d M Y H:i', strtotime((string) $a['opens_at'])) . '.';
        }
        if ($a['closes_at'] && strtotime((string) $a['closes_at']) < time()) {
            return 'Closed ' . date('d M Y H:i', strtotime((string) $a['closes_at'])) . '.';
        }
        return null;
    }

    // ------------------------------------------------------------------- questions

    /** Authoring view — includes `is_correct`. Never render this to a student. */
    public static function questions(int $assessmentId): array
    {
        $questions = Database::all(
            'SELECT * FROM assessment_questions WHERE assessment_id = :a ORDER BY sort_order, id',
            ['a' => $assessmentId]
        );
        foreach ($questions as &$q) {
            $q['options'] = Database::all(
                'SELECT * FROM assessment_options WHERE question_id = :q ORDER BY sort_order, id',
                ['q' => (int) $q['id']]
            );
        }
        return $questions;
    }

    /**
     * Sitting view. Selects everything a candidate needs and nothing they must not see:
     * `is_correct`, `expected_answer` and `explanation` are all absent by construction,
     * not filtered out afterwards where a later edit could reintroduce them.
     */
    public static function questionsForTaking(int $assessmentId, bool $shuffle = false): array
    {
        $questions = Database::all(
            'SELECT id, assessment_id, type, prompt, points, sort_order
             FROM assessment_questions WHERE assessment_id = :a ORDER BY sort_order, id',
            ['a' => $assessmentId]
        );
        foreach ($questions as &$q) {
            $q['options'] = Database::all(
                'SELECT id, label FROM assessment_options WHERE question_id = :q ORDER BY sort_order, id',
                ['q' => (int) $q['id']]
            );
        }
        unset($q);
        if ($shuffle) {
            shuffle($questions);
        }
        return $questions;
    }

    public static function findQuestion(int $id): ?array
    {
        return Database::one('SELECT * FROM assessment_questions WHERE id = :id', ['id' => $id]);
    }

    public static function addQuestion(int $assessmentId, array $d): int
    {
        $next = (int) (Database::one(
            'SELECT COALESCE(MAX(sort_order),0) + 1 AS n FROM assessment_questions WHERE assessment_id = :a',
            ['a' => $assessmentId]
        )['n'] ?? 1);

        Database::query(
            'INSERT INTO assessment_questions (assessment_id, type, prompt, points, expected_answer, explanation, sort_order)
             VALUES (:a,:t,:p,:pts,:exp,:why,:o)',
            [
                'a' => $assessmentId, 't' => $d['type'], 'p' => $d['prompt'],
                'pts' => $d['points'], 'exp' => $d['expected_answer'] ?: null,
                'why' => $d['explanation'] ?: null, 'o' => $next,
            ]
        );
        $questionId = Database::lastInsertId();

        // True/false is a two-option single-choice question. Generating the options here
        // keeps grading uniform — one code path for every choice question.
        if ($d['type'] === 'true_false') {
            $correct = ($d['expected_answer'] ?? 'true') === 'true';
            self::addOption($questionId, 'True', $correct, 1);
            self::addOption($questionId, 'False', !$correct, 2);
        }
        return $questionId;
    }

    public static function deleteQuestion(int $id): void
    {
        Database::query('DELETE FROM assessment_questions WHERE id = :id', ['id' => $id]);
    }

    public static function addOption(int $questionId, string $label, bool $isCorrect, ?int $sort = null): int
    {
        $sort ??= (int) (Database::one(
            'SELECT COALESCE(MAX(sort_order),0) + 1 AS n FROM assessment_options WHERE question_id = :q',
            ['q' => $questionId]
        )['n'] ?? 1);

        Database::query(
            'INSERT INTO assessment_options (question_id, label, is_correct, sort_order) VALUES (:q,:l,:c,:o)',
            ['q' => $questionId, 'l' => $label, 'c' => $isCorrect ? 1 : 0, 'o' => $sort]
        );
        return Database::lastInsertId();
    }

    public static function deleteOption(int $id): void
    {
        Database::query('DELETE FROM assessment_options WHERE id = :id', ['id' => $id]);
    }

    public static function maxPoints(int $assessmentId): int
    {
        return (int) (Database::one(
            'SELECT COALESCE(SUM(points),0) AS p FROM assessment_questions WHERE assessment_id = :a',
            ['a' => $assessmentId]
        )['p'] ?? 0);
    }

    // -------------------------------------------------------------------- attempts

    public static function attemptsForUser(int $assessmentId, int $userId): array
    {
        return Database::all(
            'SELECT * FROM assessment_attempts WHERE assessment_id = :a AND user_id = :u ORDER BY attempt_no',
            ['a' => $assessmentId, 'u' => $userId]
        );
    }

    public static function openAttempt(int $assessmentId, int $userId): ?array
    {
        return Database::one(
            "SELECT * FROM assessment_attempts
             WHERE assessment_id = :a AND user_id = :u AND status = 'in_progress'
             ORDER BY attempt_no DESC LIMIT 1",
            ['a' => $assessmentId, 'u' => $userId]
        );
    }

    public static function findAttempt(int $id): ?array
    {
        return Database::one(
            'SELECT t.*, a.title, a.course_id, a.pass_mark, a.duration_minutes, a.show_results,
                    a.closes_at, a.type, a.shuffle_questions,
                    CONCAT(p.first_name," ",p.last_name) AS student_name, u.email
             FROM assessment_attempts t
             JOIN assessments a ON a.id = t.assessment_id
             JOIN users u ON u.id = t.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             WHERE t.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Begins a sitting, or resumes one already in progress.
     *
     * Wrapped in a transaction because two tabs submitting at once would otherwise both
     * read the same attempt count and both insert `attempt_no = 2`. The unique key would
     * reject the loser with a raw SQL error; taking the lock here means the second call
     * simply resumes the first one's attempt.
     *
     * @return array{0:?array,1:?string} [attempt, error]
     */
    public static function startAttempt(int $assessmentId, int $userId, ?int $enrolmentId): array
    {
        $assessment = self::find($assessmentId);
        if (!$assessment) {
            return [null, 'Assessment not found.'];
        }
        if (!self::isOpen($assessment)) {
            return [null, self::closedReason($assessment) ?? 'This assessment is not open.'];
        }
        if ((int) $assessment['question_count'] === 0) {
            return [null, 'This assessment has no questions yet.'];
        }

        return Database::transaction(function () use ($assessmentId, $userId, $enrolmentId, $assessment) {
            $existing = self::openAttempt($assessmentId, $userId);
            if ($existing) {
                // An in-progress attempt whose clock has run out is closed and marked,
                // rather than handed back for an untimed continuation.
                if (self::hasExpired($existing, $assessment)) {
                    self::finalise((int) $existing['id'], true);
                    return [null, 'Your previous attempt ran out of time and has been submitted.'];
                }
                return [$existing, null];
            }

            $used = (int) (Database::one(
                "SELECT COUNT(*) AS n FROM assessment_attempts
                 WHERE assessment_id = :a AND user_id = :u AND status <> 'expired'",
                ['a' => $assessmentId, 'u' => $userId]
            )['n'] ?? 0);

            $max = (int) $assessment['max_attempts'];
            if ($max > 0 && $used >= $max) {
                return [null, 'You have used all ' . $max . ' permitted attempt(s).'];
            }

            Database::query(
                'INSERT INTO assessment_attempts (assessment_id, user_id, enrolment_id, attempt_no, status, started_at)
                 VALUES (:a,:u,:e,:n,\'in_progress\',NOW())',
                ['a' => $assessmentId, 'u' => $userId, 'e' => $enrolmentId, 'n' => $used + 1]
            );
            $id = Database::lastInsertId();
            return [self::findAttempt($id), null];
        });
    }

    /** Server-side clock. A timed attempt is over when started_at + duration has passed. */
    public static function hasExpired(array $attempt, ?array $assessment = null): bool
    {
        $assessment ??= self::find((int) $attempt['assessment_id']);
        $duration = $assessment['duration_minutes'] ?? null;
        if (!$duration) {
            return false;
        }
        return strtotime((string) $attempt['started_at']) + ((int) $duration * 60) < time();
    }

    /** Seconds left, or null when untimed. Never negative. */
    public static function secondsRemaining(array $attempt, array $assessment): ?int
    {
        if (empty($assessment['duration_minutes'])) {
            return null;
        }
        $end = strtotime((string) $attempt['started_at']) + ((int) $assessment['duration_minutes'] * 60);
        return max(0, $end - time());
    }

    /**
     * Records the candidate's responses and marks everything that can be marked.
     *
     * $responses is keyed by question id:
     *   choice questions => array of option ids
     *   text questions   => string
     */
    public static function submit(int $attemptId, array $responses): void
    {
        Database::transaction(function () use ($attemptId, $responses) {
            $attempt = Database::one('SELECT * FROM assessment_attempts WHERE id = :id FOR UPDATE', ['id' => $attemptId]);
            if (!$attempt || $attempt['status'] !== 'in_progress') {
                return; // already submitted — a double-posted form must not re-grade
            }
            $questions = self::questions((int) $attempt['assessment_id']);

            foreach ($questions as $q) {
                $qid = (int) $q['id'];
                $given = $responses[$qid] ?? null;
                $selected = [];
                $text = null;

                if (in_array($q['type'], ['single_choice', 'multi_choice', 'true_false'], true)) {
                    $selected = array_values(array_map('intval', (array) ($given ?? [])));
                } else {
                    $text = is_string($given) ? trim($given) : null;
                }

                [$awarded, $isCorrect] = self::markAnswer($q, $selected, $text);

                Database::query(
                    'INSERT INTO assessment_answers (attempt_id, question_id, selected_options, response_text, awarded_points, is_correct)
                     VALUES (:t,:q,:sel,:txt,:pts,:ok)
                     ON DUPLICATE KEY UPDATE selected_options=VALUES(selected_options), response_text=VALUES(response_text),
                        awarded_points=VALUES(awarded_points), is_correct=VALUES(is_correct)',
                    [
                        't' => $attemptId, 'q' => $qid,
                        'sel' => $selected ? json_encode($selected) : null,
                        'txt' => ($text === '' ? null : $text),
                        'pts' => $awarded, 'ok' => $isCorrect,
                    ]
                );
            }

            Database::query(
                "UPDATE assessment_attempts
                 SET status = 'submitted', submitted_at = NOW(),
                     time_spent_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW())
                 WHERE id = :id",
                ['id' => $attemptId]
            );
            self::finalise($attemptId);
        });
    }

    /**
     * Marks one answer.
     *
     * Choice questions are all-or-nothing: every correct option selected and no incorrect
     * one. Partial credit sounds generous but needs a documented scheme (and a defensible
     * one — negative marking? proportional?) before it can be fair, so it is deliberately
     * left out rather than guessed at.
     *
     * @return array{0:?int,1:?int} [awarded points, is_correct] — both null for essays
     */
    private static function markAnswer(array $q, array $selected, ?string $text): array
    {
        $points = (int) $q['points'];

        switch ($q['type']) {
            case 'single_choice':
            case 'true_false':
            case 'multi_choice':
                $correctIds = [];
                foreach ($q['options'] as $o) {
                    if ((int) $o['is_correct'] === 1) {
                        $correctIds[] = (int) $o['id'];
                    }
                }
                sort($correctIds);
                $chosen = array_values(array_unique($selected));
                sort($chosen);
                $ok = $correctIds !== [] && $chosen === $correctIds;
                return [$ok ? $points : 0, $ok ? 1 : 0];

            case 'short_text':
                $expected = (string) ($q['expected_answer'] ?? '');
                if ($expected === '' || $text === null || $text === '') {
                    return [0, 0];
                }
                $normalise = static fn(string $s): string => preg_replace('/\s+/u', ' ', trim(mb_strtolower($s)));
                $given = $normalise($text);
                foreach (explode('|', $expected) as $alt) {
                    if ($normalise($alt) === $given) {
                        return [$points, 1];
                    }
                }
                return [0, 0];

            case 'essay':
            default:
                return [null, null]; // awaits a human
        }
    }

    /**
     * Totals an attempt and decides pass/fail.
     *
     * An attempt containing an ungraded essay stays `submitted` with
     * needs_manual_grade = 1 — it is NOT given a provisional percentage, because a
     * student shown "42%" that later becomes "78%" has been misinformed, not informed.
     */
    public static function finalise(int $attemptId, bool $expired = false): void
    {
        $attempt = Database::one('SELECT * FROM assessment_attempts WHERE id = :id', ['id' => $attemptId]);
        if (!$attempt) {
            return;
        }
        $assessmentId = (int) $attempt['assessment_id'];
        $maxPoints = self::maxPoints($assessmentId);

        $pending = (int) (Database::one(
            'SELECT COUNT(*) AS n FROM assessment_answers a
             JOIN assessment_questions q ON q.id = a.question_id
             WHERE a.attempt_id = :t AND q.type = :essay AND a.awarded_points IS NULL',
            ['t' => $attemptId, 'essay' => 'essay']
        )['n'] ?? 0);

        $awarded = (int) (Database::one(
            'SELECT COALESCE(SUM(awarded_points),0) AS p FROM assessment_answers WHERE attempt_id = :t',
            ['t' => $attemptId]
        )['p'] ?? 0);

        if ($pending > 0) {
            Database::query(
                "UPDATE assessment_attempts
                 SET status = 'submitted', needs_manual_grade = 1, max_points = :max,
                     submitted_at = COALESCE(submitted_at, NOW()),
                     time_spent_seconds = COALESCE(time_spent_seconds, TIMESTAMPDIFF(SECOND, started_at, NOW()))
                 WHERE id = :id",
                ['max' => $maxPoints, 'id' => $attemptId]
            );
            return;
        }

        $percent = $maxPoints > 0 ? round($awarded * 100 / $maxPoints, 2) : 0.0;
        $passMark = (int) (Database::one('SELECT pass_mark FROM assessments WHERE id = :a', ['a' => $assessmentId])['pass_mark'] ?? 50);

        Database::query(
            "UPDATE assessment_attempts
             SET status = 'graded', needs_manual_grade = 0,
                 score_points = :pts, max_points = :max, score_percent = :pct,
                 passed = :passed, graded_at = NOW(),
                 submitted_at = COALESCE(submitted_at, NOW()),
                 time_spent_seconds = COALESCE(time_spent_seconds, TIMESTAMPDIFF(SECOND, started_at, NOW()))
             WHERE id = :id",
            [
                'pts' => $awarded, 'max' => $maxPoints, 'pct' => $percent,
                'passed' => $percent >= $passMark ? 1 : 0, 'id' => $attemptId,
            ]
        );
    }

    // ------------------------------------------------------------- manual marking

    public static function answersFor(int $attemptId, bool $withCorrect = false): array
    {
        $rows = Database::all(
            'SELECT a.*, q.type, q.prompt, q.points, q.expected_answer, q.explanation, q.sort_order
             FROM assessment_answers a
             JOIN assessment_questions q ON q.id = a.question_id
             WHERE a.attempt_id = :t ORDER BY q.sort_order, q.id',
            ['t' => $attemptId]
        );
        foreach ($rows as &$r) {
            $r['selected'] = $r['selected_options'] ? (array) json_decode((string) $r['selected_options'], true) : [];
            $cols = $withCorrect ? 'id, label, is_correct' : 'id, label';
            $r['options'] = Database::all(
                "SELECT $cols FROM assessment_options WHERE question_id = :q ORDER BY sort_order, id",
                ['q' => (int) $r['question_id']]
            );
        }
        return $rows;
    }

    public static function gradeAnswer(int $answerId, int $points, string $feedback): void
    {
        Database::query(
            'UPDATE assessment_answers
             SET awarded_points = :p, feedback = :f, graded_by = :by, graded_at = NOW()
             WHERE id = :id',
            ['p' => $points, 'f' => $feedback !== '' ? $feedback : null, 'by' => Auth::id(), 'id' => $answerId]
        );
    }

    /** Attempts waiting on a human, limited to the courses an instructor actually teaches. */
    public static function markingQueue(?array $courseIds): array
    {
        if ($courseIds !== null && $courseIds === []) {
            return [];
        }
        $sql = "SELECT t.*, a.title AS assessment_title, a.pass_mark, c.title AS course_title,
                       CONCAT(p.first_name,' ',p.last_name) AS student_name
                FROM assessment_attempts t
                JOIN assessments a ON a.id = t.assessment_id
                JOIN courses c ON c.id = a.course_id
                JOIN users u ON u.id = t.user_id
                LEFT JOIN user_profiles p ON p.user_id = u.id
                WHERE t.status = 'submitted' AND t.needs_manual_grade = 1";
        $params = [];
        if ($courseIds !== null) {
            $ph = implode(',', array_fill(0, count($courseIds), '?'));
            $sql .= " AND a.course_id IN ($ph)";
            $params = array_values($courseIds);
        }
        return Database::query($sql . ' ORDER BY t.submitted_at', $params)->fetchAll();
    }

    public static function attemptsFor(int $assessmentId): array
    {
        return Database::all(
            "SELECT t.*, CONCAT(p.first_name,' ',p.last_name) AS student_name, u.email, e.student_no
             FROM assessment_attempts t
             JOIN users u ON u.id = t.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             LEFT JOIN enrolments e ON e.id = t.enrolment_id
             WHERE t.assessment_id = :a AND t.status <> 'in_progress'
             ORDER BY t.submitted_at DESC",
            ['a' => $assessmentId]
        );
    }

    /** A learner's own results across every assessment — the §20 "view results" surface. */
    public static function resultsForUser(int $userId): array
    {
        return Database::all(
            "SELECT t.*, a.title AS assessment_title, a.pass_mark, a.show_results, a.closes_at,
                    c.title AS course_title, c.id AS course_id
             FROM assessment_attempts t
             JOIN assessments a ON a.id = t.assessment_id
             JOIN courses c ON c.id = a.course_id
             WHERE t.user_id = :u AND t.status <> 'in_progress'
             ORDER BY t.submitted_at DESC",
            ['u' => $userId]
        );
    }

    /**
     * May this candidate see their mark yet?
     *
     * `after_close` exists so a whole cohort sitting the same exam cannot leak answers to
     * each other by comparing results while the paper is still open.
     */
    public static function resultsVisible(array $assessment, array $attempt): bool
    {
        if ($attempt['status'] !== 'graded') {
            return false;
        }
        return match ($assessment['show_results']) {
            'immediately' => true,
            'never'       => false,
            'after_close' => !empty($assessment['closes_at']) && strtotime((string) $assessment['closes_at']) < time(),
            default       => false,
        };
    }

    /** Best graded percentage a learner has achieved, or null if never graded. */
    public static function bestPercent(int $assessmentId, int $userId): ?float
    {
        $row = Database::one(
            "SELECT MAX(score_percent) AS best FROM assessment_attempts
             WHERE assessment_id = :a AND user_id = :u AND status = 'graded'",
            ['a' => $assessmentId, 'u' => $userId]
        );
        return isset($row['best']) && $row['best'] !== null ? (float) $row['best'] : null;
    }
}
