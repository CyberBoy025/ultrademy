<?php /** @var array $affiliates @var string $status @var bool $enabled @var bool $canApprove */ ?>
<div class="topbar">
  <div><h1>Affiliates</h1><p><?= count($affiliates) ?> account(s)</p></div>
  <?php if (Auth::can('affiliate.commission.approve')): ?><a class="btn sm" href="app.php?r=affiliate.commissions">Commission approvals</a><?php endif; ?>
</div>

<?php if (!$enabled): ?>
<div class="card" style="margin-bottom:20px;border-left:3px solid var(--warning)">
  <p class="cap" style="margin:0">
    <strong>The affiliate programme is switched off.</strong> Referral links do not attribute,
    no commission is earned, and the public application form is closed. Turn on
    <code>affiliate_enabled</code> in <a href="app.php?r=settings" style="color:var(--brand-cyan-text)">Settings</a> to open it.
  </p>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:16px">
  <form method="get" action="app.php" style="display:flex;gap:10px;align-items:flex-end">
    <input type="hidden" name="r" value="affiliate.admin">
    <div class="field" style="margin:0;min-width:200px">
      <label>Status</label>
      <select name="status" onchange="this.form.submit()">
        <?php foreach (['' => 'All', 'applied' => 'Applied', 'under_review' => 'Under review', 'approved' => 'Approved', 'suspended' => 'Suspended', 'rejected' => 'Rejected'] as $k => $l): ?>
          <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Affiliate</th><th>Code</th><th>Rate</th><th>Referrals</th><th>Earned</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($affiliates as $a): ?>
        <tr>
          <td>
            <span class="cell-main"><?= View::e($a['name'] ?: '—') ?></span>
            <span class="cap" style="display:block"><?= View::e($a['email']) ?></span>
          </td>
          <td class="cap"><?= View::e($a['code']) ?></td>
          <td class="cap"><?= number_format(((int) $a['commission_rate_bps']) / 100, 2) ?>%</td>
          <td class="cap"><?= (int) $a['qualified_count'] ?> / <?= (int) $a['referral_count'] ?></td>
          <td><?= View::e(Money::formatShort((int) $a['earned'])) ?></td>
          <td><span class="status-pill <?= $a['status'] === 'approved' ? 'success' : (in_array($a['status'], ['rejected','suspended'], true) ? 'error' : 'warning') ?>"><?= View::e(ucfirst(str_replace('_', ' ', $a['status']))) ?></span></td>
          <td><a class="btn sm <?= in_array($a['status'], ['applied','under_review'], true) && $canApprove ? 'primary' : '' ?>" href="app.php?r=affiliate.show&id=<?= (int) $a['id'] ?>"><?= in_array($a['status'], ['applied','under_review'], true) && $canApprove ? 'Review' : 'View' ?></a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$affiliates): ?><tr><td colspan="7" class="cap" style="padding:16px;text-align:center">No affiliates yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
