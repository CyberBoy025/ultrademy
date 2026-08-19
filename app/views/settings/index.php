<?php /** @var array $settings */ ?>
<div class="topbar">
  <div><h1>Settings</h1><p>Platform-wide key/value configuration (README §22 of data-model.md).</p></div>
</div>

<div class="card">
  <?php foreach ($settings as $s): ?>
  <form method="post" action="app.php?r=settings.update" style="display:flex;gap:10px;align-items:flex-end;padding:12px 0;border-bottom:1px solid var(--border)">
    <?= Csrf::field() ?>
    <input type="hidden" name="key" value="<?= View::e($s['key']) ?>">
    <div class="field" style="flex:none;margin:0"><label><?= View::e($s['key']) ?> <span class="cap">(<?= View::e($s['group']) ?>)</span></label>
      <input type="text" name="value" value="<?= View::e(is_string($s['value']) ? $s['value'] : json_encode($s['value'])) ?>" style="width:320px">
    </div>
    <button type="submit" class="btn sm">Save</button>
  </form>
  <?php endforeach; ?>
  <?php if (!$settings): ?><p class="cap">No settings defined yet.</p><?php endif; ?>
</div>
