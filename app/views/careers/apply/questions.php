<?php
/** @var array<string,mixed> $app @var array<int,array<string,mixed>> $questions @var array<int,string> $answers */
$stepActive = 'questions';
?>
<div class="cw-wizard-wrap">
  <?php require __DIR__ . '/_steps.php'; ?>

  <form method="post" action="app.php?r=apply.questions.save">
    <?= Csrf::field() ?>
    <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
    <div class="cw-form-card">
      <h2>Application Questions</h2>
      <p class="cw-form-hint">Specific to this role.</p>
      <?php foreach ($questions as $q): ?>
        <label class="cw-field cw-field-full">
          <span><?= View::e($q['label']) ?><?= $q['is_required'] ? ' *' : ' (optional)' ?></span>
          <?php $val = View::e($answers[$q['id']] ?? ''); ?>
          <?php if ($q['type'] === 'long_text'): ?>
            <textarea class="cw-textarea" name="q<?= (int) $q['id'] ?>" <?= $q['is_required'] ? 'required' : '' ?>><?= $val ?></textarea>
          <?php elseif ($q['type'] === 'yes_no'): ?>
            <select class="cw-select" name="q<?= (int) $q['id'] ?>" <?= $q['is_required'] ? 'required' : '' ?>>
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

    <div class="cw-wizard-actions">
      <a class="cw-btn cw-btn-outline" href="app.php?r=apply.documents&id=<?= (int) $app['id'] ?>">&larr; Back</a>
      <button type="submit" class="cw-btn cw-btn-primary">Continue &rarr;</button>
    </div>
  </form>
</div>
