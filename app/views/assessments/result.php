<?php /** @var array $assessment @var array $attempt @var bool $visible @var array $answers
 *  @var bool $isMarker @var bool $isOwner */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px">
      <a href="<?= $isOwner ? 'app.php?r=learn.course&id=' . (int) $assessment['course_id'] : 'app.php?r=assessments.attempts&id=' . (int) $assessment['id'] ?>" style="color:var(--text-3)"><?= View::e($isOwner ? $assessment['course_title'] : $assessment['title']) ?></a> / Result
    </span>
    <h1><?= View::e($assessment['title']) ?></h1>
    <p>
      <?php if (!$isOwner): ?><?= View::e($attempt['student_name'] ?: $attempt['email']) ?> · <?php endif; ?>
      Attempt <?= (int) $attempt['attempt_no'] ?>
      <?php if ($attempt['submitted_at']): ?> · submitted <?= View::e(date('d M Y H:i', strtotime((string) $attempt['submitted_at']))) ?><?php endif; ?>
      <?php if ($attempt['time_spent_seconds'] !== null): ?> · took <?= (int) round(((int) $attempt['time_spent_seconds']) / 60) ?> min<?php endif; ?>
    </p>
  </div>
</div>

<?php if ($attempt['status'] === 'submitted' && (int) $attempt['needs_manual_grade'] === 1): ?>
  <div class="card" style="margin-bottom:20px;border-left:3px solid var(--warning)">
    <div class="chead"><h3>Awaiting marking</h3></div>
    <p class="cap" style="margin:0">
      Your written answers are with your instructor. Your mark is deliberately withheld until
      every question has been marked — a provisional score that later changes is worse than
      no score at all.
    </p>
  </div>

<?php elseif (!$visible): ?>
  <div class="card" style="margin-bottom:20px">
    <div class="chead"><h3>Result not available</h3></div>
    <p class="cap" style="margin:0">
      <?php if ($assessment['show_results'] === 'never'): ?>
        Marks for this assessment are not published to candidates.
      <?php elseif ($assessment['show_results'] === 'after_close'): ?>
        Results are released after the assessment closes<?= $assessment['closes_at'] ? ' on ' . View::e(date('d M Y H:i', strtotime((string) $assessment['closes_at']))) : '' ?>.
      <?php else: ?>
        Not yet marked.
      <?php endif; ?>
    </p>
  </div>

<?php else: ?>
  <?php $passed = (int) $attempt['passed'] === 1; ?>
  <div class="card" style="margin-bottom:20px">
    <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap">
      <span class="pct" style="color:<?= $passed ? 'var(--success)' : 'var(--error)' ?>"><?= rtrim(rtrim((string) $attempt['score_percent'], '0'), '.') ?>%</span>
      <span class="cap"><?= (int) $attempt['score_points'] ?> of <?= (int) $attempt['max_points'] ?> mark(s)</span>
      <span class="status-pill <?= $passed ? 'success' : 'error' ?>" style="margin-left:auto"><?= $passed ? 'Passed' : 'Not passed' ?></span>
    </div>
    <div class="bar" style="margin-top:12px"><span style="width:<?= min(100, (float) $attempt['score_percent']) ?>%;<?= $passed ? '' : 'background:var(--error)' ?>"></span></div>
    <p class="cap" style="margin-top:10px">Pass mark for this assessment is <?= (int) $assessment['pass_mark'] ?>%.</p>
  </div>

  <h2 class="sec-title">Review</h2>
  <?php foreach ($answers as $i => $a):
    $correct = $a['is_correct'] === null ? null : (int) $a['is_correct'] === 1; ?>
  <div class="card" style="margin-bottom:12px">
    <div class="chead">
      <h3>Question <?= $i + 1 ?></h3>
      <div style="display:flex;gap:8px;align-items:center">
        <span class="cap"><?= $a['awarded_points'] === null ? '—' : (int) $a['awarded_points'] ?> / <?= (int) $a['points'] ?></span>
        <?php if ($correct === true): ?><span class="status-pill success">Correct</span>
        <?php elseif ($correct === false): ?><span class="status-pill error">Incorrect</span>
        <?php else: ?><span class="status-pill neutral">Marked by hand</span><?php endif; ?>
      </div>
    </div>
    <p style="font-size:13.5px;margin-bottom:12px"><?= nl2br(View::e($a['prompt'])) ?></p>

    <?php if ($a['options']): ?>
      <?php foreach ($a['options'] as $o):
        $chosen = in_array((int) $o['id'], array_map('intval', $a['selected']), true);
        $right  = isset($o['is_correct']) && (int) $o['is_correct'] === 1; ?>
        <div style="display:flex;align-items:center;gap:9px;padding:7px 0;font-size:13px;color:<?= $right ? 'var(--success)' : ($chosen ? 'var(--error)' : 'var(--text-3)') ?>">
          <span style="flex:none;width:16px"><?= $chosen ? '●' : '○' ?></span>
          <span><?= View::e($o['label']) ?><?= $right ? ' — correct answer' : '' ?><?= $chosen && !$right ? ' — your answer' : '' ?></span>
        </div>
      <?php endforeach; ?>

    <?php elseif ($a['type'] === 'short_text'): ?>
      <p class="cap">Your answer: <strong style="color:var(--text)"><?= View::e((string) ($a['response_text'] ?? '—')) ?></strong></p>
      <?php if ($correct === false && $a['expected_answer']): ?>
        <p class="cap">Accepted: <?= View::e(str_replace('|', ' / ', (string) $a['expected_answer'])) ?></p>
      <?php endif; ?>

    <?php else: ?>
      <div class="card" style="background:var(--surface-muted);margin-bottom:10px">
        <p class="cap" style="margin-bottom:4px">Your answer</p>
        <p style="font-size:13px"><?= nl2br(View::e((string) ($a['response_text'] ?? '—'))) ?></p>
      </div>
    <?php endif; ?>

    <?php if ($a['feedback']): ?>
      <div class="card" style="background:var(--surface-muted);margin-top:10px">
        <p class="cap" style="margin-bottom:4px">Marker's feedback</p>
        <p style="font-size:13px"><?= nl2br(View::e((string) $a['feedback'])) ?></p>
      </div>
    <?php endif; ?>

    <?php if ($a['explanation']): ?>
      <p class="cap" style="margin-top:10px"><strong>Why:</strong> <?= View::e((string) $a['explanation']) ?></p>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<div class="card">
  <a class="btn" href="<?= $isOwner ? 'app.php?r=learn.course&id=' . (int) $assessment['course_id'] : 'app.php?r=assessments.attempts&id=' . (int) $assessment['id'] ?>">
    <?= $isOwner ? 'Back to course' : 'Back to results' ?>
  </a>
</div>
