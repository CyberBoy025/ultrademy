<?php
/** @var array $package @var array $grouped @var array $featureMap @var int $subscribers */
$statusColor = ['draft' => 'neutral', 'active' => 'success', 'retired' => 'neutral'];
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=packages" style="color:var(--text-3)">Packages</a> / <?= View::e($package['name']) ?></span>
    <h1><?= View::e($package['name']) ?> <span class="status-pill <?= $statusColor[$package['status']] ?>" style="margin-left:8px"><?= View::e(ucfirst($package['status'])) ?></span></h1>
    <p><?= ((int) $package['price_amount']) === 0 ? 'Free' : '₦' . number_format(((int) $package['price_amount']) / 100) ?>
       · <?= View::e($package['billing_period']) ?> · <?= $subscribers ?> active subscriber(s)</p>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>Details</h3></div>
  <form method="post" action="app.php?r=packages.update">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $package['id'] ?>">
    <div class="field-row">
      <div class="field"><label>Name</label><input type="text" name="name" value="<?= View::e($package['name']) ?>" required></div>
      <div class="field"><label>Status</label>
        <select name="status">
          <?php foreach (['draft', 'active', 'retired'] as $s): ?>
          <option value="<?= $s ?>" <?= $package['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field"><label>Description</label><input type="text" name="description" value="<?= View::e($package['description']) ?>"></div>
    <div class="field-row">
      <div class="field"><label>Price (₦)</label><input type="number" name="price_naira" min="0" step="500" value="<?= (int) (((int) $package['price_amount']) / 100) ?>"></div>
      <div class="field"><label>Billing period</label>
        <select name="billing_period">
          <?php foreach (['monthly', 'quarterly', 'annual', 'one_off'] as $b): ?>
          <option value="<?= $b ?>" <?= $package['billing_period'] === $b ? 'selected' : '' ?>><?= ucfirst(str_replace('_', '-', $b)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field-row">
      <div class="field"><label>Duration (days)</label><input type="number" name="duration_days" min="1" value="<?= (int) $package['duration_days'] ?>"></div>
      <div class="field"><label>Sort order</label><input type="number" name="sort_order" min="0" value="<?= (int) $package['sort_order'] ?>"></div>
    </div>
    <button type="submit" class="btn primary">Save Details</button>
  </form>
</div>

<h2 class="sec-title">Feature Matrix</h2>
<div class="card">
  <p class="cap" style="margin-bottom:16px">
    Tick a feature to include it. Leave the limit blank for <strong>unlimited</strong>; a number caps
    metered features. Unticked means the feature is off for this package entirely.
  </p>
  <form method="post" action="app.php?r=packages.features">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $package['id'] ?>">

    <?php foreach ($grouped as $module => $features): ?>
      <div class="nav-label" style="padding:14px 0 6px"><?= View::e(strtoupper($module)) ?></div>
      <div class="table-wrap">
        <table class="dt">
          <thead><tr><th style="width:70px">Include</th><th>Feature</th><th style="width:110px">Type</th><th style="width:190px">Limit</th></tr></thead>
          <tbody>
            <?php foreach ($features as $f):
              $fid = (int) $f['id'];
              $on = array_key_exists($fid, $featureMap);
              $limit = $featureMap[$fid] ?? null;
              $metered = $f['limit_type'] !== 'none';
            ?>
            <tr>
              <td><input type="checkbox" name="enabled[<?= $fid ?>]" value="1" <?= $on ? 'checked' : '' ?>></td>
              <td><span class="cell-main"><?= View::e($f['name']) ?></span><span class="cell-sub"><?= View::e($f['code']) ?></span></td>
              <td><span class="status-pill neutral"><?= View::e($f['limit_type']) ?></span></td>
              <td>
                <?php if ($metered): ?>
                  <input type="number" min="0" name="limit[<?= $fid ?>]" value="<?= $limit === null ? '' : (int) $limit ?>"
                         placeholder="unlimited" style="width:100%">
                <?php else: ?>
                  <span class="cap">on / off</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>

    <button type="submit" class="btn primary" style="margin-top:18px">Save Feature Matrix</button>
  </form>
</div>
