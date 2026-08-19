<?php
declare(strict_types=1);

/**
 * Recruitment admin/backend (docs/architecture/16-careers-portal.md §4, brief §27–§33) —
 * lives in the MAIN app (this shell, this Nav, these permissions), not the careers
 * subdomain: it's staff tooling, not applicant-facing.
 *
 * Job management (`recruitment.job.manage`) is held only by the global, non-scopable
 * `recruitment_admin` role, so job CRUD needs no centre-scope checks. The applicant
 * pipeline (`recruitment.application.view_any`) is also held by the centre-scopable
 * `recruiter` role, so every pipeline query goes through Auth::scopeCentres() exactly
 * like ApplicationController does for programme admissions.
 */
final class RecruitmentAdminController
{
    // -------------------------------------------------------------------------- landing

    public static function index(): void
    {
        if (Auth::can('recruitment.job.manage')) {
            header('Location: app.php?r=recruitment.jobs');
        } elseif (Auth::can('recruitment.application.view_any')) {
            header('Location: app.php?r=recruitment.applications');
        } elseif (Auth::can('recruitment.report.view')) {
            header('Location: app.php?r=recruitment.reports');
        } else {
            Auth::requirePermission('recruitment.job.manage'); // renders 403
        }
        exit;
    }

    // ------------------------------------------------------------------------------ jobs

    public static function jobs(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        $status = (string) ($_GET['status'] ?? '');
        $main = View::render('recruitment/jobs/index', [
            'jobs' => JobPosting::allForAdmin($status ?: null),
            'status' => $status,
        ]);
        View::shell('recruitment', 'Recruitment — Jobs', $main);
    }

    public static function jobCreate(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        $main = View::render('recruitment/jobs/form', [
            'job' => null, 'departments' => Department::all(), 'categories' => JobCategory::all(),
            'centres' => Centre::all(), 'selectedCentres' => [],
        ]);
        View::shell('recruitment', 'New Job Posting', $main);
    }

    public static function jobStore(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        Csrf::requireValid();
        $data = self::jobFormData();
        if ($data['title'] === '') {
            Session::flash('error', 'Enter a job title.');
            header('Location: app.php?r=recruitment.jobs.create');
            exit;
        }
        $id = JobPosting::create($data);
        JobPosting::setCentres($id, array_map('intval', $_POST['centre_ids'] ?? []));
        Audit::log('job_posting.created', 'job_postings', $id, null, ['title' => $data['title']]);
        Session::flash('success', 'Job posting created as a draft.');
        header('Location: app.php?r=recruitment.jobs.edit&id=' . $id);
        exit;
    }

    public static function jobEdit(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        $job = JobPosting::find((int) ($_GET['id'] ?? 0));
        if (!$job) {
            http_response_code(404);
            echo 'Job posting not found.';
            return;
        }
        $selected = array_column(JobPosting::centresFor((int) $job['id']), 'id');
        $main = View::render('recruitment/jobs/form', [
            'job' => $job, 'departments' => Department::all(), 'categories' => JobCategory::all(),
            'centres' => Centre::all(), 'selectedCentres' => $selected,
        ]);
        View::shell('recruitment', 'Edit — ' . $job['title'], $main);
    }

    public static function jobUpdate(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $job = JobPosting::find($id);
        if (!$job) {
            http_response_code(404);
            echo 'Job posting not found.';
            return;
        }
        $data = self::jobFormData();
        JobPosting::update($id, $data);
        JobPosting::setCentres($id, array_map('intval', $_POST['centre_ids'] ?? []));
        Audit::log('job_posting.updated', 'job_postings', $id, null, ['title' => $data['title']]);
        Session::flash('success', 'Job posting saved.');
        header('Location: app.php?r=recruitment.jobs.edit&id=' . $id);
        exit;
    }

    public static function jobPublish(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        JobPosting::publish($id);
        Audit::log('job_posting.published', 'job_postings', $id);
        Session::flash('success', 'Job posting published.');
        header('Location: app.php?r=recruitment.jobs.edit&id=' . $id);
        exit;
    }

    public static function jobUnpublish(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        JobPosting::unpublish($id);
        Audit::log('job_posting.unpublished', 'job_postings', $id);
        Session::flash('success', 'Job posting unpublished.');
        header('Location: app.php?r=recruitment.jobs.edit&id=' . $id);
        exit;
    }

    public static function jobClose(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        JobPosting::close($id);
        Audit::log('job_posting.closed', 'job_postings', $id);
        Session::flash('success', 'Job posting closed.');
        header('Location: app.php?r=recruitment.jobs.edit&id=' . $id);
        exit;
    }

    public static function jobDuplicate(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $newId = JobPosting::duplicate($id);
        Audit::log('job_posting.duplicated', 'job_postings', $newId, null, ['from' => $id]);
        Session::flash('success', 'Job posting duplicated as a new draft.');
        header('Location: app.php?r=recruitment.jobs.edit&id=' . $newId);
        exit;
    }

    /** @return array<string,mixed> */
    private static function jobFormData(): array
    {
        return [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'department_id' => $_POST['department_id'] !== '' ? (int) $_POST['department_id'] : null,
            'category_id' => $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null,
            'employment_type' => isset(JobPosting::EMPLOYMENT_TYPES[$_POST['employment_type'] ?? '']) ? $_POST['employment_type'] : 'full_time',
            'work_mode' => isset(JobPosting::WORK_MODES[$_POST['work_mode'] ?? '']) ? $_POST['work_mode'] : 'onsite',
            'location_type' => in_array($_POST['location_type'] ?? '', ['centre', 'remote', 'multiple_centres', 'head_office', 'other'], true) ? $_POST['location_type'] : 'centre',
            'location_label' => trim((string) ($_POST['location_label'] ?? '')) ?: null,
            'summary' => trim((string) ($_POST['summary'] ?? '')) ?: null,
            'responsibilities' => trim((string) ($_POST['responsibilities'] ?? '')) ?: null,
            'requirements' => trim((string) ($_POST['requirements'] ?? '')) ?: null,
            'qualifications' => trim((string) ($_POST['qualifications'] ?? '')) ?: null,
            'skills' => trim((string) ($_POST['skills'] ?? '')) ?: null,
            'experience_requirements' => trim((string) ($_POST['experience_requirements'] ?? '')) ?: null,
            'benefits' => trim((string) ($_POST['benefits'] ?? '')) ?: null,
            'application_deadline' => trim((string) ($_POST['application_deadline'] ?? '')) ?: null,
        ];
    }

    // -------------------------------------------------------------------------- questions

    public static function jobQuestions(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        $job = JobPosting::find((int) ($_GET['id'] ?? 0));
        if (!$job) {
            http_response_code(404);
            echo 'Job posting not found.';
            return;
        }
        $main = View::render('recruitment/jobs/questions', ['job' => $job, 'questions' => JobQuestion::forPosting((int) $job['id'])]);
        View::shell('recruitment', 'Questions — ' . $job['title'], $main);
    }

    public static function jobQuestionStore(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        Csrf::requireValid();
        $jobId = (int) $_POST['job_posting_id'];
        $label = trim((string) ($_POST['label'] ?? ''));
        if ($label === '') {
            Session::flash('error', 'Enter the question text.');
        } else {
            $options = null;
            if (($_POST['type'] ?? '') === 'multiple_choice') {
                $options = array_values(array_filter(array_map('trim', explode("\n", (string) ($_POST['options'] ?? '')))));
            }
            JobQuestion::create($jobId, $label, (string) ($_POST['type'] ?? 'short_text'), isset($_POST['is_required']), $options);
            Session::flash('success', 'Question added.');
        }
        header('Location: app.php?r=recruitment.jobs.questions&id=' . $jobId);
        exit;
    }

    public static function jobQuestionDelete(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        Csrf::requireValid();
        $jobId = (int) $_POST['job_posting_id'];
        JobQuestion::delete((int) $_POST['id'], $jobId);
        header('Location: app.php?r=recruitment.jobs.questions&id=' . $jobId);
        exit;
    }

    // ---------------------------------------------------------------------- departments

    public static function departments(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        $main = View::render('recruitment/settings/departments', ['departments' => Department::all()]);
        View::shell('recruitment', 'Recruitment — Departments', $main);
    }

    public static function departmentStore(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        Csrf::requireValid();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name !== '') {
            Department::create($name);
            Session::flash('success', 'Department added.');
        }
        header('Location: app.php?r=recruitment.departments');
        exit;
    }

    public static function categories(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        $main = View::render('recruitment/settings/categories', ['categories' => JobCategory::all()]);
        View::shell('recruitment', 'Recruitment — Job Categories', $main);
    }

    public static function categoryStore(): void
    {
        Auth::requirePermission('recruitment.job.manage');
        Csrf::requireValid();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name !== '') {
            JobCategory::create($name);
            Session::flash('success', 'Category added.');
        }
        header('Location: app.php?r=recruitment.categories');
        exit;
    }

    // ------------------------------------------------------------------------ pipeline

    public static function applications(): void
    {
        Auth::requirePermission('recruitment.application.view_any');
        $scope = Auth::scopeCentres('recruitment.application.view_any');
        $filters = [
            'job_posting_id' => (int) ($_GET['job'] ?? 0) ?: null,
            'status' => (string) ($_GET['status'] ?? '') ?: null,
        ];
        $main = View::render('recruitment/applications/index', [
            'applications' => JobApplication::pipeline($filters, $scope),
            'counts' => JobApplication::pipelineCounts([], $scope),
            'jobs' => JobPosting::allForAdmin(),
            'filters' => $filters,
        ]);
        View::shell('recruitment', 'Recruitment — Applications', $main);
    }

    public static function applicationShow(): void
    {
        Auth::requirePermission('recruitment.application.view_any');
        $id = (int) ($_GET['id'] ?? 0);
        $app = self::loadInScopeOrDeny($id);

        $interviews = Interview::forApplication($id);
        $feedback = [];
        foreach ($interviews as $iv) {
            $feedback[$iv['id']] = InterviewFeedback::forInterview((int) $iv['id']);
        }

        $main = View::render('recruitment/applications/show', [
            'app' => $app,
            'profile' => JobApplicantProfile::forUser((int) $app['user_id']),
            'education' => ApplicantEducation::forUser((int) $app['user_id']),
            'experience' => ApplicantExperience::forUser((int) $app['user_id']),
            'skills' => ApplicantSkill::forUser((int) $app['user_id']),
            'references' => ApplicantReference::forUser((int) $app['user_id']),
            'documents' => JobApplicationDocument::forApplication($id),
            'answers' => ApplicationAnswer::forApplication($id),
            'history' => JobApplicationStatusHistory::forApplication($id),
            'notes' => RecruitmentNote::forApplication($id),
            'interviews' => $interviews,
            'feedback' => $feedback,
            'panelistCandidates' => Database::all(
                "SELECT DISTINCT u.id, CONCAT(pr.first_name, ' ', pr.last_name) AS name
                 FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id
                 LEFT JOIN user_profiles pr ON pr.user_id = u.id
                 WHERE r.code IN ('interviewer','recruiter','recruitment_admin') ORDER BY name"
            ),
            'centres' => Centre::all(),
            'canReview' => Auth::can('recruitment.application.review'),
            'canDecide' => Auth::can('recruitment.application.decide'),
            'canManageInterviews' => Auth::can('recruitment.interview.manage'),
        ]);
        View::shell('recruitment', $app['reference'], $main);
    }

    /** Moves an application through the review pipeline — never into a terminal decision. */
    public static function applicationReview(): void
    {
        Auth::requirePermission('recruitment.application.review');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $app = self::loadInScopeOrDeny($id);
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, JobApplication::REVIEW_STATUSES, true)) {
            Session::flash('error', 'Invalid status.');
            header('Location: app.php?r=recruitment.applications.show&id=' . $id);
            exit;
        }
        JobApplication::setStatus($id, $status, (int) Auth::id());
        JobApplicationStatusHistory::record($id, $app['status'], $status, (int) Auth::id());
        Audit::log('job_application.status_changed', 'job_applications', $id, ['status' => $app['status']], ['status' => $status]);

        $updated = JobApplication::find($id);
        $mail = EmailTemplate::render('status_update', JobApplication::emailVars($updated));
        Notify::send((int) $app['user_id'], 'job_application.status_changed', 'recruitment', $mail['subject'], $mail['body'], null);
        RecruitmentEmailLog::record($id, $updated['email'], $mail['subject'], 'status_update', 'job_application.status_changed');

        Session::flash('success', 'Application moved to ' . (JobApplication::STATUS_LABELS[$status] ?? $status) . '.');
        header('Location: app.php?r=recruitment.applications.show&id=' . $id);
        exit;
    }

    /** Final decision — separate permission from review, mirroring admissions' "approve is never implied by review". */
    public static function applicationDecide(): void
    {
        Auth::requirePermission('recruitment.application.decide');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $app = self::loadInScopeOrDeny($id);
        $decision = (string) ($_POST['decision'] ?? '');
        if (!in_array($decision, JobApplication::DECISION_STATUSES, true)) {
            Session::flash('error', 'Invalid decision.');
            header('Location: app.php?r=recruitment.applications.show&id=' . $id);
            exit;
        }
        $note = trim((string) ($_POST['decision_note'] ?? ''));
        JobApplication::setStatus($id, $decision, (int) Auth::id(), $note !== '' ? $note : null);
        JobApplicationStatusHistory::record($id, $app['status'], $decision, (int) Auth::id(), $note !== '' ? $note : null);
        JobApplication::syncApplicantRole((int) $app['user_id']);
        Audit::log("job_application.$decision", 'job_applications', $id, ['status' => $app['status']], ['status' => $decision]);

        $decided = JobApplication::find($id);
        $templateCode = $decision === 'selected' ? 'application_selected' : 'application_rejected';
        $mail = EmailTemplate::render($templateCode, JobApplication::emailVars($decided));
        Notify::send((int) $app['user_id'], "job_application.$decision", 'recruitment', $mail['subject'], $mail['body'], null);
        RecruitmentEmailLog::record($id, $decided['email'], $mail['subject'], $templateCode, "job_application.$decision");

        Session::flash('success', $decision === 'selected' ? 'Applicant marked as selected.' : 'Application marked not successful.');
        header('Location: app.php?r=recruitment.applications.show&id=' . $id);
        exit;
    }

    public static function noteStore(): void
    {
        Auth::requirePermission('recruitment.note.manage');
        Csrf::requireValid();
        $id = (int) $_POST['job_application_id'];
        self::loadInScopeOrDeny($id);
        $note = trim((string) ($_POST['note'] ?? ''));
        if ($note !== '') {
            RecruitmentNote::create($id, (int) Auth::id(), $note);
        }
        header('Location: app.php?r=recruitment.applications.show&id=' . $id);
        exit;
    }

    /** Staff-side document download — same authorisation-then-stream discipline as the applicant-side one. */
    public static function documentDownload(): void
    {
        Auth::requirePermission('recruitment.application.view_any');
        $doc = JobApplicationDocument::find((int) ($_GET['id'] ?? 0));
        if (!$doc) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }
        self::loadInScopeOrDeny((int) $doc['job_application_id']);
        $path = JobApplicationDocument::storageDir() . '/' . $doc['stored_name'];
        if (!is_file($path)) {
            http_response_code(404);
            echo 'File missing from storage.';
            return;
        }
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $doc['original_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    // -------------------------------------------------------------------- email templates

    public static function emailTemplates(): void
    {
        Auth::requirePermission('recruitment.email_template.manage');
        $main = View::render('recruitment/settings/emailtemplates', ['templates' => EmailTemplate::all()]);
        View::shell('recruitmentemail', 'Recruitment — Email Templates', $main);
    }

    public static function emailTemplateEdit(): void
    {
        Auth::requirePermission('recruitment.email_template.manage');
        $code = (string) ($_GET['code'] ?? '');
        if (!isset(EmailTemplate::DEFAULTS[$code])) {
            http_response_code(404);
            echo 'Unknown template.';
            return;
        }
        $existing = EmailTemplate::find($code);
        $template = $existing ?? ['code' => $code, 'name' => EmailTemplate::DEFAULTS[$code]['name'],
            'subject' => EmailTemplate::DEFAULTS[$code]['subject'], 'body' => EmailTemplate::DEFAULTS[$code]['body']];
        $main = View::render('recruitment/settings/emailtemplate_form', ['template' => $template, 'isCustomised' => $existing !== null]);
        View::shell('recruitmentemail', 'Edit — ' . $template['name'], $main);
    }

    public static function emailTemplateSave(): void
    {
        Auth::requirePermission('recruitment.email_template.manage');
        Csrf::requireValid();
        $code = (string) ($_POST['code'] ?? '');
        if (!isset(EmailTemplate::DEFAULTS[$code])) {
            http_response_code(404);
            echo 'Unknown template.';
            return;
        }
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($subject === '' || $body === '') {
            Session::flash('error', 'Subject and body cannot be empty.');
            header('Location: app.php?r=recruitment.emailtemplates.edit&code=' . $code);
            exit;
        }
        EmailTemplate::upsert($code, EmailTemplate::DEFAULTS[$code]['name'], $subject, $body);
        $saved = EmailTemplate::find($code);
        Audit::log('recruitment.email_template.updated', 'recruitment_email_templates', (int) $saved['id'], null, ['code' => $code]);
        Session::flash('success', 'Template saved.');
        header('Location: app.php?r=recruitment.emailtemplates');
        exit;
    }

    public static function emailLogs(): void
    {
        Auth::requirePermission('recruitment.email_template.manage');
        $status = (string) ($_GET['status'] ?? '');
        $main = View::render('recruitment/settings/emaillogs', [
            'logs' => RecruitmentEmailLog::search(['status' => $status ?: null]),
            'status' => $status,
        ]);
        View::shell('recruitmentemail', 'Recruitment — Email Log', $main);
    }

    // -------------------------------------------------------------------------- reports

    public static function reports(): void
    {
        Auth::requirePermission('recruitment.report.view');
        $scope = Auth::scopeCentres('recruitment.report.view');
        $from = (string) ($_GET['from'] ?? date('Y-m-01'));
        $to = (string) ($_GET['to'] ?? date('Y-m-d'));

        $main = View::render('recruitment/reports', [
            'from' => $from, 'to' => $to, 'isGlobal' => $scope === null,
            'vacancySummary' => RecruitmentReport::vacancySummary(),
            'applicationSummary' => RecruitmentReport::applicationSummary($from, $to, $scope),
            'byJob' => RecruitmentReport::byJob($from, $to, $scope),
            'byCentre' => $scope === null ? RecruitmentReport::byCentre($from, $to) : [],
            'interviewStats' => RecruitmentReport::interviewStats($from, $to, $scope),
            'activity' => RecruitmentReport::recentActivity($scope, 12),
        ]);
        View::shell('recruitmentreports', 'Recruitment — Reports', $main);
    }

    /** @return array<string,mixed> the application, or exits 403/404 */
    private static function loadInScopeOrDeny(int $id): array
    {
        $app = JobApplication::find($id);
        if (!$app) {
            http_response_code(404);
            echo 'Application not found.';
            exit;
        }
        $scope = Auth::scopeCentres('recruitment.application.view_any');
        if ($scope !== null) {
            $centreIds = array_column(JobPosting::centresFor((int) $app['job_posting_id']), 'id');
            if (!array_intersect($centreIds, $scope)) {
                http_response_code(403);
                require dirname(__DIR__) . '/views/errors/403.php';
                exit;
            }
        }
        return $app;
    }
}
