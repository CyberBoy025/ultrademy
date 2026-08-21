<?php /** @var array $commissions */ ?>
<div class="topbar">
  <div>
    <h1>Commission Approvals</h1>
    <p><?= count($commissions) ?> commission(s) awaiting a decision.</p>
  </div>
  <a class="btn sm" href="app.php?r=affiliate.admin">All affiliates</a>
</div>

<div class="card" style="margin-bottom:16px;border-left:3px solid var(--brand-cyan-text)">
  <p class="cap" style="margin:0">
    A commission is earned automatically when a referred person makes their first
    qualifying payment. Approving it makes it payable; voiding it does not reverse the
    underlying payment, it only declines the commission. Both are audited.
  </p>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Affiliate</th><th>Earned</th><th>Base</th><th>Rate</th><th>Amount</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($commissions as $c): ?>
        <tr>
          <td>
            <span class="cell-main"><?= View::e($c['affiliate_name'] ?: '—') ?></span>
            <span class="cap" style="display:block"><?= View::e($c['code']) ?></span>
          </td>
          <td class="cap"><?= View::e(date('d M Y', strtotime((string) $c['created_at']))) ?></td>
          <td class="cap"><?= View::e(Money::format((int) $c['base_amount'], $c['currency'])) ?></td>
          <td class="cap"><?= number_format(((int) $c['rate_bps']) / 100, 2) ?>%</td>
          <td><span class="cell-main"><?= View::e(Money::format((int) $c['amount'], $c['currency'])) ?></span></td>
          <td style="white-space:nowrap">
            <form method="post" action="app.php?r=affiliate.commissions.decide" style="display:inline">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="decision" value="approve">
              <button type="submit" class="btn sm primary">Approve</button>
            </form>
            <form method="post" action="app.php?r=affiliate.commissions.decide" style="display:inline"
                  onsubmit="return confirm('Void this commission? The affiliate will not be paid for it.')">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="decision" value="void">
              <button type="submit" class="btn sm">Void</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$commissions): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">Nothing awaiting approval.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
