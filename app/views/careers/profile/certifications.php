<?php
/** @var array<int,array<string,mixed>> $items @var array<string,mixed> $profile */
$stepActive = 'certifications';
?>
<div class="wizard">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1>Certifications</h1>
  <p class="muted">Optional — add any certifications relevant to the roles you're pursuing.</p>

  <div class="card card-body form-card">
    <?php if (!$items): ?>
      <p class="empty-hint">No certifications added yet.</p>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <div class="list-row">
          <div>
            <div class="title"><?= View::e($it['name']) ?></div>
            <div class="sub"><?= View::e($it['issuing_organisation'] ?? '') ?></div>
            <div class="meta">
              <?= $it['issued_on'] ? 'Issued ' . date('M Y', strtotime($it['issued_on'])) : '' ?>
              <?= $it['expires_on'] ? ' · Expires ' . date('M Y', strtotime($it['expires_on'])) : '' ?>
              <?= $it['stored_name'] ? ' · Document attached' : '' ?>
            </div>
          </div>
          <form method="post" action="app.php?r=profile.certifications.delete" onsubmit="return confirm('Remove this certification?')">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
            <button type="submit" class="btn-danger-text">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card card-body form-card">
    <h2>Add Certification</h2>
    <form method="post" action="app.php?r=profile.certifications.store" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <div class="form-grid">
        <label class="field"><span>Certification name</span><input type="text" name="name" required></label>
        <label class="field"><span>Issuing organisation</span><input type="text" name="issuing_organisation"></label>
        <label class="field"><span>Issued on</span><input type="date" name="issued_on"></label>
        <label class="field"><span>Expires on (optional)</span><input type="date" name="expires_on"></label>
        <label class="field field-full"><span>Supporting document (optional — PDF, JPG or PNG)</span><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png"></label>
      </div>
      <button type="submit" class="btn btn-secondary">Add Certification</button>
    </form>
  </div>

  <div class="wizard-actions">
    <a class="btn btn-secondary" href="app.php?r=profile.skills">&larr; Back</a>
    <a class="btn btn-primary" href="app.php?r=profile.resume">Continue &rarr;</a>
  </div>
</div>
