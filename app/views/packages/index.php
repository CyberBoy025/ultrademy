<?php /** @var array $packages */
$statusColor = ['draft' => 'neutral', 'active' => 'success', 'retired' => 'neutral'];
?>
<div class="topbar">
  <div>
    <h1>Packages</h1>
    <p>What UltrAdemy sells. Which features each package grants is configured per package — never in code.</p>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>New Package</h3></div>
  <form method="post" action="app.php?r=packages.store">
    <?= Csrf::field() ?>
    <div class="field-row">
      <div class="field"><label>Name</label><input type="text" name="name" placeholder="e.g. Professional" required></div>
      <div class="field"><label>Code</label><input type="text" name="code" placeholder="e.g. professional" required></div>
    </div>
    <div class="field"><label>Description</label><input type="text" name="description"></div>
    <div class="field-row">
      <div class="field"><label>Price (₦)</label><input type="number" name="price_naira" min="0" step="500" value="0"></div>
      <div class="field"><label>Billing period</label>
        <select name="billing_period">
          <option value="monthly">Monthly</option><option value="quarterly">Quarterly</option>
          <option value="annual">Annual</option><option value="one_off">One-off</option>
        </select>
      </div>
    </div>
    <div class="field-row">
      <div class="field"><label>Duration (days)</label><input type="number" name="duration_days" min="1" value="30"></div>
      <div class="field"><label>Sort order</label><input type="number" name="sort_order" min="0" value="0"></div>
    </div>
    <button type="submit" class="btn primary">Create Draft</button>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Package</th><th>Price</th><th>Billing</th><th>Features</th><th>Active Subscribers</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($packages as $p): ?>
        <tr onclick="location='app.php?r=packages.show&id=<?= $p['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($p['name']) ?></span><span class="cell-sub"><?= View::e($p['code']) ?></span></td>
          <td><?= ((int) $p['price_amount']) === 0 ? 'Free' : '₦' . number_format(((int) $p['price_amount']) / 100) ?></td>
          <td><?= View::e(ucfirst(str_replace('_', '-', $p['billing_period']))) ?> · <?= (int) $p['duration_days'] ?>d</td>
          <td><?= (int) $p['feature_count'] ?></td>
          <td><?= (int) $p['subscribers'] ?></td>
          <td><span class="status-pill <?= $statusColor[$p['status']] ?>"><?= View::e(ucfirst($p['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$packages): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">No packages yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
