<?php /** @var array $assessment @var array $attempt @var array $answers */
$autoPoints = 0; $autoMax = 0;
foreach ($answers as $a) {
    if ($a['type'] !== 'essay') { $autoPoints += (int) $a['awarded_points']; $autoMax += (int) $a['points']; }
} ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=assessments.marking" style="color:var(--text-3)">Marking</a> / <?= View::e($assessment['title']) ?></span>
    <h1><?= View::e($attempt['student_name'] ?: $attempt['email']) ?></h1>
    <p>
      Attempt <?= (int) $attempt['attempt_no'] ?> ·
      submitted <?= $attempt['submitted_at'] ? View::e(date('d M Y H:i', strtotime((string) $attempt['submitted_at']))) : '—' ?> ·
      auto-marked <?= $autoPoints ?>/<?= $autoMax ?>
    </p>
  </div>
</div>

<div class="card" style="margin-bottom:20px;border-left:3px solid var(--brand-cyan-text)">
  <p class="cap" style="margin:0">
    Only written answers need your decision — everything else is already marked. Leave a
    box blank to defer it; the attempt stays unmarked until every written answer has a
    mark, so the candidate is never shown a score that later moves.
  </p>
</div>

<form method="post" action="app.php?r=assessments.marks.save">
  <?= Csrf::field() ?><input type="hidden" name="attempt_id" value="<?= (int) $attempt['id'] ?>">

  <?php foreach ($answers as $i => $a): ?>
  <div class="card" style="margin-bottom:12px">
    <div class="chead">
      <h3>Question <?= $i + 1 ?></h3>
      <div style="display:flex;gap:8px;align-items:center">
        <span class="status-pill neutral"><?= View::e(Assessment::QUESTION_TYPES[$a['type']] ?? $a['type']) ?></span>
        <span class="cap">max <?= (int) $a['points'] ?></span>
      </div>
    </div>
    <p style="font-size:13.5px;margin-bottom:12px"><?= nl2br(View::e($a['prompt'])) ?></p>

    <?php if ($a['type'] === 'essay'): ?>
      <div class="card" style="background:var(--surface-muted);margin-bottom:14px">
        <p class="cap" style="margin-bottom:4px">Candidate's answer</p>
        <p style="font-size:13px;line-height:1.7"><?= nl2br(View::e((string) ($a['response_text'] ?? '(left blank)'))) ?></p>
      </div>
      <div class="row row-b">
        <div class="field">
          <label>Marks awarded (out of <?= (int) $a['points'] ?>)</label>
          <input type="number" name="points[<?= (int) $a['id'] ?>]" min="0" max="<?= (int) $a['points'] ?>"
                 value="<?= $a['awarded_points'] !== null ? (int) $a['awarded_points'] : '' ?>">
        </div>
        <div class="field">
          <label>Feedback</label>
          <input type="text" name="feedback[<?= (int) $a['id'] ?>]" maxlength="500" value="<?= View::e((string) ($a['feedback'] ?? '')) ?>">
        </div>
      </div>

    <?php else: ?>
      <p class="cap">
        <?= (int) $a['is_correct'] === 1 ? 'Correct' : 'Incorrect' ?> —
        <?= (int) $a['awarded_points'] ?>/<?= (int) $a['points'] ?> awarded automatically.
      </p>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

  <div class="card">
    <button type="submit" class="btn primary">Save marks</button>
  </div>
</form>
