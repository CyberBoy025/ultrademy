<?php
/** @var array<string,mixed> $app @var array<int,array<string,mixed>> $documents
 *  @var array<int,array<string,mixed>> $answers @var array<string,mixed> $profile */
$stepActive = 'review';
?>
<div class="cw-wizard-wrap">
  <?php require __DIR__ . '/_steps.php'; ?>

  <div class="cw-form-card">
    <div class="cw-review-section">
      <h2>Your Profile</h2>
      <p style="font-size:0.9rem">Profile completion: <strong><?= (int) $profile['completion_pct'] ?>%</strong> — <a href="app.php?r=profile.review">review your profile</a></p>
    </div>

    <div class="cw-review-section">
      <h2>Documents</h2>
      <?php if (!$documents): ?><p class="cw-empty-hint">None uploaded — <a href="app.php?r=apply.documents&id=<?= (int) $app['id'] ?>">add now</a></p>
      <?php else: ?>
        <?php foreach ($documents as $d): ?><p style="font-size:0.9rem;margin:4px 0"><?= View::e(JobApplicationDocument::TYPES[$d['type']] ?? $d['type']) ?> — <?= View::e($d['original_name']) ?></p><?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($answers): ?>
    <div class="cw-review-section">
      <h2>Your Answers</h2>
      <?php foreach ($answers as $a): ?>
        <p style="font-size:0.9rem;margin:0 0 10px"><strong><?= View::e($a['label']) ?></strong><br><?= View::e($a['answer_text'] ?: '—') ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <form method="post" action="app.php?r=apply.submit">
    <?= Csrf::field() ?>
    <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
    <div class="cw-form-card">
      <h2>Cover Letter (optional)</h2>
      <label class="cw-field cw-field-full">
        <span>Tell us why you're a good fit</span>
        <textarea class="cw-textarea" name="cover_letter"><?= View::e($app['cover_letter'] ?? '') ?></textarea>
      </label>
    </div>

    <div class="cw-form-card">
      <h2>Declaration</h2>
      <label class="cw-check">
        <input type="checkbox" name="declaration" required>
        <span>I confirm that the information provided in this application is accurate and complete.</span>
      </label>
      <?= Captcha::widget() ?>
    </div>

    <div class="cw-wizard-actions">
      <a class="cw-btn cw-btn-outline" href="app.php?r=apply.questions&id=<?= (int) $app['id'] ?>">&larr; Back</a>
      <button type="submit" class="cw-btn cw-btn-primary">Submit Application</button>
    </div>
  </form>
</div>
