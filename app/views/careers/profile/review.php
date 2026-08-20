<?php
/** @var array<string,mixed> $user @var array<string,mixed> $person @var array<string,mixed> $profile
 *  @var array<int,array<string,mixed>> $education @var array<int,array<string,mixed>> $experience
 *  @var array<int,array<string,mixed>> $skills @var array<int,array<string,mixed>> $certifications
 *  @var array<int,array<string,mixed>> $references */
$stepActive = 'review';
?>
<div class="wizard">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1>Review Your Profile</h1>
  <p class="muted">This is what recruiters will see when you apply.</p>

  <div class="card card-body form-card">
    <div class="review-section">
      <h2>Personal &amp; Professional</h2>
      <dl class="spec-grid">
        <div><dt>Name</dt><dd><?= View::e(trim(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''))) ?></dd></div>
        <div><dt>Email</dt><dd><?= View::e($user['email'] ?? '') ?></dd></div>
        <div><dt>Phone</dt><dd><?= View::e($user['phone'] ?? '—') ?></dd></div>
        <div><dt>Location</dt><dd><?= View::e(trim(($person['city'] ?? '') . ', ' . ($person['state'] ?? ''), ', ')) ?: '—' ?></dd></div>
        <div style="grid-column:1/-1"><dt>Summary</dt><dd><?= View::e($profile['professional_summary'] ?? '—') ?></dd></div>
      </dl>
    </div>

    <div class="review-section">
      <h2>Education (<?= count($education) ?>)</h2>
      <?php if (!$education): ?><p class="empty-hint">None added — <a href="app.php?r=profile.education">add now</a></p><?php endif; ?>
      <?php foreach ($education as $e): ?><p style="font-size:14px;margin:4px 0"><?= View::e($e['qualification']) ?> — <?= View::e($e['institution']) ?></p><?php endforeach; ?>
    </div>

    <div class="review-section">
      <h2>Experience (<?= count($experience) ?>)</h2>
      <?php if (!$experience): ?><p class="empty-hint">None added — <a href="app.php?r=profile.experience">add now</a></p><?php endif; ?>
      <?php foreach ($experience as $e): ?><p style="font-size:14px;margin:4px 0"><?= View::e($e['job_title']) ?> — <?= View::e($e['organisation']) ?></p><?php endforeach; ?>
    </div>

    <div class="review-section">
      <h2>Skills (<?= count($skills) ?>)</h2>
      <?php if (!$skills): ?><p class="empty-hint">None added — <a href="app.php?r=profile.skills">add now</a></p>
      <?php else: ?><div class="tag-row"><?php foreach ($skills as $s): ?><span class="pill"><?= View::e($s['skill_name']) ?></span><?php endforeach; ?></div><?php endif; ?>
    </div>

    <div class="review-section">
      <h2>Certifications (<?= count($certifications) ?>)</h2>
      <?php if (!$certifications): ?><p class="empty-hint">None added — optional</p>
      <?php else: ?><?php foreach ($certifications as $c): ?><p style="font-size:14px;margin:4px 0"><?= View::e($c['name']) ?></p><?php endforeach; ?><?php endif; ?>
    </div>

    <div class="review-section" style="margin-bottom:0">
      <h2>References (<?= count($references) ?>)</h2>
      <?php if (!$references): ?><p class="empty-hint">None added — <a href="app.php?r=profile.references">add now</a></p>
      <?php else: ?><?php foreach ($references as $r): ?><p style="font-size:14px;margin:4px 0"><?= View::e($r['name']) ?></p><?php endforeach; ?><?php endif; ?>
    </div>
  </div>

  <div class="wizard-actions">
    <a class="btn btn-secondary" href="app.php?r=profile.references">&larr; Back</a>
    <a class="btn btn-primary" href="app.php?r=jobs">Browse Jobs &rarr;</a>
  </div>
</div>
