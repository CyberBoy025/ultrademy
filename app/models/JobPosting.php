<?php
declare(strict_types=1);

/**
 * Recruitment job postings (docs/architecture/16-careers-portal.md §5).
 *
 * `search()` is the only entry point the public careers site uses to list postings — it
 * hard-codes `status = 'published'`, so a draft, unpublished, or closed posting is never
 * reachable from the public listing regardless of what filters are passed. The detail page
 * uses `findPublishedBySlug()` for the same reason.
 */
final class JobPosting
{
    private const BASE_SELECT = "SELECT jp.*, d.name AS department_name, d.slug AS department_slug,
            jc.name AS category_name, jc.slug AS category_slug
        FROM job_postings jp
        LEFT JOIN departments d ON d.id = jp.department_id
        LEFT JOIN job_categories jc ON jc.id = jp.category_id";

    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract',
        'internship' => 'Internship', 'temporary' => 'Temporary', 'volunteer' => 'Volunteer',
    ];

    public const WORK_MODES = ['onsite' => 'On-site', 'hybrid' => 'Hybrid', 'remote' => 'Remote'];

    public static function find(int $id): ?array
    {
        return Database::one(self::BASE_SELECT . ' WHERE jp.id = :id', ['id' => $id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::one(self::BASE_SELECT . ' WHERE jp.slug = :s', ['s' => $slug]);
    }

    /** Public detail page — only a published posting is visible, regardless of deadline. */
    public static function findPublishedBySlug(string $slug): ?array
    {
        return Database::one(self::BASE_SELECT . " WHERE jp.slug = :s AND jp.status = 'published'", ['s' => $slug]);
    }

    /**
     * Public search/filter (brief §7). Always scoped to published postings — this is the
     * one place that boundary is enforced for the whole careers site.
     *
     * @param array<string,mixed> $filters keyword, department_id, category_id,
     *     employment_type, work_mode, centre_id
     */
    public static function search(array $filters): array
    {
        $where = ["jp.status = 'published'"];
        $params = [];

        if (!empty($filters['keyword'])) {
            $where[] = '(jp.title LIKE :kw OR jp.summary LIKE :kw)';
            $params['kw'] = '%' . $filters['keyword'] . '%';
        }
        if (!empty($filters['department_id'])) {
            $where[] = 'jp.department_id = :dept';
            $params['dept'] = (int) $filters['department_id'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'jp.category_id = :cat';
            $params['cat'] = (int) $filters['category_id'];
        }
        if (!empty($filters['employment_type']) && isset(self::EMPLOYMENT_TYPES[$filters['employment_type']])) {
            $where[] = 'jp.employment_type = :emp';
            $params['emp'] = $filters['employment_type'];
        }
        if (!empty($filters['work_mode']) && isset(self::WORK_MODES[$filters['work_mode']])) {
            $where[] = 'jp.work_mode = :mode';
            $params['mode'] = $filters['work_mode'];
        }
        if (!empty($filters['centre_id'])) {
            $where[] = 'jp.id IN (SELECT job_posting_id FROM job_posting_centres WHERE centre_id = :centre)';
            $params['centre'] = (int) $filters['centre_id'];
        }

        $sql = self::BASE_SELECT . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY jp.published_at IS NULL, jp.published_at DESC';
        return Database::query($sql, $params)->fetchAll();
    }

    /** @return array<int,array<string,mixed>> centres this posting is tied to, if any */
    public static function centresFor(int $jobPostingId): array
    {
        return Database::all(
            'SELECT c.* FROM job_posting_centres jpc JOIN centres c ON c.id = jpc.centre_id
             WHERE jpc.job_posting_id = :p ORDER BY c.name',
            ['p' => $jobPostingId]
        );
    }

    public static function isAcceptingApplications(array $posting): bool
    {
        if ($posting['status'] !== 'published') {
            return false;
        }
        if ($posting['application_deadline'] === null) {
            return true;
        }
        return strtotime((string) $posting['application_deadline']) >= time();
    }

    /** @param array<int,array<string,mixed>> $centres from centresFor() */
    public static function locationLabel(array $posting, array $centres): string
    {
        return match ($posting['location_type']) {
            'centre', 'multiple_centres' => $centres !== []
                ? implode(' & ', array_column($centres, 'name'))
                : (string) ($posting['location_label'] ?? 'On-site'),
            'remote' => 'Remote',
            'head_office' => 'Head Office',
            default => (string) ($posting['location_label'] ?? 'Other'),
        };
    }

    // ------------------------------------------------------------------- admin (recruitment.job.manage)

    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published', 'unpublished' => 'Unpublished', 'closed' => 'Closed'];

    /** Admin listing — every status, unlike search()/find*BySlug() which are published-only. */
    public static function allForAdmin(?string $status = null): array
    {
        $sql = self::BASE_SELECT;
        $params = [];
        if ($status !== null && $status !== '') {
            $sql .= ' WHERE jp.status = :s';
            $params['s'] = $status;
        }
        $sql .= ' ORDER BY jp.created_at DESC';
        return Database::query($sql, $params)->fetchAll();
    }

    public static function slugify(string $title): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        $base = $slug !== '' ? $slug : 'position';
        $candidate = $base;
        $n = 2;
        while (Database::one('SELECT id FROM job_postings WHERE slug = :s', ['s' => $candidate]) !== null) {
            $candidate = $base . '-' . $n++;
        }
        return $candidate;
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO job_postings
                (title, slug, department_id, category_id, employment_type, work_mode, location_type, location_label,
                 summary, responsibilities, requirements, qualifications, skills, experience_requirements, benefits,
                 application_deadline, status, created_by)
             VALUES (:title,:slug,:dept,:cat,:emp,:mode,:loct,:locl,:summ,:resp,:req,:qual,:skills,:exp,:ben,:deadline,\'draft\',:by)',
            [
                'title' => $data['title'], 'slug' => self::slugify($data['title']),
                'dept' => $data['department_id'], 'cat' => $data['category_id'],
                'emp' => $data['employment_type'], 'mode' => $data['work_mode'],
                'loct' => $data['location_type'], 'locl' => $data['location_label'],
                'summ' => $data['summary'], 'resp' => $data['responsibilities'], 'req' => $data['requirements'],
                'qual' => $data['qualifications'], 'skills' => $data['skills'], 'exp' => $data['experience_requirements'],
                'ben' => $data['benefits'], 'deadline' => $data['application_deadline'], 'by' => Auth::id(),
            ]
        );
        return Database::lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE job_postings SET title = :title, department_id = :dept, category_id = :cat,
                employment_type = :emp, work_mode = :mode, location_type = :loct, location_label = :locl,
                summary = :summ, responsibilities = :resp, requirements = :req, qualifications = :qual,
                skills = :skills, experience_requirements = :exp, benefits = :ben, application_deadline = :deadline
             WHERE id = :id',
            [
                'title' => $data['title'], 'dept' => $data['department_id'], 'cat' => $data['category_id'],
                'emp' => $data['employment_type'], 'mode' => $data['work_mode'],
                'loct' => $data['location_type'], 'locl' => $data['location_label'],
                'summ' => $data['summary'], 'resp' => $data['responsibilities'], 'req' => $data['requirements'],
                'qual' => $data['qualifications'], 'skills' => $data['skills'], 'exp' => $data['experience_requirements'],
                'ben' => $data['benefits'], 'deadline' => $data['application_deadline'], 'id' => $id,
            ]
        );
    }

    /** @param array<int,int> $centreIds */
    public static function setCentres(int $id, array $centreIds): void
    {
        Database::query('DELETE FROM job_posting_centres WHERE job_posting_id = :p', ['p' => $id]);
        foreach (array_unique($centreIds) as $centreId) {
            Database::query('INSERT INTO job_posting_centres (job_posting_id, centre_id) VALUES (:p,:c)', ['p' => $id, 'c' => (int) $centreId]);
        }
    }

    public static function publish(int $id): void
    {
        Database::query("UPDATE job_postings SET status = 'published', published_at = COALESCE(published_at, NOW()) WHERE id = :id", ['id' => $id]);
    }

    public static function unpublish(int $id): void
    {
        Database::query("UPDATE job_postings SET status = 'unpublished' WHERE id = :id", ['id' => $id]);
    }

    public static function close(int $id): void
    {
        Database::query("UPDATE job_postings SET status = 'closed', closed_at = NOW() WHERE id = :id", ['id' => $id]);
    }

    /** Duplicates a posting as a fresh draft — brief §28's "duplicate jobs" — questions included, applications and centres are not. */
    public static function duplicate(int $id): int
    {
        $source = self::find($id);
        if (!$source) {
            throw new RuntimeException("Job posting $id not found");
        }
        $newId = self::create([
            'title' => $source['title'] . ' (Copy)', 'department_id' => $source['department_id'], 'category_id' => $source['category_id'],
            'employment_type' => $source['employment_type'], 'work_mode' => $source['work_mode'],
            'location_type' => $source['location_type'], 'location_label' => $source['location_label'],
            'summary' => $source['summary'], 'responsibilities' => $source['responsibilities'], 'requirements' => $source['requirements'],
            'qualifications' => $source['qualifications'], 'skills' => $source['skills'], 'experience_requirements' => $source['experience_requirements'],
            'benefits' => $source['benefits'], 'application_deadline' => null,
        ]);
        foreach (JobQuestion::forPosting($id) as $q) {
            Database::query(
                'INSERT INTO job_questions (job_posting_id, label, type, options, is_required, sort_order) VALUES (:p,:l,:t,:o,:r,:s)',
                ['p' => $newId, 'l' => $q['label'], 't' => $q['type'], 'o' => $q['options'], 'r' => $q['is_required'], 's' => $q['sort_order']]
            );
        }
        return $newId;
    }

    /** Pipeline counts by status, for the admin overview. */
    public static function pipelineCounts(int $jobPostingId): array
    {
        $rows = Database::all(
            "SELECT status, COUNT(*) c FROM job_applications WHERE job_posting_id = :p AND status <> 'draft' GROUP BY status",
            ['p' => $jobPostingId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['c'];
        }
        return $out;
    }
}
