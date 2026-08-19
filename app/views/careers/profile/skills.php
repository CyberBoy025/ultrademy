<?php
/** @var array<int,array<string,mixed>> $items @var array<string,mixed> $profile */
$stepActive = 'skills';
?>
<div class="cw-wizard-wrap">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1 style="font-size:1.5rem;margin-bottom:6px">Skills</h1>
  <p style="color:var(--cw-ink-soft);margin-bottom:22px">Technical, professional, software and language skills.</p>

  <div class="cw-form-card">
    <?php if (!$items): ?>
      <p class="cw-empty-hint">No skills added yet.</p>
    <?php else: ?>
      <div class="cw-tag-row" style="margin-bottom:4px">
        <?php foreach ($items as $it): ?>
          <form method="post" action="app.php?r=profile.skills.delete" style="display:inline-flex;align-items:center;gap:6px;background:var(--cw-surface-2);border-radius:20px;padding:6px 6px 6px 13px;margin:4px 4px 0 0">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
            <span style="font-size:0.85rem"><?= View::e($it['skill_name']) ?> <span style="color:var(--cw-ink-faint);font-size:0.74rem"><?= View::e(ApplicantSkill::TYPES[$it['skill_type']] ?? '') ?></span></span>
            <button type="submit" class="cw-btn-danger-text" style="padding:2px 6px" aria-label="Remove <?= View::e($it['skill_name']) ?>">&times;</button>
          </form>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="cw-form-card">
    <h2>Add Skill</h2>
    <form method="post" action="app.php?r=profile.skills.store">
      <?= Csrf::field() ?>
      <div class="cw-form-grid">
        <label class="cw-field"><span>Skill</span><input type="text" name="skill_name" placeholder="e.g. Laravel, Public Speaking" required></label>
        <label class="cw-field">
          <span>Type</span>
          <select class="cw-select" name="skill_type">
            <?php foreach (ApplicantSkill::TYPES as $c => $l): ?><option value="<?= $c ?>"><?= $l ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="cw-field">
          <span>Proficiency (optional)</span>
          <select class="cw-select" name="proficiency">
            <option value="">Select…</option>
            <?php foreach (ApplicantSkill::PROFICIENCIES as $c => $l): ?><option value="<?= $c ?>"><?= $l ?></option><?php endforeach; ?>
          </select>
        </label>
      </div>
      <button type="submit" class="cw-btn cw-btn-outline">Add Skill</button>
    </form>
  </div>

  <div class="cw-wizard-actions">
    <a class="cw-btn cw-btn-outline" href="app.php?r=profile.experience">&larr; Back</a>
    <a class="cw-btn cw-btn-primary" href="app.php?r=profile.certifications">Continue &rarr;</a>
  </div>
</div>
