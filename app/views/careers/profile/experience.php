<?php
/** @var array<int,array<string,mixed>> $items @var array<string,mixed> $profile */
$stepActive = 'experience';
?>
<div class="cw-wizard-wrap">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1 style="font-size:1.5rem;margin-bottom:6px">Work Experience</h1>
  <p style="color:var(--cw-ink-soft);margin-bottom:22px">Your roles, most relevant first.</p>

  <div class="cw-form-card">
    <?php if (!$items): ?>
      <p class="cw-empty-hint">No experience added yet.</p>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <div class="cw-list-item">
          <div>
            <div class="cw-list-item-title"><?= View::e($it['job_title']) ?></div>
            <div class="cw-list-item-sub"><?= View::e($it['organisation']) ?></div>
            <div class="cw-list-item-meta">
              <?= $it['start_date'] ? date('M Y', strtotime($it['start_date'])) : '' ?>
              &ndash; <?= $it['is_current'] ? 'Present' : ($it['end_date'] ? date('M Y', strtotime($it['end_date'])) : '') ?>
            </div>
          </div>
          <form method="post" action="app.php?r=profile.experience.delete" onsubmit="return confirm('Remove this experience entry?')">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
            <button type="submit" class="cw-btn-danger-text">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="cw-form-card">
    <h2>Add Experience</h2>
    <form method="post" action="app.php?r=profile.experience.store">
      <?= Csrf::field() ?>
      <div class="cw-form-grid">
        <label class="cw-field"><span>Organisation</span><input type="text" name="organisation" required></label>
        <label class="cw-field"><span>Job title</span><input type="text" name="job_title" required></label>
        <label class="cw-field">
          <span>Employment type</span>
          <select class="cw-select" name="employment_type">
            <option value="">Select…</option>
            <?php foreach (['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship', 'volunteer' => 'Volunteer', 'freelance' => 'Freelance'] as $c => $l): ?>
              <option value="<?= $c ?>"><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="cw-field"><span>Start date</span><input type="date" name="start_date"></label>
        <label class="cw-field"><span>End date</span><input type="date" name="end_date"></label>
        <label class="cw-check" style="align-self:end"><input type="checkbox" name="is_current"><span>I currently work here</span></label>
        <label class="cw-field cw-field-full"><span>Responsibilities</span><textarea class="cw-textarea" name="responsibilities"></textarea></label>
        <label class="cw-field cw-field-full"><span>Reason for leaving (optional)</span><input type="text" name="reason_for_leaving"></label>
      </div>
      <button type="submit" class="cw-btn cw-btn-outline">Add Experience</button>
    </form>
  </div>

  <div class="cw-wizard-actions">
    <a class="cw-btn cw-btn-outline" href="app.php?r=profile.education">&larr; Back</a>
    <a class="cw-btn cw-btn-primary" href="app.php?r=profile.skills">Continue &rarr;</a>
  </div>
</div>
