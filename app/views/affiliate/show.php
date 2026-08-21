<?php /** @var array $affiliate @var array $stats @var array $referrals @var array $commissions
 *  @var array $payouts @var int $payable @var bool $canApprove @var bool $canPay @var int $minPayout */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=affiliate.admin" style="color:var(--text-3)">Affiliates</a> / <?= View::e($affiliate['name'] ?: $affiliate['email']) ?></span>
    <h1><?= View::e($affiliate['name'] ?: $affiliate['email']) ?></h1>
    <p>Code <strong><?= View::e($affiliate['code']) ?></strong> · <?= number_format(((int) $affiliate['commission_rate_bps']) / 100, 2) ?>% · <?= (int) $stats['qualified'] ?> of <?= (int) $stats['referrals'] ?> referrals qualified</p>
  </div>
  <span class="status-pill <?= $affiliate['status'] === 'approved' ? 'success' : (in_array($affiliate['status'], ['rejected','suspended'], true) ? 'error' : 'warning') ?>"><?= View::e(ucfirst(str_replace('_', ' ', $affiliate['status']))) ?></span>
</div>

<div class="row row-a" style="margin-bottom:16px">
  <div class="card">
    <div class="chead"><h3>Application</h3></div>
    <p class="cap" style="margin-bottom:4px">How they plan to refer</p>
    <p style="font-size:13px;margin-bottom:14px"><?= nl2br(View::e((string) ($affiliate['motivation'] ?: '—'))) ?></p>
    <p class="cap" style="margin-bottom:4px">Payout</p>
    <p style="font-size:13px"><?= View::e((string) ($affiliate['payout_method'] ?: '—')) ?><?= $affiliate['payout_details'] ? ' · ' . View::e((string) $affiliate['payout_details']) : '' ?></p>
  </div>

  <?php if ($canApprove): ?>
  <div class="card">
    <div class="chead"><h3>Decision</h3></div>
    <form method="post" action="app.php?r=affiliate.decide">
      <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $affiliate['id'] ?>">
      <div class="field">
        <label>Status</label>
        <select name="status">
          <?php foreach (['approved' => 'Approve', 'under_review' => 'Mark under review', 'rejected' => 'Reject', 'suspended' => 'Suspend'] as $k => $l): ?>
            <option value="<?= $k ?>" <?= $affiliate['status'] === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Commission rate (%) <span class="cap">— leave blank to keep the current rate</span></label>
        <input type="number" name="commission_rate" step="0.01" min="0" max="100" placeholder="<?= number_format(((int) $affiliate['commission_rate_bps']) / 100, 2) ?>">
      </div>
      <div class="field"><label>Note to the affiliate</label><input type="text" name="note" maxlength="255"></div>
      <button type="submit" class="btn primary">Save decision</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<div class="row row-b" style="margin-bottom:16px">
  <div class="card"><div class="chead"><h3>Pending</h3></div><span class="pct"><?= View::e(Money::formatShort($stats['pending'])) ?></span><span class="cap">awaiting approval</span></div>
  <div class="card">
    <div class="chead"><h3>Ready to pay</h3></div>
    <span class="pct"><?= View::e(Money::formatShort($payable)) ?></span>
    <span class="cap">approved, not yet paid</span>
    <?php if ($canPay && $payable > 0): ?>
      <form method="post" action="app.php?r=affiliate.payout" style="margin-top:12px">
        <?= Csrf::field() ?><input type="hidden" name="affiliate_id" value="<?= (int) $affiliate['id'] ?>">
        <input type="hidden" name="method" value="<?= View::e((string) $affiliate['payout_method']) ?>">
        <button type="submit" class="btn sm primary" <?= $payable < $minPayout ? 'disabled title="Below the payout minimum"' : '' ?>>Raise payout</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<h2 class="sec-title">Commissions</h2>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Date</th><th>Referred</th><th>Base</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($commissions as $c): ?>
        <tr>
          <td class="cap"><?= View::e(date('d M Y', strtotime((string) $c['created_at']))) ?></td>
          <td class="cap"><?= View::e($c['referred_name'] ?: '—') ?></td>
          <td class="cap"><?= View::e(Money::format((int) $c['base_amount'], $c['currency'])) ?></td>
          <td><span class="cell-main"><?= View::e(Money::format((int) $c['amount'], $c['currency'])) ?></span></td>
          <td><span class="status-pill <?= in_array($c['status'], ['paid','approved'], true) ? 'success' : ($c['status'] === 'void' ? 'neutral' : 'warning') ?>"><?= View::e(ucfirst($c['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$commissions): ?><tr><td colspan="5" class="cap" style="padding:16px;text-align:center">Nothing earned yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($payouts): ?>
<h2 class="sec-title">Payouts</h2>
<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Amount</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($payouts as $p): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($p['reference']) ?></span><span class="cap" style="display:block"><?= View::e(date('d M Y', strtotime((string) $p['requested_at']))) ?></span></td>
          <td><?= View::e(Money::format((int) $p['amount'], $p['currency'])) ?></td>
          <td><span class="status-pill <?= $p['status'] === 'paid' ? 'success' : 'warning' ?>"><?= View::e(ucfirst($p['status'])) ?></span></td>
          <td>
            <?php if ($canPay && $p['status'] !== 'paid'): ?>
            <form method="post" action="app.php?r=affiliate.payout.paid" style="display:flex;gap:6px">
              <?= Csrf::field() ?>
              <input type="hidden" name="payout_id" value="<?= (int) $p['id'] ?>">
              <input type="hidden" name="affiliate_id" value="<?= (int) $affiliate['id'] ?>">
              <input type="text" name="bank_reference" placeholder="Bank reference" style="width:160px">
              <button type="submit" class="btn sm primary">Mark paid</button>
            </form>
            <?php elseif ($p['bank_reference']): ?>
              <span class="cap"><?= View::e((string) $p['bank_reference']) ?></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
