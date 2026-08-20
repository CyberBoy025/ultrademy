<?php
/** @var array<int,array<string,mixed>> $items @var array<string,mixed> $profile */
$stepActive = 'education';
?>
<div class="wizard">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1>Education</h1>
  <p class="muted">Add every qualification you'd like recruiters to see.</p>

  <div class="card card-body form-card">
    <?php if (!$items): ?>
      <p class="empty-hint">No education added yet.</p>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <div class="list-row">
          <div>
            <div class="title"><?= View::e($it['qualification']) ?></div>
            <div class="sub"><?= View::e($it['institution']) ?><?= $it['field_of_study'] ? ' — ' . View::e($it['field_of_study']) : '' ?></div>
            <div class="meta"><?= View::e((string) ($it['start_year'] ?? '')) ?><?= $it['end_year'] ? ' – ' . View::e((string) $it['end_year']) : ($it['start_year'] ? ' – present' : '') ?></div>
          </div>
          <form method="post" action="app.php?r=profile.education.delete" onsubmit="return confirm('Remove this education entry?')">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
            <button type="submit" class="btn-danger-text">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card card-body form-card">
    <h2>Add Education</h2>
    <form method="post" action="app.php?r=profile.education.store">
      <?= Csrf::field() ?>
      <div class="form-grid">
        <label class="field"><span>Institution</span><input type="text" name="institution" required></label>
        <label class="field"><span>Qualification</span><input type="text" name="qualification" placeholder="e.g. B.Sc Computer Science" required></label>
        <label class="field"><span>Field of study</span><input type="text" name="field_of_study"></label>
        <label class="field"><span>Start year</span><input type="number" min="1960" max="2100" name="start_year"></label>
        <label class="field"><span>End year (leave blank if ongoing)</span><input type="number" min="1960" max="2100" name="end_year"></label>
      </div>
      <button type="submit" class="btn btn-secondary">Add Education</button>
    </form>
  </div>

  <div class="wizard-actions">
    <a class="btn btn-secondary" href="app.php?r=profile.personal">&larr; Back</a>
    <a class="btn btn-primary" href="app.php?r=profile.experience">Continue &rarr;</a>
  </div>
</div>
