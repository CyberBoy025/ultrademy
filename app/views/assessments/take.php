<?php /** @var array $assessment @var array $attempt @var array $questions @var ?int $remaining */ ?>
<div class="topbar">
  <div>
    <h1><?= View::e($assessment['title']) ?></h1>
    <p><?= View::e($assessment['course_title']) ?> · attempt <?= (int) $attempt['attempt_no'] ?> · <?= count($questions) ?> question(s) · <?= (int) $assessment['max_points'] ?> mark(s)</p>
  </div>
  <?php if ($remaining !== null): ?>
    <div class="card" style="padding:10px 16px;text-align:center;margin:0">
      <span class="cap" style="display:block">Time remaining</span>
      <strong id="clock" style="font-family:var(--font-1);font-size:20px" data-left="<?= (int) $remaining ?>">--:--</strong>
    </div>
  <?php endif; ?>
</div>

<?php if ($assessment['instructions']): ?>
<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>Instructions</h3></div>
  <p style="font-size:13px;color:var(--text-2)"><?= nl2br(View::e($assessment['instructions'])) ?></p>
</div>
<?php endif; ?>

<form method="post" action="app.php?r=assessments.submit" id="paper">
  <?= Csrf::field() ?><input type="hidden" name="attempt_id" value="<?= (int) $attempt['id'] ?>">

  <?php foreach ($questions as $i => $q): ?>
  <div class="card" style="margin-bottom:12px">
    <div class="chead">
      <h3>Question <?= $i + 1 ?></h3>
      <span class="cap"><?= (int) $q['points'] ?> mark<?= (int) $q['points'] === 1 ? '' : 's' ?></span>
    </div>
    <p style="font-size:14px;color:var(--text);margin-bottom:14px"><?= nl2br(View::e($q['prompt'])) ?></p>

    <?php if ($q['type'] === 'multi_choice'): ?>
      <p class="cap" style="margin-bottom:8px">Select all that apply.</p>
      <?php foreach ($q['options'] as $o): ?>
        <label style="display:flex;align-items:flex-start;gap:10px;padding:9px 0;font-size:13px;cursor:pointer">
          <input type="checkbox" name="q[<?= (int) $q['id'] ?>][]" value="<?= (int) $o['id'] ?>" style="width:auto;flex:none;margin-top:3px">
          <span><?= View::e($o['label']) ?></span>
        </label>
      <?php endforeach; ?>

    <?php elseif ($q['type'] === 'single_choice' || $q['type'] === 'true_false'): ?>
      <?php foreach ($q['options'] as $o): ?>
        <label style="display:flex;align-items:flex-start;gap:10px;padding:9px 0;font-size:13px;cursor:pointer">
          <input type="radio" name="q[<?= (int) $q['id'] ?>][]" value="<?= (int) $o['id'] ?>" style="width:auto;flex:none;margin-top:3px">
          <span><?= View::e($o['label']) ?></span>
        </label>
      <?php endforeach; ?>

    <?php elseif ($q['type'] === 'short_text'): ?>
      <div class="field"><input type="text" name="q[<?= (int) $q['id'] ?>]" maxlength="500" autocomplete="off"></div>

    <?php else: ?>
      <div class="field">
        <textarea name="q[<?= (int) $q['id'] ?>]" rows="8" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"></textarea>
        <span class="cap">Marked by your instructor — your result will follow.</span>
      </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

  <div class="card">
    <p class="cap" style="margin-bottom:12px">
      Once submitted you cannot change your answers<?= (int) $assessment['max_attempts'] === 1 ? ', and this is your only attempt' : '' ?>.
    </p>
    <button type="submit" class="btn primary" id="submitbtn">Submit answers</button>
  </div>
</form>

<?php if ($remaining !== null): ?>
<script>
(function () {
  // Display only. The server decides whether a submission was in time — see
  // Assessment::hasExpired(). Putting the clock here is a courtesy, not a control.
  var el = document.getElementById('clock'), left = parseInt(el.dataset.left, 10);
  function paint() {
    var m = Math.floor(left / 60), s = left % 60;
    el.textContent = m + ':' + (s < 10 ? '0' : '') + s;
    if (left <= 60) { el.style.color = 'var(--error)'; }
    if (left <= 0) { document.getElementById('paper').submit(); return; }
    left--; setTimeout(paint, 1000);
  }
  paint();
})();
</script>
<?php endif; ?>
