<?php
/** @var array<int,array<string,mixed>> $items @var array<string,mixed> $profile */
$stepActive = 'skills';
?>
<div class="wizard">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1>Skills</h1>
  <p class="muted">Technical, professional, software and language skills.</p>

  <div class="card card-body form-card">
    <?php if (!$items): ?>
      <p class="empty-hint">No skills added yet.</p>
    <?php else: ?>
      <div class="tag-row">
        <?php foreach ($items as $it): ?>
          <form method="post" action="app.php?r=profile.skills.delete" class="pill pill-removable">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
            <span><?= View::e($it['skill_name']) ?> <span class="muted"><?= View::e(ApplicantSkill::TYPES[$it['skill_type']] ?? '') ?></span></span>
            <button type="submit" aria-label="Remove <?= View::e($it['skill_name']) ?>">&times;</button>
          </form>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card card-body form-card">
    <h2>Add Skill</h2>
    <form method="post" action="app.php?r=profile.skills.store">
      <?= Csrf::field() ?>
      <div class="form-grid">
        <label class="field"><span>Skill</span><input type="text" name="skill_name" placeholder="e.g. Laravel, Public Speaking" required></label>
        <label class="field">
          <span>Type</span>
          <select name="skill_type">
            <?php foreach (ApplicantSkill::TYPES as $c => $l): ?><option value="<?= $c ?>"><?= $l ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="field">
          <span>Proficiency (optional)</span>
          <select name="proficiency">
            <option value="">Select…</option>
            <?php foreach (ApplicantSkill::PROFICIENCIES as $c => $l): ?><option value="<?= $c ?>"><?= $l ?></option><?php endforeach; ?>
          </select>
        </label>
      </div>
      <button type="submit" class="btn btn-secondary">Add Skill</button>
    </form>
  </div>

  <div class="wizard-actions">
    <a class="btn btn-secondary" href="app.php?r=profile.experience">&larr; Back</a>
    <a class="btn btn-primary" href="app.php?r=profile.certifications">Continue &rarr;</a>
  </div>
</div>
