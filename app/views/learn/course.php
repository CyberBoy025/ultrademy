<?php
/** @var array $course @var array $outline @var array $progress @var int $percent
 *  @var array $assignments @var array $submissions @var array|null $enrolment
 *  @var bool $complete @var array|null $certificate */
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=learn" style="color:var(--text-3)">My Learning</a> / <?= View::e($course['title']) ?></span>
    <h1><?= View::e($course['title']) ?></h1>
    <p><?= (int) $course['estimated_minutes'] ?> min · <?= count($outline) ?> module(s)</p>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div style="display:flex;align-items:baseline;gap:6px"><span class="pct"><?= $percent ?>%</span><span class="cap">complete</span></div>
  <div class="bar"><span style="width:<?= $percent ?>%"></span></div>
  <?php if ($complete): ?>
    <p class="cap" style="margin-top:12px;color:var(--success)">
      All lessons complete.
      <?php if ($certificate): ?>
        Your certificate serial is <strong><?= View::e($certificate['serial']) ?></strong> —
        <a href="app.php?r=learn.certificates" style="color:var(--brand-cyan-text);font-weight:600">view it</a>.
      <?php elseif (!Entitlements::can('certificates')): ?>
        Certificates aren't included in your current package.
      <?php endif; ?>
    </p>
  <?php endif; ?>
</div>

<?php if ($course['objectives'] || $course['prerequisites']): ?>
<div class="row row-b">
  <?php if ($course['objectives']): ?>
  <div class="card"><div class="chead"><h3>What you'll learn</h3></div><p style="font-size:13px;color:var(--text-2)"><?= nl2br(View::e($course['objectives'])) ?></p></div>
  <?php endif; ?>
  <?php if ($course['prerequisites']): ?>
  <div class="card"><div class="chead"><h3>Prerequisites</h3></div><p style="font-size:13px;color:var(--text-2)"><?= nl2br(View::e($course['prerequisites'])) ?></p></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<h2 class="sec-title">Course Outline</h2>
<?php foreach ($outline as $mi => $m): ?>
<div class="card" style="margin-bottom:12px">
  <div class="chead"><h3><?= ($mi + 1) . '. ' . View::e($m['title']) ?></h3></div>
  <?php if ($m['summary']): ?><p class="cap" style="margin-bottom:10px"><?= View::e($m['summary']) ?></p><?php endif; ?>
  <div class="queue">
    <?php foreach ($m['lessons'] as $l):
      $done = isset($progress[(int) $l['id']]) && $progress[(int) $l['id']]['completed_at'] !== null; ?>
    <div class="queue-item">
      <div class="queue-ico" style="<?= $done ? 'background:var(--cyan-50);color:var(--brand-cyan-text)' : '' ?>">
        <?php if ($done): ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
        <?php else: ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="9"/></svg>
        <?php endif; ?>
      </div>
      <div class="queue-t">
        <h4><?= View::e($l['title']) ?></h4>
        <p><?= View::e(ucfirst($l['content_type'])) ?> · <?= (int) $l['duration_minutes'] ?> min<?= (int) $l['is_preview'] === 1 ? ' · Preview' : '' ?></p>
      </div>
      <a class="btn sm <?= $done ? '' : 'primary' ?>" href="app.php?r=learn.lesson&id=<?= $l['id'] ?>"><?= $done ? 'Review' : 'Open' ?></a>
    </div>
    <?php endforeach; ?>
    <?php if (!$m['lessons']): ?><p class="cap" style="padding:8px 0">No lessons in this module yet.</p><?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<?php if ($assignments): ?>
<h2 class="sec-title">Assignments</h2>
<?php foreach ($assignments as $a):
  $sub = $submissions[(int) $a['id']] ?? null;
  $overdue = $a['due_at'] && strtotime($a['due_at']) < time() && !$sub; ?>
<div class="card" style="margin-bottom:12px">
  <div class="chead">
    <h3><?= View::e($a['title']) ?></h3>
    <?php if ($sub && $sub['status'] === 'graded'): ?>
      <span class="status-pill success"><?= (int) $sub['score'] ?>/<?= (int) $a['max_score'] ?></span>
    <?php elseif ($sub): ?>
      <span class="status-pill warning">Awaiting grade</span>
    <?php elseif ($overdue): ?>
      <span class="status-pill error">Overdue</span>
    <?php else: ?>
      <span class="status-pill neutral">Not submitted</span>
    <?php endif; ?>
  </div>
  <?php if ($a['instructions']): ?><p style="font-size:13px;color:var(--text-2);margin-bottom:8px"><?= nl2br(View::e($a['instructions'])) ?></p><?php endif; ?>
  <p class="cap" style="margin-bottom:12px">
    <?= $a['due_at'] ? 'Due ' . View::e(date('d M Y H:i', strtotime($a['due_at']))) : 'No due date' ?>
    · Max <?= (int) $a['max_score'] ?>
  </p>

  <?php if ($sub && $sub['status'] === 'graded' && $sub['feedback']): ?>
    <div class="card" style="background:var(--surface-muted);margin-bottom:12px">
      <p class="cap" style="margin-bottom:4px">Feedback</p>
      <p style="font-size:13px"><?= nl2br(View::e($sub['feedback'])) ?></p>
    </div>
  <?php endif; ?>

  <?php if ($sub): ?>
    <p class="cap" style="margin-bottom:10px">
      Submitted <?= View::e(date('d M Y H:i', strtotime($sub['submitted_at']))) ?>
      <?php if ($sub['original_name']): ?>
        · <a href="app.php?r=submissions.download&id=<?= $sub['id'] ?>" style="color:var(--brand-cyan-text)"><?= View::e($sub['original_name']) ?></a>
      <?php endif; ?>
    </p>
  <?php endif; ?>

  <?php if (!$sub || (int) $a['allows_resubmission'] === 1): ?>
  <form method="post" action="app.php?r=learn.submit" enctype="multipart/form-data">
    <?= Csrf::field() ?><input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
    <?php if ((int) $a['allows_text'] === 1): ?>
    <div class="field">
      <label>Your answer</label>
      <textarea name="body" rows="4" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"><?= View::e($sub['body'] ?? '') ?></textarea>
    </div>
    <?php endif; ?>
    <?php if ((int) $a['allows_file'] === 1): ?>
    <div class="field"><label>Attach a file</label><input type="file" name="file"></div>
    <?php endif; ?>
    <button type="submit" class="btn primary btn-sm"><?= $sub ? 'Resubmit' : 'Submit' ?></button>
  </form>
  <?php elseif ($sub): ?>
    <p class="cap">This assignment does not allow resubmission.</p>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
