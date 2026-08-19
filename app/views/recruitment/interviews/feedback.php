<?php /** @var array<string,mixed> $interview @var array<string,mixed> $app @var array<string,mixed>|null $existing */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=recruitment.interviews.mine" style="color:var(--text-3)">My Interviews</a> / Feedback</span>
    <h1><?= View::e($app['job_title']) ?></h1>
    <p><?= View::e($app['applicant_name'] ?: $app['email']) ?> · <?= View::e($app['reference']) ?></p>
  </div>
</div>

<div class="card">
  <form method="post" action="app.php?r=recruitment.interviews.feedback.store">
    <?= Csrf::field() ?><input type="hidden" name="interview_id" value="<?= $interview['id'] ?>">
    <div class="field"><label>Score (0–100)</label><input type="number" min="0" max="100" name="score" value="<?= View::e((string) ($existing['score'] ?? '')) ?>"></div>
    <div class="field"><label>Evaluation</label><textarea name="evaluation" rows="4"><?= View::e($existing['evaluation'] ?? '') ?></textarea></div>
    <div class="field-row">
      <div class="field"><label>Strengths</label><textarea name="strengths" rows="3"><?= View::e($existing['strengths'] ?? '') ?></textarea></div>
      <div class="field"><label>Concerns</label><textarea name="concerns" rows="3"><?= View::e($existing['concerns'] ?? '') ?></textarea></div>
    </div>
    <div class="field">
      <label>Recommendation</label>
      <select name="recommendation">
        <option value="">— Select —</option>
        <?php foreach (InterviewFeedback::RECOMMENDATIONS as $c => $l): ?><option value="<?= $c ?>" <?= ($existing['recommendation'] ?? '') === $c ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
      </select>
    </div>
    <p class="cap" style="margin-bottom:10px">This feedback is internal only and is never shown to the applicant.</p>
    <button type="submit" class="btn primary"><?= $existing ? 'Update Feedback' : 'Submit Feedback' ?></button>
  </form>
</div>
