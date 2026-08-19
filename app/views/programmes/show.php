<?php
/** @var array $programme @var bool $canManage @var bool $canApprove @var bool $canPublish @var array $centres @var array $cohorts */
$statusColor = ['draft' => 'neutral', 'pending_approval' => 'warning', 'approved' => 'info', 'published' => 'success', 'archived' => 'neutral'];
$transitions = [
    'draft' => $canManage ? [['pending_approval', 'Submit for Approval']] : [],
    'pending_approval' => $canApprove ? [['approved', 'Approve'], ['draft', 'Send Back to Draft']] : [],
    'approved' => $canPublish ? [['published', 'Publish']] : [],
    'published' => $canPublish ? [['archived', 'Archive']] : [],
    'archived' => [],
];
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=programmes" style="color:var(--text-3)">Programmes</a> / <?= View::e($programme['title']) ?></span>
    <h1><?= View::e($programme['title']) ?> <span class="status-pill <?= $statusColor[$programme['status']] ?>" style="margin-left:8px;vertical-align:middle"><?= View::e(ucwords(str_replace('_', ' ', $programme['status']))) ?></span></h1>
    <p><?= View::e($programme['code']) ?> · <?= View::e(ucfirst($programme['delivery_mode'])) ?> · <?= $programme['duration_weeks'] ? $programme['duration_weeks'] . ' weeks' : 'Duration TBD' ?></p>
  </div>
  <div class="actions">
    <?php foreach ($transitions[$programme['status']] ?? [] as [$next, $label]): ?>
    <form method="post" action="app.php?r=programmes.status" style="display:inline">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" value="<?= $programme['id'] ?>">
      <input type="hidden" name="status" value="<?= $next ?>">
      <button type="submit" class="btn <?= $next === 'published' ? 'primary' : '' ?>"><?= View::e($label) ?></button>
    </form>
    <?php endforeach; ?>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <p class="cap" style="margin-bottom:6px">Description</p>
  <p><?= View::e($programme['description'] ?: 'No description yet.') ?></p>
</div>

<div class="row row-b">
  <div class="card">
    <div class="chead"><h3>Available At</h3></div>
    <?php if ($centres): ?>
      <div class="centre-facilities" style="display:flex;flex-wrap:wrap;gap:8px">
        <?php foreach ($centres as $c): ?><span class="pill" style="font-size:11.5px;padding:5px 11px;border-radius:999px;background:var(--surface-muted);border:1px solid var(--border)"><?= View::e($c['name']) ?></span><?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="cap">Online only, or not yet assigned to a centre.</p>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="chead"><h3>Fee</h3></div>
    <div style="display:flex;align-items:baseline;gap:6px"><span class="pct">₦<?= number_format(((int) $programme['fee_amount']) / 100) ?></span></div>
  </div>
</div>

<h2 class="sec-title">Cohorts</h2>
<?php if ($canManage): ?>
<div class="card" style="margin-bottom:16px">
  <form method="post" action="app.php?r=cohorts.store">
    <?= Csrf::field() ?>
    <input type="hidden" name="programme_id" value="<?= $programme['id'] ?>">
    <div class="field-row">
      <div class="field"><label>Cohort name</label><input type="text" name="name" placeholder="e.g. Cohort B" required></div>
      <div class="field"><label>Centre</label>
        <select name="centre_id">
          <option value="">Online</option>
          <?php foreach ($centres as $c): ?><option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field-row">
      <div class="field"><label>Starts</label><input type="date" name="starts_on"></div>
      <div class="field"><label>Ends</label><input type="date" name="ends_on"></div>
    </div>
    <button type="submit" class="btn primary">Add Cohort</button>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Cohort</th><th>Centre</th><th>Starts</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($cohorts as $co): ?>
        <tr onclick="location='app.php?r=cohorts.show&id=<?= $co['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($co['name']) ?></span></td>
          <td><?= View::e($co['centre_name'] ?? 'Online') ?></td>
          <td><?= $co['starts_on'] ? View::e(date('d M Y', strtotime($co['starts_on']))) : '—' ?></td>
          <td><span class="status-pill info"><?= View::e(ucfirst($co['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$cohorts): ?><tr><td colspan="4" class="cap" style="padding:16px;text-align:center">No cohorts yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
