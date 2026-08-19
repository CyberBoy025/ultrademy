<?php
/** @var array $programmes @var array $centres @var int $openCount @var int|null $limit @var bool $entitled */
?>
<div class="topbar">
  <div>
    <h1>Apply for a Programme</h1>
    <p>Pick a programme, tell us why you're applying, then upload your documents on the next screen.</p>
  </div>
</div>

<?php if ($entitled): ?>
<div class="card" style="margin-bottom:20px">
  <p class="cap">
    You have <strong><?= $openCount ?></strong> open application<?= $openCount === 1 ? '' : 's' ?><?php
      if ($limit !== null): ?> of a maximum of <strong><?= $limit ?></strong> on your current package<?php
      else: ?> — your package allows <strong>unlimited</strong> open applications<?php endif; ?>.
  </p>
</div>
<?php else: ?>
<div class="card" style="margin-bottom:20px;border-color:var(--warning)">
  <p class="cap">
    Applying online isn't included in your current package. You can still enrol in person
    at one of our centres — or <a href="app.php?r=subscription" style="color:var(--brand-cyan-text);font-weight:600">view packages</a>.
  </p>
</div>
<?php endif; ?>

<div class="card">
  <form method="post" action="app.php?r=apply.store">
    <?= Csrf::field() ?>
    <div class="field">
      <label for="programme_id">Programme</label>
      <select name="programme_id" id="programme_id" required>
        <option value="">— Choose a programme —</option>
        <?php foreach ($programmes as $p): ?>
        <option value="<?= $p['id'] ?>"><?= View::e($p['title']) ?> (<?= View::e(ucfirst($p['delivery_mode'])) ?><?= $p['duration_weeks'] ? ', ' . (int) $p['duration_weeks'] . ' weeks' : '' ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="preferred_centre_id">Preferred centre</label>
      <select name="preferred_centre_id" id="preferred_centre_id">
        <option value="">Online / no preference</option>
        <?php foreach ($centres as $c): ?>
        <option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <span class="cap">Ignored for online-only programmes.</span>
    </div>
    <div class="field">
      <label for="motivation">Why are you applying?</label>
      <textarea name="motivation" id="motivation" rows="4" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"></textarea>
    </div>
    <button type="submit" class="btn primary">Submit Application</button>
  </form>
</div>
