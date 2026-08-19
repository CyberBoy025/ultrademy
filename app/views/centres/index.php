<?php
/** @var array $centres @var bool $canCreate */
$statusColor = ['active' => 'success', 'inactive' => 'neutral', 'planned' => 'warning'];
?>
<div class="topbar">
  <div><h1>Centres</h1><p>Physical training hubs (README §12, §45 — new centres can be added here without a code change).</p></div>
</div>

<?php if ($canCreate): ?>
<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>New Centre</h3></div>
  <form method="post" action="app.php?r=centres.store">
    <?= Csrf::field() ?>
    <div class="field-row">
      <div class="field"><label>Name</label><input type="text" name="name" placeholder="e.g. Lagos Hub" required></div>
      <div class="field"><label>Code</label><input type="text" name="code" placeholder="e.g. LAG" maxlength="20" required></div>
    </div>
    <div class="field-row">
      <div class="field"><label>City</label><input type="text" name="city"></div>
      <div class="field"><label>State</label><input type="text" name="state" value="FCT"></div>
    </div>
    <button type="submit" class="btn primary">Create Centre</button>
  </form>
</div>
<?php endif; ?>

<div class="grid grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
  <?php foreach ($centres as $c): ?>
  <div class="card" style="cursor:pointer" onclick="location='app.php?r=centres.show&id=<?= $c['id'] ?>'">
    <div class="chead">
      <h3><?= View::e($c['name']) ?></h3>
      <span class="status-pill <?= $statusColor[$c['status']] ?>"><?= View::e(ucfirst($c['status'])) ?></span>
    </div>
    <p class="cap" style="margin-bottom:14px"><?= View::e($c['code']) ?> · <?= View::e($c['city'] ?: 'City TBD') ?></p>
    <div class="prog-meta" style="display:flex;gap:14px;font-size:12px;color:var(--text-3)">
      <span><?= (int) $c['counts']['rooms'] ?> rooms</span>
      <span><?= (int) $c['counts']['staff'] ?> staff</span>
      <span><?= (int) $c['counts']['students'] ?> students</span>
      <span><?= (int) $c['counts']['cohorts'] ?> active cohorts</span>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (!$centres): ?><div class="empty-card"><b>No centres visible</b><p>Either none exist yet, or your role isn't scoped to any.</p></div><?php endif; ?>
</div>
