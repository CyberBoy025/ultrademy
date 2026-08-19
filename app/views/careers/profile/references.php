<?php
/** @var array<int,array<string,mixed>> $items @var array<string,mixed> $profile */
$stepActive = 'references';
?>
<div class="cw-wizard-wrap">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1 style="font-size:1.5rem;margin-bottom:6px">References</h1>
  <p style="color:var(--cw-ink-soft);margin-bottom:22px">People who can speak to your work, where a role requires them.</p>

  <div class="cw-form-card">
    <?php if (!$items): ?>
      <p class="cw-empty-hint">No references added yet.</p>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <div class="cw-list-item">
          <div>
            <div class="cw-list-item-title"><?= View::e($it['name']) ?></div>
            <div class="cw-list-item-sub"><?= View::e(trim(($it['relationship'] ?? '') . ($it['organisation'] ? ' — ' . $it['organisation'] : ''))) ?></div>
            <div class="cw-list-item-meta"><?= View::e($it['email'] ?? '') ?><?= $it['phone'] ? ' · ' . View::e($it['phone']) : '' ?></div>
          </div>
          <form method="post" action="app.php?r=profile.references.delete" onsubmit="return confirm('Remove this reference?')">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
            <button type="submit" class="cw-btn-danger-text">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="cw-form-card">
    <h2>Add Reference</h2>
    <form method="post" action="app.php?r=profile.references.store">
      <?= Csrf::field() ?>
      <div class="cw-form-grid">
        <label class="cw-field"><span>Full name</span><input type="text" name="name" required></label>
        <label class="cw-field"><span>Relationship</span><input type="text" name="relationship" placeholder="e.g. Former Manager"></label>
        <label class="cw-field"><span>Organisation</span><input type="text" name="organisation"></label>
        <label class="cw-field"><span>Email</span><input type="email" name="email"></label>
        <label class="cw-field"><span>Phone</span><input type="tel" name="phone"></label>
      </div>
      <button type="submit" class="cw-btn cw-btn-outline">Add Reference</button>
    </form>
  </div>

  <div class="cw-wizard-actions">
    <a class="cw-btn cw-btn-outline" href="app.php?r=profile.resume">&larr; Back</a>
    <a class="cw-btn cw-btn-primary" href="app.php?r=profile.review">Continue &rarr;</a>
  </div>
</div>
