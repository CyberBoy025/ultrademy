<?php
declare(strict_types=1);

/**
 * Applicant dashboard (brief §19) — a minimal first slice: account, saved jobs. The
 * application tracker (§20), profile completion (§12) and notifications tile land with
 * the application workflow and biodata wizard in the next phase.
 */
final class CareersDashboardController
{
    public static function index(): void
    {
        $userId = Auth::id();
        if ($userId === null) {
            header('Location: login.php');
            exit;
        }

        $savedIds = SavedJob::idsForUser($userId);
        $savedJobs = [];
        foreach ($savedIds as $id) {
            $job = JobPosting::find($id);
            if ($job !== null) {
                $savedJobs[] = $job;
            }
        }

        $applications = JobApplication::forUser($userId);
        $main = View::render('careers/dashboard', [
            'savedJobs' => $savedJobs,
            'applications' => $applications,
            'openCount' => count(array_filter($applications, static fn ($a) => in_array($a['status'], JobApplication::OPEN_STATUSES, true))),
            'completionPct' => JobApplicantProfile::forUser($userId)['completion_pct'],
        ]);
        View::careersShell('dashboard', 'My Dashboard', $main);
    }
}
