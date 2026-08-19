<?php
declare(strict_types=1);

/**
 * Programme applications (README §10, §11; 02-data-model.md §4).
 *
 * Status flow, matching README §34 exactly:
 *   draft → submitted → under_review → approved | rejected
 * with `withdrawn` reachable by the applicant while it is still open.
 *
 * There is no `applicants` table by design (02-data-model.md §12): an applicant is a
 * user with an open application, so this table IS the applicant relationship.
 */
final class Application
{
    private const BASE_SELECT = "SELECT a.*,
            p.title AS programme_title, p.slug AS programme_slug, p.delivery_mode,
            c.name AS centre_name,
            co.name AS cohort_name,
            u.email,
            CONCAT(pr.first_name, ' ', pr.last_name) AS applicant_name,
            pr.first_name, pr.last_name, u.phone,
            CONCAT(rv.first_name, ' ', rv.last_name) AS reviewer_name
        FROM applications a
        JOIN programmes p ON p.id = a.programme_id
        JOIN users u ON u.id = a.user_id
        LEFT JOIN user_profiles pr ON pr.user_id = u.id
        LEFT JOIN centres c ON c.id = a.preferred_centre_id
        LEFT JOIN cohorts co ON co.id = a.cohort_id
        LEFT JOIN user_profiles rv ON rv.user_id = a.reviewed_by";

    /** Statuses that still count as "open" — used for the entitlement meter and role revocation. */
    public const OPEN_STATUSES = ['draft', 'submitted', 'under_review'];

    public static function find(int $id): ?array
    {
        $rows = Database::query(self::BASE_SELECT . ' WHERE a.id = ?', [$id])->fetchAll();
        return $rows[0] ?? null;
    }

    public static function forUser(int $userId): array
    {
        return Database::query(self::BASE_SELECT . ' WHERE a.user_id = ? ORDER BY a.created_at DESC', [$userId])->fetchAll();
    }

    public static function countOpenForUser(int $userId): int
    {
        $ph = implode(',', array_fill(0, count(self::OPEN_STATUSES), '?'));
        return (int) Database::query(
            "SELECT COUNT(*) c FROM applications WHERE user_id = ? AND status IN ($ph)",
            array_merge([$userId], self::OPEN_STATUSES)
        )->fetch()['c'];
    }

    /**
     * Staff listing, centre-scoped.
     * @param array<int,int>|null $centreIds null = GLOBAL, [] = nothing visible
     */
    public static function queue(?array $centreIds, ?string $status = null): array
    {
        $where = [];
        $params = [];

        if ($centreIds !== null) {
            if (empty($centreIds)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            // Decision 8 default (a centre manager does not see online-only records) is why
            // this is a plain IN and not "IN (...) OR preferred_centre_id IS NULL".
            $where[] = "a.preferred_centre_id IN ($ph)";
            array_push($params, ...array_values($centreIds));
        }
        if ($status !== null && $status !== '') {
            $where[] = 'a.status = ?';
            $params[] = $status;
        } else {
            $where[] = "a.status <> 'draft'";  // drafts belong to the applicant, not the queue
        }

        $sql = self::BASE_SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY a.submitted_at IS NULL, a.submitted_at ASC';
        return Database::query($sql, $params)->fetchAll();
    }

    /** @param array<int,int>|null $centreIds */
    public static function statusCounts(?array $centreIds): array
    {
        $sql = "SELECT status, COUNT(*) c FROM applications WHERE status <> 'draft'";
        $params = [];
        if ($centreIds !== null) {
            if (empty($centreIds)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $sql .= " AND preferred_centre_id IN ($ph)";
            $params = array_values($centreIds);
        }
        $sql .= ' GROUP BY status';
        $out = [];
        foreach (Database::query($sql, $params)->fetchAll() as $r) {
            $out[$r['status']] = (int) $r['c'];
        }
        return $out;
    }

    public static function create(int $userId, int $programmeId, ?int $centreId, string $motivation): int
    {
        Database::query(
            'INSERT INTO applications (reference, user_id, programme_id, preferred_centre_id, motivation, status, created_by)
             VALUES (:ref,:u,:p,:c,:m,\'draft\',:by)',
            [
                'ref' => self::nextReference(),
                'u' => $userId, 'p' => $programmeId, 'c' => $centreId,
                'm' => $motivation, 'by' => Auth::id(),
            ]
        );
        return Database::lastInsertId();
    }

    public static function submit(int $id): void
    {
        $app = self::find($id);
        Database::query(
            "UPDATE applications SET status = 'submitted', submitted_at = NOW() WHERE id = :id AND status = 'draft'",
            ['id' => $id]
        );
        // The applicant role exists only while an application is open (03-rbac.md §2).
        Role::grantByCode((int) $app['user_id'], 'applicant');
    }

    public static function setStatus(int $id, string $status, ?string $note = null, ?int $cohortId = null): void
    {
        Database::query(
            'UPDATE applications SET status = :s, decision_note = COALESCE(:n, decision_note),
                    cohort_id = COALESCE(:co, cohort_id), reviewed_by = :by, reviewed_at = NOW()
             WHERE id = :id',
            ['s' => $status, 'n' => $note, 'co' => $cohortId, 'by' => Auth::id(), 'id' => $id]
        );
    }

    public static function withdraw(int $id): void
    {
        Database::query("UPDATE applications SET status = 'withdrawn' WHERE id = :id", ['id' => $id]);
    }

    /**
     * Drops the applicant role once the person has nothing open any more.
     * Called after every terminal transition (approved / rejected / withdrawn).
     */
    public static function syncApplicantRole(int $userId): void
    {
        if (self::countOpenForUser($userId) === 0) {
            Role::revokeByCode($userId, 'applicant');
        }
    }

    private static function nextReference(): string
    {
        // APP-2026-0007 — readable, sortable, and no internal id exposed (README §73).
        $year = date('Y');
        $row = Database::one(
            "SELECT COUNT(*) c FROM applications WHERE reference LIKE :like",
            ['like' => "APP-$year-%"]
        );
        return sprintf('APP-%s-%04d', $year, ((int) $row['c']) + 1);
    }
}
