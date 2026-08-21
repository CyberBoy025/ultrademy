<?php
declare(strict_types=1);

/**
 * Demo assessment data. Idempotent — every insert is guarded, so running it twice does
 * not duplicate anything.
 *
 * Kept out of seed.php deliberately: this seeds one module of the system and can be run
 * on an installation that already has its other demo data, without re-running everything.
 *
 *   php database/seed-assessments.php
 */

require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';

$pdo = Database::pdo();

function firstId(PDO $pdo, string $sql, array $params = []): ?int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $v = $stmt->fetchColumn();
    return $v === false ? null : (int) $v;
}

$courseId = firstId($pdo, 'SELECT id FROM courses ORDER BY id LIMIT 1');
if ($courseId === null) {
    exit("No courses exist yet — run database/seed.php first.\n");
}
$author = firstId($pdo, "SELECT u.id FROM users u
    JOIN user_roles ur ON ur.user_id = u.id
    JOIN roles r ON r.id = ur.role_id
    WHERE r.code = 'instructor' LIMIT 1")
    ?? firstId($pdo, 'SELECT id FROM users ORDER BY id LIMIT 1');

$existing = firstId($pdo, 'SELECT id FROM assessments WHERE course_id = :c AND title = :t',
    ['c' => $courseId, 't' => 'Module 1 Knowledge Check']);

if ($existing !== null) {
    exit("Demo assessment already present (id $existing). Nothing to do.\n");
}

echo "Seeding demo assessment on course #$courseId...\n";

$pdo->prepare(
    'INSERT INTO assessments
        (course_id, title, instructions, type, duration_minutes, max_attempts, pass_mark,
         shuffle_questions, show_results, status, created_by)
     VALUES (:c,:t,:i,:type,:dur,:att,:pass,0,:show,:status,:by)'
)->execute([
    'c' => $courseId,
    't' => 'Module 1 Knowledge Check',
    'i' => "Ten minutes, five questions. You may attempt this twice; your best mark counts.\nThe final question is marked by your instructor, so your result will follow rather than appear immediately.",
    'type' => 'quiz',
    'dur' => 10,
    'att' => 2,
    'pass' => 60,
    'show' => 'immediately',
    'status' => 'published',
    'by' => $author,
]);
$assessmentId = (int) $pdo->lastInsertId();

/** @return int question id */
$addQuestion = function (string $type, string $prompt, int $points, ?string $expected, ?string $why, int $order) use ($pdo, $assessmentId): int {
    $pdo->prepare(
        'INSERT INTO assessment_questions (assessment_id, type, prompt, points, expected_answer, explanation, sort_order)
         VALUES (:a,:t,:p,:pts,:exp,:why,:o)'
    )->execute([
        'a' => $assessmentId, 't' => $type, 'p' => $prompt, 'pts' => $points,
        'exp' => $expected, 'why' => $why, 'o' => $order,
    ]);
    return (int) $pdo->lastInsertId();
};

$addOption = function (int $questionId, string $label, bool $correct, int $order) use ($pdo): void {
    $pdo->prepare('INSERT INTO assessment_options (question_id, label, is_correct, sort_order) VALUES (:q,:l,:c,:o)')
        ->execute(['q' => $questionId, 'l' => $label, 'c' => $correct ? 1 : 0, 'o' => $order]);
};

// 1 — single choice
$q = $addQuestion('single_choice', 'Which HTML element carries the page title shown in a browser tab?', 2, null,
    'The <title> element lives inside <head> and is what the tab and search results display.', 1);
$addOption($q, '<title>', true, 1);
$addOption($q, '<header>', false, 2);
$addOption($q, '<h1>', false, 3);
$addOption($q, '<caption>', false, 4);

// 2 — multiple choice
$q = $addQuestion('multi_choice', 'Which of these are valid HTTP request methods? Select all that apply.', 3, null,
    'GET, POST and DELETE are defined by the HTTP specification. SEND is not.', 2);
$addOption($q, 'GET', true, 1);
$addOption($q, 'POST', true, 2);
$addOption($q, 'SEND', false, 3);
$addOption($q, 'DELETE', true, 4);

// 3 — true / false
$q = $addQuestion('true_false', 'CSS is applied to a page in the order the rules are declared, with later rules of equal specificity winning.', 1, 'true',
    'Where specificity ties, the rule declared later wins — the cascade.', 3);
$addOption($q, 'True', true, 1);
$addOption($q, 'False', false, 2);

// 4 — short answer, with an accepted alternative
$addQuestion('short_text', 'What does the abbreviation HTTP stand for?', 2, 'HTTP|Hypertext Transfer Protocol|Hyper Text Transfer Protocol',
    'Hypertext Transfer Protocol — the request/response protocol the web runs on.', 4);

// 5 — essay, so the demo exercises the manual marking path
$addQuestion('essay', 'In your own words, explain why validating user input on the server matters even when the browser already validates it.', 5, null,
    null, 5);

$total = (int) $pdo->query("SELECT COALESCE(SUM(points),0) FROM assessment_questions WHERE assessment_id = $assessmentId")->fetchColumn();

echo "Created assessment #$assessmentId with 5 questions, {$total} marks available.\n";
echo "Sign in as a student enrolled on that course and open My Learning to sit it.\n";
