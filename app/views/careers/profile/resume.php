<?php /** @var array<string,mixed> $profile */
$stepActive = 'resume';
?>
<div class="cw-wizard-wrap">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1 style="font-size:1.5rem;margin-bottom:6px">Documents</h1>
  <p style="color:var(--cw-ink-soft);margin-bottom:22px">Keep a standing CV/résumé on your profile — you can still attach a role-specific one when you apply.</p>

  <div class="cw-form-card">
    <h2>CV / Résumé</h2>
    <?php if ($profile['resume_stored_name']): ?>
      <p style="font-size:0.9rem;margin-bottom:16px">Current file: <strong><?= View::e($profile['resume_original_name']) ?></strong></p>
    <?php else: ?>
      <p class="cw-empty-hint" style="margin-bottom:16px">No résumé uploaded yet.</p>
    <?php endif; ?>
    <form method="post" action="app.php?r=profile.resume.upload" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <label class="cw-field"><span>Upload résumé (PDF or Word, up to 5 MB)</span><input type="file" name="resume" accept=".pdf,.doc,.docx" required></label>
      <button type="submit" class="cw-btn cw-btn-outline"><?= $profile['resume_stored_name'] ? 'Replace Résumé' : 'Upload Résumé' ?></button>
    </form>
  </div>

  <div class="cw-wizard-actions">
    <a class="cw-btn cw-btn-outline" href="app.php?r=profile.certifications">&larr; Back</a>
    <a class="cw-btn cw-btn-primary" href="app.php?r=profile.references">Continue &rarr;</a>
  </div>
</div>
