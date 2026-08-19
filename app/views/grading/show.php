<?php /** @var array $submission */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=grading" style="color:var(--text-3)">Grading</a> / <?= View::e($submission['assignment_title']) ?></span>
    <h1><?= View::e($submission['student_name'] ?: 'Submission') ?></h1>
    <p><?= View::e($submission['assignment_title']) ?> · submitted <?= View::e(date('d M Y H:i', strtotime($submission['submitted_at']))) ?></p>
  </div>
</div>

<div class="row row-b">
  <div class="card">
    <div class="chead"><h3>Submission</h3></div>
    <?php if (trim((string) $submission['body']) !== ''): ?>
      <div style="font-size:13.5px;line-height:1.7;color:var(--text-2)"><?= nl2br(View::e($submission['body'])) ?></div>
    <?php else: ?>
      <p class="cap">No written answer.</p>
    <?php endif; ?>
    <?php if ($submission['original_name']): ?>
      <div style="margin-top:14px">
        <a class="btn sm" href="app.php?r=submissions.download&id=<?= $submission['id'] ?>">Download <?= View::e($submission['original_name']) ?></a>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="chead"><h3>Grade</h3></div>
    <form method="post" action="app.php?r=grading.grade">
      <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $submission['id'] ?>">
      <div class="field">
        <label>Score (out of <?= (int) $submission['max_score'] ?>)</label>
        <input type="number" name="score" min="0" max="<?= (int) $submission['max_score'] ?>" value="<?= $submission['score'] !== null ? (int) $submission['score'] : '' ?>" required>
      </div>
      <div class="field">
        <label>Feedback</label>
        <textarea name="feedback" rows="6" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"><?= View::e($submission['feedback'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn primary">Save Grade</button>
    </form>
  </div>
</div>
