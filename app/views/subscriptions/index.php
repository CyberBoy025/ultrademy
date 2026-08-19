<?php /** @var array $subscriptions @var string $status @var bool $canActivate @var array $counts */
$statusColor = ['pending' => 'warning', 'active' => 'success', 'expired' => 'neutral', 'cancelled' => 'neutral', 'void' => 'error'];
?>
<div class="topbar">
  <div>
    <h1>Subscriptions</h1>
    <p><?= (int) $counts['active'] ?> active · <?= (int) $counts['pending'] ?> awaiting activation</p>
  </div>
  <div class="actions">
    <?php if (Auth::can('subscriptions.override.grant')): ?>
      <a class="btn" href="app.php?r=overrides">Entitlement Overrides</a>
    <?php endif; ?>
  </div>
</div>

<div class="filters">
  <?php foreach (['' => 'All', 'pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired', 'cancelled' => 'Cancelled', 'void' => 'Void'] as $val => $label): ?>
    <a class="chip <?= $status === $val ? 'active' : '' ?>" href="app.php?r=subscriptions<?= $val ? '&status=' . $val : '' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if ($canActivate): ?>
<div class="card" style="margin-bottom:20px;border-color:var(--warning)">
  <p class="cap">
    <strong>Note:</strong> activation is manual in this phase. Once Phase 9 (Finance) lands, a
    subscription activates automatically when its invoice is paid — never on request.
  </p>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>User</th><th>Package</th><th>Status</th><th>Started</th><th>Ends</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($subscriptions as $s): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($s['user_name'] ?: '—') ?></span><span class="cell-sub"><?= View::e($s['email']) ?></span></td>
          <td><?= View::e($s['package_name']) ?></td>
          <td>
            <span class="status-pill <?= $statusColor[$s['status']] ?>"><?= View::e(ucfirst($s['status'])) ?></span>
            <?php if ($s['status'] === 'active' && $s['cancelled_at']): ?><span class="cap" style="display:block;margin-top:3px">cancelled — runs to end</span><?php endif; ?>
          </td>
          <td><?= $s['starts_at'] ? View::e(date('d M Y', strtotime($s['starts_at']))) : '—' ?></td>
          <td><?= $s['ends_at'] ? View::e(date('d M Y', strtotime($s['ends_at']))) : '—' ?></td>
          <td>
            <?php if ($canActivate && $s['status'] === 'pending'): ?>
            <div style="display:flex;gap:6px">
              <form method="post" action="app.php?r=subscriptions.void" style="display:inline">
                <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn sm">Void</button>
              </form>
              <form method="post" action="app.php?r=subscriptions.activate" style="display:inline">
                <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn sm primary">Activate</button>
              </form>
            </div>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$subscriptions): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">No subscriptions in this view.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
