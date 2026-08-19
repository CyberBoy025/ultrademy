<?php
/** @var array<int,array<string,mixed>> $items @var array<string,mixed> $profile */
$stepActive = 'certifications';
?>
<div class="cw-wizard-wrap">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1 style="font-size:1.5rem;margin-bottom:6px">Certifications</h1>
  <p style="color:var(--cw-ink-soft);margin-bottom:22px">Optional — add any certifications relevant to the roles you're pursuing.</p>

  <div class="cw-form-card">
    <?php if (!$items): ?>
      <p class="cw-empty-hint">No certifications added yet.</p>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <div class="cw-list-item">
          <div>
            <div class="cw-list-item-title"><?= View::e($it['name']) ?></div>
            <div class="cw-list-item-sub"><?= View::e($it['issuing_organisation'] ?? '') ?></div>
            <div class="cw-list-item-meta">
              <?= $it['issued_on'] ? 'Issued ' . date('M Y', strtotime($it['issued_on'])) : '' ?>
              <?= $it['expires_on'] ? ' · Expires ' . date('M Y', strtotime($it['expires_on'])) : '' ?>
              <?= $it['stored_name'] ? ' · Document attached' : '' ?>
            </div>
          </div>
          <form method="post" action="app.php?r=profile.certifications.delete" onsubmit="return confirm('Remove this certification?')">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
            <button type="submit" class="cw-btn-danger-text">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="cw-form-card">
    <h2>Add Certification</h2>
    <form method="post" action="app.php?r=profile.certifications.store" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <div class="cw-form-grid">
        <label class="cw-field"><span>Certification name</span><input type="text" name="name" required></label>
        <label class="cw-field"><span>Issuing organisation</span><input type="text" name="issuing_organisation"></label>
        <label class="cw-field"><span>Issued on</span><input type="date" name="issued_on"></label>
        <label class="cw-field"><span>Expires on (optional)</span><input type="date" name="expires_on"></label>
        <label class="cw-field cw-field-full"><span>Supporting document (optional — PDF, JPG or PNG)</span><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png"></label>
      </div>
      <button type="submit" class="cw-btn cw-btn-outline">Add Certification</button>
    </form>
  </div>

  <div class="cw-wizard-actions">
    <a class="cw-btn cw-btn-outline" href="app.php?r=profile.skills">&larr; Back</a>
    <a class="cw-btn cw-btn-primary" href="app.php?r=profile.resume">Continue &rarr;</a>
  </div>
</div>
