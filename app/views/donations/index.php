<?php /** @var array $donations @var array $totals @var string $status @var bool $canExport */ ?>
<div class="topbar">
  <div>
    <h1>Donations</h1>
    <p><?= View::e(Money::format($totals['total'])) ?> from <?= (int) $totals['count'] ?> completed gift(s) · average <?= View::e(Money::format($totals['average'])) ?></p>
  </div>
  <div style="display:flex;gap:8px">
    <?php if (Auth::can('donation.campaign.manage')): ?><a class="btn sm" href="app.php?r=donations.campaigns">Campaigns</a><?php endif; ?>
    <?php if ($canExport): ?><a class="btn sm" href="app.php?r=donations.export">Export CSV</a><?php endif; ?>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <form method="get" action="app.php" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="r" value="donations">
    <div class="field" style="margin:0;min-width:180px">
      <label>Status</label>
      <select name="status" onchange="this.form.submit()">
        <?php foreach (['' => 'All', 'completed' => 'Completed', 'pending' => 'Pending', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $k => $label): ?>
          <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= View::e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Date</th><th>Supporter</th><th>Campaign</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($donations as $d): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($d['reference']) ?></span></td>
          <td class="cap"><?= View::e(date('d M Y H:i', strtotime((string) $d['created_at']))) ?></td>
          <td>
            <span class="cell-main"><?= View::e($d['donor_name']) ?><?= (int) $d['is_anonymous'] === 1 ? ' (anonymous publicly)' : '' ?></span>
            <span class="cap" style="display:block"><?= View::e($d['donor_email']) ?></span>
          </td>
          <td class="cap"><?= View::e($d['campaign_title'] ?? 'General fund') ?></td>
          <td><span class="cell-main"><?= View::e(Money::format((int) $d['amount'], $d['currency'])) ?></span></td>
          <td>
            <span class="status-pill <?= $d['status'] === 'completed' ? 'success' : ($d['status'] === 'pending' ? 'warning' : 'neutral') ?>"><?= View::e(ucfirst($d['status'])) ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$donations): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">No donations recorded.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
