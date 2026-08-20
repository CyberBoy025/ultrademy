<?php /** @var array<string,mixed> $profile */
$stepActive = 'resume';
?>
<div class="wizard">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1>Documents</h1>
  <p class="muted">Keep a standing CV/résumé on your profile — you can still attach a role-specific one when you apply.</p>

  <div class="card card-body form-card">
    <h2>CV / Résumé</h2>
    <?php if ($profile['resume_stored_name']): ?>
      <p style="font-size:14px;margin-bottom:16px">Current file: <strong><?= View::e($profile['resume_original_name']) ?></strong></p>
    <?php else: ?>
      <p class="empty-hint" style="margin-bottom:16px">No résumé uploaded yet.</p>
    <?php endif; ?>
    <form method="post" action="app.php?r=profile.resume.upload" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <label class="field"><span>Upload résumé (PDF or Word, up to 5 MB)</span><input type="file" name="resume" accept=".pdf,.doc,.docx" required></label>
      <button type="submit" class="btn btn-secondary"><?= $profile['resume_stored_name'] ? 'Replace Résumé' : 'Upload Résumé' ?></button>
    </form>
  </div>

  <div class="wizard-actions">
    <a class="btn btn-secondary" href="app.php?r=profile.certifications">&larr; Back</a>
    <a class="btn btn-primary" href="app.php?r=profile.references">Continue &rarr;</a>
  </div>
</div>
