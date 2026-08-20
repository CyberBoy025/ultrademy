<?php
/** @var array<string,mixed> $app @var array<int,array<string,mixed>> $questions @var array<int,string> $answers */
$stepActive = 'questions';
?>
<div class="wizard">
  <?php require __DIR__ . '/_steps.php'; ?>

  <form method="post" action="app.php?r=apply.questions.save">
    <?= Csrf::field() ?>
    <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
    <div class="card card-body form-card">
      <h2>Application Questions</h2>
      <p class="form-hint">Specific to this role.</p>
      <?php foreach ($questions as $q): ?>
        <label class="field field-full">
          <span><?= View::e($q['label']) ?><?= $q['is_required'] ? ' *' : ' (optional)' ?></span>
          <?php $val = View::e($answers[$q['id']] ?? ''); ?>
          <?php if ($q['type'] === 'long_text'): ?>
            <textarea name="q<?= (int) $q['id'] ?>" <?= $q['is_required'] ? 'required' : '' ?>><?= $val ?></textarea>
          <?php elseif ($q['type'] === 'yes_no'): ?>
            <select name="q<?= (int) $q['id'] ?>" <?= $q['is_required'] ? 'required' : '' ?>>
              <option value="">Select…</option>
              <option value="Yes" <?= $val === 'Yes' ? 'selected' : '' ?>>Yes</option>
              <option value="No" <?= $val === 'No' ? 'selected' : '' ?>>No</option>
            </select>
          <?php elseif ($q['type'] === 'date'): ?>
            <input type="date" name="q<?= (int) $q['id'] ?>" value="<?= $val ?>" <?= $q['is_required'] ? 'required' : '' ?>>
          <?php elseif ($q['type'] === 'number'): ?>
            <input type="number" name="q<?= (int) $q['id'] ?>" value="<?= $val ?>" <?= $q['is_required'] ? 'required' : '' ?>>
          <?php else: ?>
            <input type="text" name="q<?= (int) $q['id'] ?>" value="<?= $val ?>" <?= $q['is_required'] ? 'required' : '' ?>>
          <?php endif; ?>
        </label>
      <?php endforeach; ?>
    </div>

    <div class="wizard-actions">
      <a class="btn btn-secondary" href="app.php?r=apply.documents&id=<?= (int) $app['id'] ?>">&larr; Back</a>
      <button type="submit" class="btn btn-primary">Continue &rarr;</button>
    </div>
  </form>
</div>
