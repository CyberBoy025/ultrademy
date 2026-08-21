<?php
declare(strict_types=1);

/**
 * Marking rules for assessments — the part where being wrong means a student is given
 * the wrong grade, so it is tested without needing a database.
 *
 * Assessment::markAnswer() is private because nothing outside the class should mark an
 * answer. Reaching it by reflection is the lesser evil: the alternative is widening the
 * API purely so a test can reach it, which makes the untested-but-public surface bigger.
 */

require_once dirname(__DIR__) . '/app/models/Assessment.php';

$mark = (new ReflectionClass(Assessment::class))->getMethod('markAnswer');
$mark->setAccessible(true);

/** @return array{0:?int,1:?int} */
$run = static function (array $question, array $selected = [], ?string $text = null) use ($mark): array {
    return $mark->invoke(null, $question, $selected, $text);
};

$choice = static fn(string $type, array $options, int $points = 2): array => [
    'type' => $type, 'points' => $points, 'expected_answer' => null, 'options' => $options,
];
$opt = static fn(int $id, bool $correct): array => ['id' => $id, 'is_correct' => $correct ? 1 : 0];

// ------------------------------------------------------------------ single choice

test('single choice: the correct option scores full marks', function () use ($run, $choice, $opt) {
    $q = $choice('single_choice', [$opt(1, true), $opt(2, false), $opt(3, false)]);
    assertSame_([2, 1], $run($q, [1]));
});

test('single choice: a wrong option scores zero', function () use ($run, $choice, $opt) {
    $q = $choice('single_choice', [$opt(1, true), $opt(2, false)]);
    assertSame_([0, 0], $run($q, [2]));
});

test('single choice: no answer scores zero, never null', function () use ($run, $choice, $opt) {
    $q = $choice('single_choice', [$opt(1, true), $opt(2, false)]);
    assertSame_([0, 0], $run($q, []));
});

// ------------------------------------------------------------------- multi choice

test('multi choice: every correct option and nothing else scores full marks', function () use ($run, $choice, $opt) {
    $q = $choice('multi_choice', [$opt(1, true), $opt(2, true), $opt(3, false)]);
    assertSame_([2, 1], $run($q, [1, 2]));
});

test('multi choice: selection order does not matter', function () use ($run, $choice, $opt) {
    $q = $choice('multi_choice', [$opt(1, true), $opt(2, true), $opt(3, false)]);
    assertSame_([2, 1], $run($q, [2, 1]));
});

test('multi choice: a partial answer scores zero, not partial credit', function () use ($run, $choice, $opt) {
    $q = $choice('multi_choice', [$opt(1, true), $opt(2, true), $opt(3, false)]);
    assertSame_([0, 0], $run($q, [1]), 'all-or-nothing is the documented rule');
});

test('multi choice: correct answers plus a wrong one scores zero', function () use ($run, $choice, $opt) {
    $q = $choice('multi_choice', [$opt(1, true), $opt(2, true), $opt(3, false)]);
    assertSame_([0, 0], $run($q, [1, 2, 3]));
});

test('multi choice: a duplicated selection does not defeat the comparison', function () use ($run, $choice, $opt) {
    // A hand-built POST could repeat an option id. It must not turn a correct answer wrong.
    $q = $choice('multi_choice', [$opt(1, true), $opt(2, true), $opt(3, false)]);
    assertSame_([2, 1], $run($q, [1, 2, 2]));
});

test('a question with no correct option is never marked correct', function () use ($run, $choice, $opt) {
    // Guards against an authoring mistake silently awarding marks to everyone who
    // selected nothing, which is what a naive empty-set comparison would do.
    $q = $choice('single_choice', [$opt(1, false), $opt(2, false)]);
    assertSame_([0, 0], $run($q, []));
});

// -------------------------------------------------------------------- true / false

test('true/false behaves as a two-option single choice', function () use ($run, $choice, $opt) {
    $q = $choice('true_false', [$opt(9, true), $opt(10, false)], 1);
    assertSame_([1, 1], $run($q, [9]));
    assertSame_([0, 0], $run($q, [10]));
});

// -------------------------------------------------------------------- short answer

$short = static fn(string $expected, int $points = 3): array => [
    'type' => 'short_text', 'points' => $points, 'expected_answer' => $expected, 'options' => [],
];

test('short answer: an exact match scores full marks', function () use ($run, $short) {
    assertSame_([3, 1], $run($short('Paris'), [], 'Paris'));
});

test('short answer: case is ignored', function () use ($run, $short) {
    assertSame_([3, 1], $run($short('Paris'), [], 'paris'));
});

test('short answer: surrounding and repeated whitespace is ignored', function () use ($run, $short) {
    assertSame_([3, 1], $run($short('New York'), [], "  new    york  "));
});

test('short answer: any listed alternative is accepted', function () use ($run, $short) {
    $q = $short('HTTP|Hypertext Transfer Protocol');
    assertSame_([3, 1], $run($q, [], 'hypertext transfer protocol'));
    assertSame_([3, 1], $run($q, [], 'http'));
});

test('short answer: a wrong answer scores zero', function () use ($run, $short) {
    assertSame_([0, 0], $run($short('Paris'), [], 'London'));
});

test('short answer: blank scores zero', function () use ($run, $short) {
    assertSame_([0, 0], $run($short('Paris'), [], ''));
    assertSame_([0, 0], $run($short('Paris'), [], null));
});

test('short answer: a question with no expected answer cannot be passed by guessing', function () use ($run, $short) {
    assertSame_([0, 0], $run($short(''), [], 'anything'));
});

// --------------------------------------------------------------------------- essay

test('essay returns null so the attempt waits for a human', function () use ($run) {
    $q = ['type' => 'essay', 'points' => 10, 'expected_answer' => null, 'options' => []];
    assertSame_([null, null], $run($q, [], 'A long and thoughtful answer.'));
});

test('a zero-mark question still marks correctly, it just awards nothing', function () use ($run, $choice, $opt) {
    $q = $choice('single_choice', [$opt(1, true), $opt(2, false)], 0);
    assertSame_([0, 1], $run($q, [1]), 'correctness and marks are separate facts');
});

// ------------------------------------------------------------------ open / closed

test('an unpublished assessment is never open', function () {
    assertFalse_(Assessment::isOpen(['status' => 'draft', 'opens_at' => null, 'closes_at' => null]));
    assertFalse_(Assessment::isOpen(['status' => 'closed', 'opens_at' => null, 'closes_at' => null]));
});

test('a published assessment with no window is open', function () {
    assertTrue_(Assessment::isOpen(['status' => 'published', 'opens_at' => null, 'closes_at' => null]));
});

test('a published assessment is shut before it opens and after it closes', function () {
    $future = date('Y-m-d H:i:s', time() + 3600);
    $past   = date('Y-m-d H:i:s', time() - 3600);
    assertFalse_(Assessment::isOpen(['status' => 'published', 'opens_at' => $future, 'closes_at' => null]));
    assertFalse_(Assessment::isOpen(['status' => 'published', 'opens_at' => null, 'closes_at' => $past]));
    assertTrue_(Assessment::isOpen(['status' => 'published', 'opens_at' => $past, 'closes_at' => $future]));
});

// ------------------------------------------------------------------------- clock

test('an untimed attempt never expires', function () {
    $attempt = ['started_at' => date('Y-m-d H:i:s', time() - 86400), 'assessment_id' => 1];
    assertFalse_(Assessment::hasExpired($attempt, ['duration_minutes' => null]));
});

test('a timed attempt expires once its duration has passed', function () {
    $attempt = ['started_at' => date('Y-m-d H:i:s', time() - 3600), 'assessment_id' => 1];
    assertTrue_(Assessment::hasExpired($attempt, ['duration_minutes' => 30]));
    assertFalse_(Assessment::hasExpired($attempt, ['duration_minutes' => 120]));
});

test('remaining time is never negative', function () {
    $attempt = ['started_at' => date('Y-m-d H:i:s', time() - 7200)];
    assertSame_(0, Assessment::secondsRemaining($attempt, ['duration_minutes' => 10]));
    assertSame_(null, Assessment::secondsRemaining($attempt, ['duration_minutes' => null]));
});

// ------------------------------------------------------------- result visibility

test('an ungraded attempt never shows a result', function () {
    $a = ['show_results' => 'immediately', 'closes_at' => null];
    assertFalse_(Assessment::resultsVisible($a, ['status' => 'submitted']));
});

test('show_results=never withholds the mark even once graded', function () {
    $a = ['show_results' => 'never', 'closes_at' => null];
    assertFalse_(Assessment::resultsVisible($a, ['status' => 'graded']));
});

test('show_results=after_close withholds the mark until the paper closes', function () {
    $graded = ['status' => 'graded'];
    assertFalse_(Assessment::resultsVisible(['show_results' => 'after_close', 'closes_at' => date('Y-m-d H:i:s', time() + 3600)], $graded));
    assertTrue_(Assessment::resultsVisible(['show_results' => 'after_close', 'closes_at' => date('Y-m-d H:i:s', time() - 3600)], $graded));
});

test('after_close with no closing date never releases — it does not fall open', function () {
    // A paper set to "after close" but given no closing date has no moment at which
    // release is correct. Defaulting to visible would leak every mark.
    assertFalse_(Assessment::resultsVisible(['show_results' => 'after_close', 'closes_at' => null], ['status' => 'graded']));
});
