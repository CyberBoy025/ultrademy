<?php
/** @var array<int,array<string,mixed>> $items @var array<string,mixed> $profile */
$stepActive = 'references';
?>
<div class="wizard">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1>References</h1>
  <p class="muted">People who can speak to your work, where a role requires them.</p>

  <div class="card card-body form-card">
    <?php if (!$items): ?>
      <p class="empty-hint">No references added yet.</p>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <div class="list-row">
          <div>
            <div class="title"><?= View::e($it['name']) ?></div>
            <div class="sub"><?= View::e(trim(($it['relationship'] ?? '') . ($it['organisation'] ? ' — ' . $it['organisation'] : ''))) ?></div>
            <div class="meta"><?= View::e($it['email'] ?? '') ?><?= $it['phone'] ? ' · ' . View::e($it['phone']) : '' ?></div>
          </div>
          <form method="post" action="app.php?r=profile.references.delete" onsubmit="return confirm('Remove this reference?')">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
            <button type="submit" class="btn-danger-text">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card card-body form-card">
    <h2>Add Reference</h2>
    <form method="post" action="app.php?r=profile.references.store">
      <?= Csrf::field() ?>
      <div class="form-grid">
        <label class="field"><span>Full name</span><input type="text" name="name" required></label>
        <label class="field"><span>Relationship</span><input type="text" name="relationship" placeholder="e.g. Former Manager"></label>
        <label class="field"><span>Organisation</span><input type="text" name="organisation"></label>
        <label class="field"><span>Email</span><input type="email" name="email"></label>
        <label class="field"><span>Phone</span><input type="tel" name="phone"></label>
      </div>
      <button type="submit" class="btn btn-secondary">Add Reference</button>
    </form>
  </div>

  <div class="wizard-actions">
    <a class="btn btn-secondary" href="app.php?r=profile.resume">&larr; Back</a>
    <a class="btn btn-primary" href="app.php?r=profile.review">Continue &rarr;</a>
  </div>
</div>
