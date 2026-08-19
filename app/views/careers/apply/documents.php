<?php
/** @var array<string,mixed> $app @var array<int,array<string,mixed>> $documents */
$stepActive = 'documents';
$hasCv = (bool) array_filter($documents, static fn ($d) => $d['type'] === 'cv');
?>
<div class="cw-wizard-wrap">
  <?php require __DIR__ . '/_steps.php'; ?>

  <div class="cw-form-card">
    <h2>Your Documents</h2>
    <p class="cw-form-hint">A CV/résumé is required. Everything else is optional unless the role specifically asks for it.</p>

    <?php if (!$documents): ?>
      <p class="cw-empty-hint">No documents uploaded yet.</p>
    <?php else: ?>
      <?php foreach ($documents as $d): ?>
        <div class="cw-doc-row">
          <span><?= View::e(JobApplicationDocument::TYPES[$d['type']] ?? $d['type']) ?> — <?= View::e($d['original_name']) ?> <span style="color:var(--cw-ink-faint)">(<?= Upload::humanSize((int) $d['size_bytes']) ?>)</span></span>
          <form method="post" action="app.php?r=apply.documents.delete" onsubmit="return confirm('Remove this document?')">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
            <button type="submit" class="cw-btn-danger-text">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="cw-form-card">
    <h2>Upload a Document</h2>
    <form method="post" action="app.php?r=apply.documents.store" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
      <div class="cw-form-grid">
        <label class="cw-field">
          <span>Document type</span>
          <select class="cw-select" name="type">
            <?php foreach (JobApplicationDocument::TYPES as $c => $l): ?><option value="<?= $c ?>"><?= $l ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="cw-field"><span>File (PDF, JPG or PNG, up to 5 MB)</span><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required></label>
      </div>
      <button type="submit" class="cw-btn cw-btn-outline">Upload</button>
    </form>
  </div>

  <div class="cw-wizard-actions">
    <a class="cw-btn cw-btn-outline" href="app.php?r=jobs.show&slug=<?= urlencode($app['job_slug']) ?>">&larr; Back to Job</a>
    <a class="cw-btn cw-btn-primary<?= $hasCv ? '' : ' is-disabled' ?>" href="app.php?r=apply.questions&id=<?= (int) $app['id'] ?>">Continue &rarr;</a>
  </div>
  <?php if (!$hasCv): ?><p style="font-size:0.8rem;color:var(--cw-warn);margin-top:10px">Upload your CV/résumé to continue.</p><?php endif; ?>
</div>
