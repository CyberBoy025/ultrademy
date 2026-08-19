<?php
declare(strict_types=1);

final class ProgrammeController
{
    public static function index(): void
    {
        $canManage = Auth::can('education.programme.create');
        $programmes = Programme::all(!$canManage);

        $main = View::render('programmes/index', [
            'programmes' => $programmes,
            'canManage' => $canManage,
            'categories' => ProgrammeCategory::all(),
            'centres' => Centre::all(),
        ]);
        View::shell('programmes', 'Programmes', $main);
    }

    public static function store(): void
    {
        Auth::requirePermission('education.programme.create');
        Csrf::requireValid();

        $title = trim((string) ($_POST['title'] ?? ''));
        $code = strtoupper('PRG-' . substr(preg_replace('/[^A-Za-z]/', '', $title) . 'XXX', 0, 6));
        $slug = self::slugify($title);

        if ($title === '') {
            Session::flash('error', 'Programme title is required.');
            header('Location: app.php?r=programmes');
            exit;
        }

        $id = Programme::create([
            'code' => $code . '-' . random_int(100, 999),
            'title' => $title,
            'slug' => $slug . '-' . random_int(100, 999),
            'category_id' => $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null,
            'description' => trim((string) ($_POST['description'] ?? '')),
            'duration_weeks' => $_POST['duration_weeks'] !== '' ? (int) $_POST['duration_weeks'] : null,
            'delivery_mode' => in_array($_POST['delivery_mode'] ?? '', ['physical', 'online', 'hybrid'], true) ? $_POST['delivery_mode'] : 'physical',
            'fee_amount' => ((int) ($_POST['fee_naira'] ?? 0)) * 100,
            'currency' => 'NGN',
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);

        if (!empty($_POST['centre_ids']) && is_array($_POST['centre_ids'])) {
            Programme::setCentres($id, array_map('intval', $_POST['centre_ids']));
        }

        Audit::log('programme.created', 'programmes', $id, null, ['title' => $title]);
        Session::flash('success', "Programme \"$title\" created as a draft.");
        header('Location: app.php?r=programmes.show&id=' . $id);
        exit;
    }

    public static function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $programme = Programme::find($id);
        if (!$programme) {
            http_response_code(404);
            echo 'Programme not found.';
            return;
        }
        $canManage = Auth::can('education.programme.create');
        if (!$canManage && $programme['status'] !== 'published') {
            Auth::requirePermission('education.programme.create'); // draft/unpublished — staff only
        }

        $main = View::render('programmes/show', [
            'programme' => $programme,
            'canManage' => $canManage,
            'canApprove' => Auth::can('education.programme.approve'),
            'canPublish' => Auth::can('education.programme.publish'),
            'centres' => Programme::centresFor($id),
            'cohorts' => Cohort::forProgramme($id),
        ]);
        View::shell('programmes', $programme['title'], $main);
    }

    public static function status(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        $map = [
            'pending_approval' => 'education.programme.create',
            'approved'         => 'education.programme.approve',
            'published'        => 'education.programme.publish',
            'archived'         => 'education.programme.publish',
        ];
        Auth::requirePermission($map[$status] ?? 'education.programme.create');

        if (!in_array($status, ['draft', 'pending_approval', 'approved', 'published', 'archived'], true)) {
            Session::flash('error', 'Invalid status.');
        } else {
            $old = Programme::find($id);
            Programme::setStatus($id, $status);
            Audit::log('programme.status_changed', 'programmes', $id, ['status' => $old['status'] ?? null], ['status' => $status]);
            Session::flash('success', 'Programme status updated.');
        }
        header('Location: app.php?r=programmes.show&id=' . $id);
        exit;
    }

    private static function slugify(string $text): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
        return $slug !== '' ? $slug : 'programme';
    }
}
