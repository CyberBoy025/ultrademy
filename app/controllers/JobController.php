<?php
declare(strict_types=1);

/**
 * Public careers site — landing page, job search/listing, job detail, save/unsave.
 * Browsing needs no login (docs/architecture/16-careers-portal.md §3); only save/unsave
 * are gated, by ownership rather than any permission code.
 */
final class JobController
{
    public static function home(): void
    {
        $featured = array_slice(JobPosting::search([]), 0, 4);
        foreach ($featured as &$job) {
            $job['_location'] = JobPosting::locationLabel($job, JobPosting::centresFor((int) $job['id']));
        }
        unset($job);

        $main = View::render('careers/home', ['featured' => $featured]);
        View::careersShell('home', 'Careers at UltrAdemy', $main,
            'Explore opportunities to join UltrAdemy across technology, education, administration, training and operations.');
    }

    public static function index(): void
    {
        $filters = [
            'keyword' => trim((string) ($_GET['q'] ?? '')),
            'department_id' => $_GET['department_id'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'employment_type' => $_GET['employment_type'] ?? '',
            'work_mode' => $_GET['work_mode'] ?? '',
            'centre_id' => $_GET['centre_id'] ?? '',
        ];

        $userId = Auth::id();
        $jobs = JobPosting::search($filters);
        foreach ($jobs as &$job) {
            $job['_location'] = JobPosting::locationLabel($job, JobPosting::centresFor((int) $job['id']));
        }
        unset($job);

        $main = View::render('careers/jobs/index', [
            'jobs' => $jobs,
            'filters' => $filters,
            'departments' => Department::all(),
            'categories' => JobCategory::all(),
            'centres' => Centre::all(),
            'savedIds' => $userId ? SavedJob::idsForUser($userId) : [],
        ]);
        View::careersShell('jobs', 'Open Positions', $main,
            'Browse current openings at UltrAdemy — technology, instruction, administration and centre operations roles.');
    }

    public static function show(): void
    {
        $slug = (string) ($_GET['slug'] ?? '');
        $posting = JobPosting::findPublishedBySlug($slug);
        if (!$posting) {
            http_response_code(404);
            $main = View::render('careers/jobs/not_found', []);
            View::careersShell('jobs', 'Position Not Found', $main);
            return;
        }

        $centres = JobPosting::centresFor((int) $posting['id']);
        $userId = Auth::id();
        $main = View::render('careers/jobs/show', [
            'job' => $posting,
            'centres' => $centres,
            'locationLabel' => JobPosting::locationLabel($posting, $centres),
            'acceptingApplications' => JobPosting::isAcceptingApplications($posting),
            'isSaved' => $userId ? in_array((int) $posting['id'], SavedJob::idsForUser($userId), true) : false,
        ]);
        View::careersShell('jobs', $posting['title'], $main, mb_strimwidth((string) $posting['summary'], 0, 155, '…'));
    }

    public static function save(): void
    {
        Csrf::requireValid();
        $userId = Auth::id();
        if ($userId === null) {
            header('Location: login.php');
            exit;
        }
        $jobId = (int) ($_POST['job_posting_id'] ?? 0);
        if (JobPosting::find($jobId)) {
            SavedJob::save($userId, $jobId);
        }
        header('Location: ' . self::backTo($jobId));
        exit;
    }

    public static function unsave(): void
    {
        Csrf::requireValid();
        $userId = Auth::id();
        if ($userId === null) {
            header('Location: login.php');
            exit;
        }
        $jobId = (int) ($_POST['job_posting_id'] ?? 0);
        SavedJob::unsave($userId, $jobId);
        header('Location: ' . self::backTo($jobId));
        exit;
    }

    private static function backTo(int $jobId): string
    {
        $posting = JobPosting::find($jobId);
        return $posting ? 'app.php?r=jobs.show&slug=' . urlencode((string) $posting['slug']) : 'app.php?r=jobs';
    }
}
