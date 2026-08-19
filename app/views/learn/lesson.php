<?php
/** @var array $lesson @var array $materials @var array|null $enrolment @var bool $isComplete
 *  @var int|null $prevId @var int|null $nextId @var int $percent @var bool $isStaffViewer */
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px">
      <a href="app.php?r=learn.course&id=<?= $lesson['course_id'] ?>" style="color:var(--text-3)"><?= View::e($lesson['course_title']) ?></a> /
      <?= View::e($lesson['module_title']) ?>
    </span>
    <h1><?= View::e($lesson['title']) ?></h1>
    <p><?= View::e(ucfirst($lesson['content_type'])) ?> · <?= (int) $lesson['duration_minutes'] ?> min
      <?php if ((int) $lesson['is_preview'] === 1): ?> · <span class="status-pill info">Preview</span><?php endif; ?>
    </p>
  </div>
  <?php if ($isStaffViewer): ?>
  <div class="actions"><a class="btn" href="app.php?r=lessons.edit&id=<?= $lesson['id'] ?>">Edit Lesson</a></div>
  <?php endif; ?>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="bar" style="margin-top:0"><span style="width:<?= $percent ?>%"></span></div>
  <p class="cap" style="margin-top:8px"><?= $percent ?>% of this course complete</p>
</div>

<div class="card" style="margin-bottom:16px">
  <?php if (trim((string) $lesson['body']) !== ''): ?>
    <div style="font-size:14px;line-height:1.75;color:var(--text)"><?= nl2br(View::e($lesson['body'])) ?></div>
  <?php else: ?>
    <p class="cap">This lesson has no written content — see the materials below.</p>
  <?php endif; ?>
</div>

<?php if ($materials): ?>
<h2 class="sec-title">Materials</h2>
<div class="card" style="margin-bottom:16px">
  <div class="queue">
    <?php foreach ($materials as $m): ?>
    <div class="queue-item">
      <div class="queue-ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
      </div>
      <div class="queue-t">
        <h4><?= View::e($m['title']) ?></h4>
        <p><?= View::e(ucfirst($m['type'])) ?><?= $m['size_bytes'] ? ' · ' . View::e(Upload::humanSize((int) $m['size_bytes'])) : '' ?></p>
      </div>
      <?php if ($m['type'] === 'link' || (int) $m['is_downloadable'] === 1 || $isStaffViewer): ?>
        <a class="btn sm" href="app.php?r=materials.download&id=<?= $m['id'] ?>"<?= $m['type'] === 'link' ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
          <?= $m['type'] === 'link' ? 'Open' : 'Download' ?>
        </a>
      <?php else: ?>
        <span class="status-pill neutral">View only</span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div style="display:flex;gap:8px">
      <?php if ($prevId): ?><a class="btn" href="app.php?r=learn.lesson&id=<?= $prevId ?>">← Previous</a><?php endif; ?>
      <?php if ($nextId): ?><a class="btn" href="app.php?r=learn.lesson&id=<?= $nextId ?>">Next →</a><?php endif; ?>
    </div>
    <?php if (!$isStaffViewer): ?>
    <form method="post" action="app.php?r=learn.progress">
      <?= Csrf::field() ?>
      <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
      <input type="hidden" name="done" value="<?= $isComplete ? '0' : '1' ?>">
      <button type="submit" class="btn <?= $isComplete ? '' : 'primary' ?>">
        <?= $isComplete ? 'Mark as not complete' : 'Mark as complete' ?>
      </button>
    </form>
    <?php else: ?>
      <span class="cap">Viewing as staff — progress isn't tracked for your account.</span>
    <?php endif; ?>
  </div>
</div>
