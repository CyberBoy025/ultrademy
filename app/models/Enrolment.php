<?php
declare(strict_types=1);

final class Enrolment
{
    private const BASE_SELECT = "SELECT e.*,
            p.title AS programme_title,
            co.name AS cohort_name,
            c.name AS centre_name,
            u.email, u.phone,
            CONCAT(pr.first_name, ' ', pr.last_name) AS student_name,
            a.reference AS application_reference
        FROM enrolments e
        JOIN users u ON u.id = e.user_id
        LEFT JOIN user_profiles pr ON pr.user_id = u.id
        JOIN programmes p ON p.id = e.programme_id
        JOIN cohorts co ON co.id = e.cohort_id
        LEFT JOIN centres c ON c.id = e.centre_id
        LEFT JOIN applications a ON a.id = e.application_id";

    public static function find(int $id): ?array
    {
        $rows = Database::query(self::BASE_SELECT . ' WHERE e.id = ?', [$id])->fetchAll();
        return $rows[0] ?? null;
    }

    public static function forCohort(int $cohortId): array
    {
        return Database::query(
            self::BASE_SELECT . ' WHERE e.cohort_id = ? ORDER BY pr.first_name',
            [$cohortId]
        )->fetchAll();
    }

    public static function forUser(int $userId): array
    {
        return Database::query(
            self::BASE_SELECT . ' WHERE e.user_id = ? ORDER BY e.enrolled_at DESC',
            [$userId]
        )->fetchAll();
    }

    /**
     * Student roster, centre-scoped.
     * @param array<int,int>|null $centreIds null = GLOBAL, [] = nothing visible
     */
    public static function roster(?array $centreIds, ?string $status = null): array
    {
        $where = [];
        $params = [];
        if ($centreIds !== null) {
            if (empty($centreIds)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $where[] = "e.centre_id IN ($ph)";
            array_push($params, ...array_values($centreIds));
        }
        if ($status !== null && $status !== '') {
            $where[] = 'e.status = ?';
            $params[] = $status;
        }
        $sql = self::BASE_SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY e.enrolled_at DESC';
        return Database::query($sql, $params)->fetchAll();
    }

    public static function count(): int
    {
        return (int) Database::one("SELECT COUNT(*) c FROM enrolments WHERE status='active'")['c'];
    }

    public static function existsFor(int $userId, int $cohortId): bool
    {
        return Database::one(
            "SELECT 1 FROM enrolments WHERE user_id = :u AND cohort_id = :c AND status NOT IN ('withdrawn') LIMIT 1",
            ['u' => $userId, 'c' => $cohortId]
        ) !== null;
    }

    /**
     * Admits a person into a cohort.
     *
     * Decision 3 default (application fee on admission) means the enrolment starts at
     * `pending_payment`: the place is held, but nothing is paid yet. Phase 9 will raise
     * the invoice here and flip it to `active` on payment; until then staff activate it
     * manually, the same bridge used for subscriptions in Phase 6.
     *
     * Decision 7 default (a student may hold enrolments at two centres at once) is why
     * nothing here checks for an existing enrolment at a different centre.
     */
    public static function create(int $userId, int $cohortId, ?int $applicationId, string $status = 'pending_payment'): int
    {
        $cohort = Cohort::find($cohortId);
        if (!$cohort) {
            throw new RuntimeException("Cohort $cohortId not found");
        }

        Database::query(
            'INSERT INTO enrolments (student_no, user_id, programme_id, cohort_id, centre_id, application_id, status)
             VALUES (:no,:u,:p,:co,:ce,:app,:st)',
            [
                'no' => self::nextStudentNo(),
                'u' => $userId,
                'p' => (int) $cohort['programme_id'],
                'co' => $cohortId,
                // centre_id is denormalised from the cohort at enrolment time and never
                // edited afterwards (02-data-model.md §4) — a transfer creates a new row.
                'ce' => $cohort['centre_id'] !== null ? (int) $cohort['centre_id'] : null,
                'app' => $applicationId,
                'st' => $status,
            ]
        );
        $id = Database::lastInsertId();

        // "An applicant becomes a student" without a second account (README §10).
        Role::grantByCode($userId, 'student');

        return $id;
    }

    public static function setStatus(int $id, string $status): void
    {
        $completedAt = $status === 'completed' ? ', completed_at = NOW()' : '';
        Database::query("UPDATE enrolments SET status = :s $completedAt WHERE id = :id", ['s' => $status, 'id' => $id]);
    }

    /**
     * Centre transfer (README §14 "transfer between centres ... maintain historical centre
     * records"). The old row is closed rather than edited, so attendance and finance
     * history stay attached to the centre where they actually happened.
     */
    public static function transfer(int $enrolmentId, int $newCohortId): int
    {
        $old = self::find($enrolmentId);
        if (!$old) {
            throw new RuntimeException("Enrolment $enrolmentId not found");
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Database::query("UPDATE enrolments SET status = 'withdrawn' WHERE id = :id", ['id' => $enrolmentId]);
            $newId = self::create((int) $old['user_id'], $newCohortId, null, $old['status'] === 'active' ? 'active' : 'pending_payment');
            $pdo->commit();
            return $newId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function nextStudentNo(): string
    {
        $year = date('Y');
        $row = Database::one(
            'SELECT COUNT(*) c FROM enrolments WHERE student_no LIKE :like',
            ['like' => "UD-$year-%"]
        );
        return sprintf('UD-%s-%04d', $year, ((int) $row['c']) + 1);
    }
}
