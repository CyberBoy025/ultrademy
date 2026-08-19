<?php
/** @var array|null $active @var array|null $pending @var array $history @var array $packages
 *  @var array $resolved @var array $allFeatures @var array $overrides */
?>
<div class="topbar">
  <div>
    <h1>My Subscription</h1>
    <p>What your account can do right now, and what each package would add.</p>
  </div>
</div>

<?php if ($active): ?>
<div class="card" style="margin-bottom:20px">
  <div class="chead">
    <h3><?= View::e($active['package_name']) ?></h3>
    <span class="status-pill success">Active</span>
  </div>
  <p class="cap">
    <?= ((int) $active['price_amount']) === 0 ? 'Free' : '₦' . number_format(((int) $active['price_amount']) / 100) ?>
    · <?= View::e($active['billing_period']) ?>
    <?php if ($active['ends_at']): ?> · Renews <?= View::e(date('d M Y', strtotime($active['ends_at']))) ?><?php endif; ?>
  </p>
  <?php if ($active['cancelled_at']): ?>
    <p class="cap" style="margin-top:10px;color:var(--warning)">
      Cancelled on <?= View::e(date('d M Y', strtotime($active['cancelled_at']))) ?> — you keep full access until
      <?= View::e(date('d M Y', strtotime($active['ends_at']))) ?>, then it will not renew.
    </p>
  <?php else: ?>
    <form method="post" action="app.php?r=subscription.cancel" style="margin-top:14px">
      <?= Csrf::field() ?>
      <button type="submit" class="btn sm">Cancel Subscription</button>
    </form>
  <?php endif; ?>
</div>
<?php elseif ($pending): ?>
<div class="card" style="margin-bottom:20px;border-color:var(--warning)">
  <div class="chead"><h3><?= View::e($pending['package_name']) ?></h3><span class="status-pill warning">Awaiting activation</span></div>
  <p class="cap">Requested <?= View::e(date('d M Y', strtotime($pending['created_at']))) ?>. It becomes active once payment is confirmed — nothing is granted until then.</p>
</div>
<?php else: ?>
<div class="empty-card" style="margin-bottom:20px">
  <b>No active subscription</b>
  <p>Staff accounts still get the operational features they need for their job. Everything else comes from a package below.</p>
</div>
<?php endif; ?>

<h2 class="sec-title">What I Can Use</h2>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Feature</th><th>Module</th><th style="width:140px">Access</th></tr></thead>
      <tbody>
        <?php foreach ($allFeatures as $f):
          $granted = array_key_exists($f['code'], $resolved);
          $limit = $resolved[$f['code']] ?? null;
        ?>
        <tr>
          <td><span class="cell-main"><?= View::e($f['name']) ?></span><span class="cell-sub"><?= View::e($f['code']) ?></span></td>
          <td class="cap"><?= View::e($f['module']) ?></td>
          <td>
            <?php if ($granted): ?>
              <span class="status-pill success"><?= View::e(Feature::formatLimit($f['limit_type'], $limit)) ?></span>
            <?php else: ?>
              <span class="status-pill neutral">Not included</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($overrides): ?>
<h2 class="sec-title">Adjustments On Your Account</h2>
<div class="card" style="margin-bottom:20px">
  <div class="queue">
    <?php foreach ($overrides as $o): ?>
    <div class="queue-item">
      <div class="queue-t">
        <h4><?= View::e($o['feature_name']) ?></h4>
        <p><?= $o['reason'] ? View::e($o['reason']) : 'No reason recorded' ?><?= $o['expires_at'] ? ' · until ' . View::e(date('d M Y', strtotime($o['expires_at']))) : '' ?></p>
      </div>
      <span class="status-pill <?= (int) $o['granted'] === 1 ? 'success' : 'error' ?>"><?= (int) $o['granted'] === 1 ? 'Granted' : 'Revoked' ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<h2 class="sec-title">Packages</h2>
<div class="kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
  <?php foreach ($packages as $p):
    $isCurrent = $active && (int) $active['package_id'] === (int) $p['id'];
    $features = Package::featuresFor((int) $p['id']);
  ?>
  <div class="card" style="display:flex;flex-direction:column;<?= $isCurrent ? 'border-color:var(--cyan-500)' : '' ?>">
    <div class="chead">
      <h3><?= View::e($p['name']) ?></h3>
      <?php if ($isCurrent): ?><span class="status-pill info">Current</span><?php endif; ?>
    </div>
    <div style="display:flex;align-items:baseline;gap:4px;margin-bottom:8px">
      <span class="pct" style="font-size:24px"><?= ((int) $p['price_amount']) === 0 ? 'Free' : '₦' . number_format(((int) $p['price_amount']) / 100) ?></span>
      <?php if (((int) $p['price_amount']) > 0): ?><span class="cap">/ <?= View::e($p['billing_period']) ?></span><?php endif; ?>
    </div>
    <p class="cap" style="margin-bottom:12px;flex:1"><?= View::e($p['description']) ?></p>
    <ul style="list-style:none;padding:0;margin:0 0 14px;display:flex;flex-direction:column;gap:6px">
      <?php foreach ($features as $f): ?>
      <li style="display:flex;gap:8px;align-items:flex-start;font-size:12px;color:var(--text-2)">
        <svg style="width:13px;height:13px;flex:none;margin-top:3px;color:var(--brand-cyan-text)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
        <?= View::e($f['name']) ?><?= $f['limit_type'] !== 'none' ? ' — ' . View::e(Feature::formatLimit($f['limit_type'], $f['limit_value'] === null ? null : (int) $f['limit_value'])) : '' ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php if (!$isCurrent && !$pending): ?>
    <form method="post" action="app.php?r=subscription.request">
      <?= Csrf::field() ?>
      <input type="hidden" name="package_id" value="<?= $p['id'] ?>">
      <button type="submit" class="btn primary" style="width:100%;justify-content:center">Choose <?= View::e($p['name']) ?></button>
    </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<?php if (count($history) > ($active || $pending ? 1 : 0)): ?>
<h2 class="sec-title">History</h2>
<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Package</th><th>Status</th><th>Started</th><th>Ended</th></tr></thead>
      <tbody>
        <?php foreach ($history as $h): ?>
        <tr>
          <td><?= View::e($h['package_name']) ?></td>
          <td><span class="status-pill <?= $h['status'] === 'active' ? 'success' : 'neutral' ?>"><?= View::e(ucfirst($h['status'])) ?></span></td>
          <td><?= $h['starts_at'] ? View::e(date('d M Y', strtotime($h['starts_at']))) : '—' ?></td>
          <td><?= $h['ends_at'] ? View::e(date('d M Y', strtotime($h['ends_at']))) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
