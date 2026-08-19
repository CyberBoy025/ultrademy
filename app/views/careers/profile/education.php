<?php
/** @var array<int,array<string,mixed>> $items @var array<string,mixed> $profile */
$stepActive = 'education';
?>
<div class="cw-wizard-wrap">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1 style="font-size:1.5rem;margin-bottom:6px">Education</h1>
  <p style="color:var(--cw-ink-soft);margin-bottom:22px">Add every qualification you'd like recruiters to see.</p>

  <div class="cw-form-card">
    <?php if (!$items): ?>
      <p class="cw-empty-hint">No education added yet.</p>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <div class="cw-list-item">
          <div>
            <div class="cw-list-item-title"><?= View::e($it['qualification']) ?></div>
            <div class="cw-list-item-sub"><?= View::e($it['institution']) ?><?= $it['field_of_study'] ? ' — ' . View::e($it['field_of_study']) : '' ?></div>
            <div class="cw-list-item-meta"><?= View::e((string) ($it['start_year'] ?? '')) ?><?= $it['end_year'] ? ' – ' . View::e((string) $it['end_year']) : ($it['start_year'] ? ' – present' : '') ?></div>
          </div>
          <form method="post" action="app.php?r=profile.education.delete" onsubmit="return confirm('Remove this education entry?')">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
            <button type="submit" class="cw-btn-danger-text">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="cw-form-card">
    <h2>Add Education</h2>
    <form method="post" action="app.php?r=profile.education.store">
      <?= Csrf::field() ?>
      <div class="cw-form-grid">
        <label class="cw-field"><span>Institution</span><input type="text" name="institution" required></label>
        <label class="cw-field"><span>Qualification</span><input type="text" name="qualification" placeholder="e.g. B.Sc Computer Science" required></label>
        <label class="cw-field"><span>Field of study</span><input type="text" name="field_of_study"></label>
        <label class="cw-field"><span>Start year</span><input type="number" min="1960" max="2100" name="start_year"></label>
        <label class="cw-field"><span>End year (leave blank if ongoing)</span><input type="number" min="1960" max="2100" name="end_year"></label>
      </div>
      <button type="submit" class="cw-btn cw-btn-outline">Add Education</button>
    </form>
  </div>

  <div class="cw-wizard-actions">
    <a class="cw-btn cw-btn-outline" href="app.php?r=profile.personal">&larr; Back</a>
    <a class="cw-btn cw-btn-primary" href="app.php?r=profile.experience">Continue &rarr;</a>
  </div>
</div>
