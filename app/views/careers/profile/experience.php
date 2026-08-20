<?php
/** @var array<int,array<string,mixed>> $items @var array<string,mixed> $profile */
$stepActive = 'experience';
?>
<div class="wizard">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1>Work Experience</h1>
  <p class="muted">Your roles, most relevant first.</p>

  <div class="card card-body form-card">
    <?php if (!$items): ?>
      <p class="empty-hint">No experience added yet.</p>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <div class="list-row">
          <div>
            <div class="title"><?= View::e($it['job_title']) ?></div>
            <div class="sub"><?= View::e($it['organisation']) ?></div>
            <div class="meta">
              <?= $it['start_date'] ? date('M Y', strtotime($it['start_date'])) : '' ?>
              &ndash; <?= $it['is_current'] ? 'Present' : ($it['end_date'] ? date('M Y', strtotime($it['end_date'])) : '') ?>
            </div>
          </div>
          <form method="post" action="app.php?r=profile.experience.delete" onsubmit="return confirm('Remove this experience entry?')">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
            <button type="submit" class="btn-danger-text">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card card-body form-card">
    <h2>Add Experience</h2>
    <form method="post" action="app.php?r=profile.experience.store">
      <?= Csrf::field() ?>
      <div class="form-grid">
        <label class="field"><span>Organisation</span><input type="text" name="organisation" required></label>
        <label class="field"><span>Job title</span><input type="text" name="job_title" required></label>
        <label class="field">
          <span>Employment type</span>
          <select name="employment_type">
            <option value="">Select…</option>
            <?php foreach (['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship', 'volunteer' => 'Volunteer', 'freelance' => 'Freelance'] as $c => $l): ?>
              <option value="<?= $c ?>"><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span>Start date</span><input type="date" name="start_date"></label>
        <label class="field"><span>End date</span><input type="date" name="end_date"></label>
        <label class="check" style="align-self:end"><input type="checkbox" name="is_current"><span>I currently work here</span></label>
        <label class="field field-full"><span>Responsibilities</span><textarea name="responsibilities"></textarea></label>
        <label class="field field-full"><span>Reason for leaving (optional)</span><input type="text" name="reason_for_leaving"></label>
      </div>
      <button type="submit" class="btn btn-secondary">Add Experience</button>
    </form>
  </div>

  <div class="wizard-actions">
    <a class="btn btn-secondary" href="app.php?r=profile.education">&larr; Back</a>
    <a class="btn btn-primary" href="app.php?r=profile.skills">Continue &rarr;</a>
  </div>
</div>
