<?php /** @var array $overrides @var array $features */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=subscriptions" style="color:var(--text-3)">Subscriptions</a> / Overrides</span>
    <h1>Entitlement Overrides</h1>
    <p>Per-user grants and revocations that sit on top of the package — comps, promotional access, corporate deals, or a sanction.</p>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>New Override</h3></div>
  <form method="post" action="app.php?r=overrides.store">
    <?= Csrf::field() ?>
    <div class="field-row">
      <div class="field"><label>User email</label><input type="email" name="email" placeholder="existing account" required></div>
      <div class="field"><label>Feature</label>
        <select name="feature_id" required>
          <?php foreach ($features as $f): ?>
          <option value="<?= $f['id'] ?>"><?= View::e($f['name']) ?> (<?= View::e($f['module']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field-row">
      <div class="field"><label>Effect</label>
        <select name="granted">
          <option value="1">Grant — add this feature</option>
          <option value="0">Revoke — remove it even if the package includes it</option>
        </select>
      </div>
      <div class="field"><label>Limit (blank = unlimited)</label><input type="number" name="limit_value" min="0" placeholder="unlimited"></div>
    </div>
    <div class="field-row">
      <div class="field"><label>Expires (blank = never)</label><input type="datetime-local" name="expires_at"></div>
      <div class="field"><label>Reason</label><input type="text" name="reason" placeholder="e.g. corporate agreement, staff comp"></div>
    </div>
    <button type="submit" class="btn primary">Save Override</button>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>User</th><th>Feature</th><th>Effect</th><th>Expires</th><th>Reason</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($overrides as $o): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($o['user_name'] ?: '—') ?></span><span class="cell-sub"><?= View::e($o['email']) ?></span></td>
          <td><?= View::e($o['feature_name']) ?></td>
          <td><span class="status-pill <?= (int) $o['granted'] === 1 ? 'success' : 'error' ?>"><?= (int) $o['granted'] === 1 ? 'Grant' : 'Revoke' ?></span></td>
          <td><?= $o['expires_at'] ? View::e(date('d M Y', strtotime($o['expires_at']))) : 'Never' ?></td>
          <td class="cap"><?= View::e($o['reason'] ?: '—') ?></td>
          <td>
            <form method="post" action="app.php?r=overrides.remove" style="display:inline">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>">
              <button type="submit" class="btn sm">Remove</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$overrides): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">No overrides in place.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
