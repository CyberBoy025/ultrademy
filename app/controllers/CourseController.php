<?php
declare(strict_types=1);

/** Course authoring: courses, modules, lessons, materials, assignments (README §18, §19, §23). */
final class CourseController
{
    public static function index(): void
    {
        Auth::requirePermission('education.lesson.view');
        $main = View::render('courses/index', [
            'courses' => Course::all(!Learning::canManage()),
            'canManage' => Learning::canManage(),
        ]);
        View::shell('courses', 'Courses', $main);
    }

    public static function store(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            Session::flash('error', 'A course needs a title.');
            header('Location: app.php?r=courses');
            exit;
        }
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-')) . '-' . random_int(100, 999);
        $id = Course::create([
            'title' => $title,
            'slug' => $slug,
            'description' => trim((string) ($_POST['description'] ?? '')),
            'objectives' => trim((string) ($_POST['objectives'] ?? '')),
            'prerequisites' => trim((string) ($_POST['prerequisites'] ?? '')),
            'standalone' => isset($_POST['standalone']) ? 1 : 0,
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);
        Audit::log('course.created', 'courses', $id, null, ['title' => $title]);
        Session::flash('success', "Course \"$title\" created as a draft.");
        header('Location: app.php?r=courses.show&id=' . $id);
        exit;
    }

    /** The authoring view of a course: outline, materials and assignments. */
    public static function show(): void
    {
        Auth::requirePermission('education.lesson.view');
        $id = (int) ($_GET['id'] ?? 0);
        $course = Course::find($id);
        if (!$course) {
            http_response_code(404);
            echo 'Course not found.';
            return;
        }

        $outline = Course::outline($id);
        foreach ($outline as &$m) {
            foreach ($m['lessons'] as &$l) {
                $l['materials'] = Material::forLesson((int) $l['id']);
            }
        }
        $main = View::render('courses/show', [
            'course' => $course,
            'outline' => $outline,
            'canManage' => Learning::canManage(),
            'assignments' => Assignment::forCourse($id),
            'programmes' => Course::programmesFor($id),
            'allProgrammes' => Learning::canManage() ? Programme::all(false) : [],
        ]);
        View::shell('courses', $course['title'], $main);
    }

    public static function update(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $old = Course::find($id);
        Course::update($id, [
            'title' => trim((string) $_POST['title']),
            'description' => trim((string) $_POST['description']),
            'objectives' => trim((string) $_POST['objectives']),
            'prerequisites' => trim((string) $_POST['prerequisites']),
            'standalone' => isset($_POST['standalone']) ? 1 : 0,
            'status' => in_array($_POST['status'] ?? '', ['draft', 'published', 'archived'], true) ? $_POST['status'] : 'draft',
        ]);
        Audit::log('course.updated', 'courses', $id, ['status' => $old['status'] ?? null], ['status' => $_POST['status'] ?? null]);
        Session::flash('success', 'Course updated.');
        header('Location: app.php?r=courses.show&id=' . $id);
        exit;
    }

    /** Attaches the course to programmes — this is what makes it reachable by students. */
    public static function linkProgrammes(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $courseId = (int) $_POST['id'];
        $programmeIds = array_map('intval', $_POST['programme_ids'] ?? []);

        Database::query('DELETE FROM programme_courses WHERE course_id = :c', ['c' => $courseId]);
        foreach ($programmeIds as $pid) {
            Database::query(
                'INSERT IGNORE INTO programme_courses (programme_id, course_id) VALUES (:p,:c)',
                ['p' => $pid, 'c' => $courseId]
            );
        }
        Audit::log('course.programmes_linked', 'courses', $courseId, null, ['programmes' => count($programmeIds)]);
        Session::flash('success', 'Programme links updated.');
        header('Location: app.php?r=courses.show&id=' . $courseId);
        exit;
    }

    // ------------------------------------------------------------- modules & lessons

    public static function storeModule(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $courseId = (int) $_POST['course_id'];
        $id = Lesson::createModule($courseId, trim((string) $_POST['title']), trim((string) ($_POST['summary'] ?? '')) ?: null);
        Audit::log('module.created', 'modules', $id, null, ['course_id' => $courseId]);
        Session::flash('success', 'Module added.');
        header('Location: app.php?r=courses.show&id=' . $courseId);
        exit;
    }

    public static function deleteModule(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $module = Lesson::findModule((int) $_POST['id']);
        if (!$module) {
            http_response_code(404);
            echo 'Module not found.';
            return;
        }
        Lesson::deleteModule((int) $module['id']);
        Course::recalcDuration((int) $module['course_id']);
        Audit::log('module.deleted', 'modules', (int) $module['id']);
        Session::flash('success', 'Module deleted.');
        header('Location: app.php?r=courses.show&id=' . $module['course_id']);
        exit;
    }

    public static function storeLesson(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $moduleId = (int) $_POST['module_id'];
        $module = Lesson::findModule($moduleId);
        if (!$module) {
            http_response_code(404);
            echo 'Module not found.';
            return;
        }
        $id = Lesson::create($moduleId, [
            'title' => trim((string) $_POST['title']),
            'content_type' => in_array($_POST['content_type'] ?? '', ['video', 'text', 'document', 'link'], true) ? $_POST['content_type'] : 'text',
            'body' => trim((string) ($_POST['body'] ?? '')),
            'duration_minutes' => max(0, (int) ($_POST['duration_minutes'] ?? 0)),
            'is_preview' => isset($_POST['is_preview']) ? 1 : 0,
        ]);
        Course::recalcDuration((int) $module['course_id']);
        Audit::log('lesson.created', 'lessons', $id, null, ['module_id' => $moduleId]);
        Session::flash('success', 'Lesson added.');
        header('Location: app.php?r=lessons.edit&id=' . $id);
        exit;
    }

    public static function editLesson(): void
    {
        Auth::requirePermission('education.course.update');
        $lesson = Lesson::find((int) ($_GET['id'] ?? 0));
        if (!$lesson) {
            http_response_code(404);
            echo 'Lesson not found.';
            return;
        }
        $main = View::render('courses/lesson-edit', [
            'lesson' => $lesson,
            'materials' => Material::forLesson((int) $lesson['id']),
        ]);
        View::shell('courses', 'Edit: ' . $lesson['title'], $main);
    }

    public static function updateLesson(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $lesson = Lesson::find($id);
        if (!$lesson) {
            http_response_code(404);
            echo 'Lesson not found.';
            return;
        }
        Lesson::update($id, [
            'title' => trim((string) $_POST['title']),
            'content_type' => in_array($_POST['content_type'] ?? '', ['video', 'text', 'document', 'link'], true) ? $_POST['content_type'] : 'text',
            'body' => (string) ($_POST['body'] ?? ''),
            'duration_minutes' => max(0, (int) ($_POST['duration_minutes'] ?? 0)),
            'is_preview' => isset($_POST['is_preview']) ? 1 : 0,
        ]);
        Course::recalcDuration((int) $lesson['course_id']);
        Audit::log('lesson.updated', 'lessons', $id);
        Session::flash('success', 'Lesson saved.');
        header('Location: app.php?r=lessons.edit&id=' . $id);
        exit;
    }

    public static function deleteLesson(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $lesson = Lesson::find((int) $_POST['id']);
        if (!$lesson) {
            http_response_code(404);
            echo 'Lesson not found.';
            return;
        }
        Lesson::delete((int) $lesson['id']);
        Course::recalcDuration((int) $lesson['course_id']);
        Audit::log('lesson.deleted', 'lessons', (int) $lesson['id']);
        Session::flash('success', 'Lesson deleted.');
        header('Location: app.php?r=courses.show&id=' . $lesson['course_id']);
        exit;
    }

    // ------------------------------------------------------------------- materials

    public static function storeMaterial(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $lessonId = (int) $_POST['lesson_id'];
        $title = trim((string) ($_POST['title'] ?? '')) ?: 'Untitled';
        $url = trim((string) ($_POST['url'] ?? ''));

        if ($url !== '') {
            if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
                Session::flash('error', 'Enter a valid http(s) link.');
            } else {
                Material::storeLink($lessonId, $title, $url);
                Session::flash('success', 'Link added.');
            }
        } else {
            $error = Material::storeUpload($lessonId, $title, $_FILES['file'] ?? [], !isset($_POST['no_download']));
            Session::flash($error === null ? 'success' : 'error', $error ?? 'Material uploaded.');
        }
        Audit::log('material.added', 'lessons', $lessonId);
        header('Location: app.php?r=lessons.edit&id=' . $lessonId);
        exit;
    }

    public static function deleteMaterial(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $material = Material::find((int) $_POST['id']);
        if (!$material) {
            http_response_code(404);
            echo 'Material not found.';
            return;
        }
        Material::delete((int) $material['id']);
        Audit::log('material.deleted', 'lesson_materials', (int) $material['id']);
        Session::flash('success', 'Material removed.');
        header('Location: app.php?r=lessons.edit&id=' . $material['lesson_id']);
        exit;
    }

    // ----------------------------------------------------------------- assignments

    public static function storeAssignment(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $courseId = (int) $_POST['course_id'];
        $id = Assignment::create($courseId, [
            'title' => trim((string) $_POST['title']),
            'instructions' => trim((string) ($_POST['instructions'] ?? '')),
            'due_at' => ($_POST['due_at'] ?? '') !== '' ? $_POST['due_at'] : null,
            'max_score' => max(1, (int) ($_POST['max_score'] ?? 100)),
            'allows_file' => isset($_POST['allows_file']) ? 1 : 0,
            'allows_text' => isset($_POST['allows_text']) ? 1 : 0,
            'allows_resubmission' => isset($_POST['allows_resubmission']) ? 1 : 0,
            'status' => 'published',
        ]);
        Audit::log('assignment.created', 'assignments', $id, null, ['course_id' => $courseId]);
        Session::flash('success', 'Assignment published.');
        header('Location: app.php?r=courses.show&id=' . $courseId);
        exit;
    }

    public static function assignmentStatus(): void
    {
        Auth::requirePermission('education.course.update');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $assignment = Assignment::find($id);
        $status = in_array($_POST['status'] ?? '', ['draft', 'published', 'closed'], true) ? $_POST['status'] : 'draft';
        Assignment::setStatus($id, $status);
        Audit::log('assignment.status_changed', 'assignments', $id, null, ['status' => $status]);
        Session::flash('success', 'Assignment marked ' . $status . '.');
        header('Location: app.php?r=courses.show&id=' . $assignment['course_id']);
        exit;
    }
}
