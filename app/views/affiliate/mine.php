<?php /** @var array $affiliate @var array $stats @var array $referrals @var array $commissions
 *  @var array $payouts @var string $link @var int $minPayout @var int $payable */ ?>
<div class="topbar">
  <div>
    <h1>Affiliate Programme</h1>
    <p>Code <strong><?= View::e($affiliate['code']) ?></strong> · <?= number_format(((int) $affiliate['commission_rate_bps']) / 100, 2) ?>% of first qualifying payment</p>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>Your referral link</h3></div>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input type="text" id="reflink" value="<?= View::e($link) ?>" readonly style="flex:1;min-width:0;width:100%">
    <button type="button" class="btn sm primary" id="copybtn">Copy</button>
  </div>
  <p class="cap" style="margin-top:10px">
    Anyone who follows this link and registers within <?= (int) Affiliate::cookieDays() ?> days is attributed to you.
  </p>
</div>

<div class="row row-b" style="margin-bottom:16px">
  <div class="card"><div class="chead"><h3>Referrals</h3></div><span class="pct"><?= (int) $stats['referrals'] ?></span><span class="cap"><?= (int) $stats['qualified'] ?> qualified</span></div>
  <div class="card"><div class="chead"><h3>Earned</h3></div><span class="pct"><?= View::e(Money::formatShort($stats['pending'] + $stats['approved'] + $stats['paid'])) ?></span><span class="cap"><?= View::e(Money::format($stats['paid'])) ?> paid out</span></div>
</div>

<div class="row row-b" style="margin-bottom:16px">
  <div class="card"><div class="chead"><h3>Awaiting approval</h3></div><span class="pct"><?= View::e(Money::formatShort($stats['pending'])) ?></span><span class="cap">not yet payable</span></div>
  <div class="card">
    <div class="chead"><h3>Ready to pay</h3></div>
    <span class="pct"><?= View::e(Money::formatShort($payable)) ?></span>
    <span class="cap"><?= $payable >= $minPayout ? 'above the ' . View::e(Money::format($minPayout)) . ' minimum' : 'minimum payout is ' . View::e(Money::format($minPayout)) ?></span>
  </div>
</div>

<h2 class="sec-title">Your referrals</h2>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Registered</th><th>Status</th><th>Earned</th></tr></thead>
      <tbody>
        <?php foreach ($referrals as $r): ?>
        <tr>
          <td class="cap"><?= View::e(date('d M Y', strtotime((string) $r['registered_at']))) ?></td>
          <td><span class="status-pill <?= $r['status'] === 'qualified' ? 'success' : ($r['status'] === 'void' ? 'neutral' : 'warning') ?>"><?= View::e(ucfirst($r['status'])) ?></span></td>
          <td class="cap"><?= View::e(Money::format((int) $r['earned'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$referrals): ?><tr><td colspan="3" class="cap" style="padding:16px;text-align:center">No referrals yet — share your link to get started.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <p class="cap" style="margin-top:12px">
    Referred people are shown by date and status only. We don't share their names or contact details with affiliates.
  </p>
</div>

<h2 class="sec-title">Commissions</h2>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Date</th><th>Base</th><th>Rate</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($commissions as $c): ?>
        <tr>
          <td class="cap"><?= View::e(date('d M Y', strtotime((string) $c['created_at']))) ?></td>
          <td class="cap"><?= View::e(Money::format((int) $c['base_amount'], $c['currency'])) ?></td>
          <td class="cap"><?= number_format(((int) $c['rate_bps']) / 100, 2) ?>%</td>
          <td><span class="cell-main"><?= View::e(Money::format((int) $c['amount'], $c['currency'])) ?></span></td>
          <td><span class="status-pill <?= $c['status'] === 'paid' ? 'success' : ($c['status'] === 'void' ? 'neutral' : ($c['status'] === 'approved' ? 'success' : 'warning')) ?>"><?= View::e(ucfirst($c['status'])) ?></span></td>
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
      <thead><tr><th>Reference</th><th>Requested</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($payouts as $p): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($p['reference']) ?></span></td>
          <td class="cap"><?= View::e(date('d M Y', strtotime((string) $p['requested_at']))) ?></td>
          <td><?= View::e(Money::format((int) $p['amount'], $p['currency'])) ?></td>
          <td><span class="status-pill <?= $p['status'] === 'paid' ? 'success' : 'warning' ?>"><?= View::e(ucfirst($p['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
document.getElementById('copybtn').addEventListener('click', function () {
  var el = document.getElementById('reflink');
  el.select(); el.setSelectionRange(0, 99999);
  try { document.execCommand('copy'); this.textContent = 'Copied'; } catch (e) {}
  setTimeout(function () { document.getElementById('copybtn').textContent = 'Copy'; }, 2000);
});
</script>
