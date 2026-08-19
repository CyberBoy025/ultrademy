<?php
/** @var string $from @var string $to @var array $summary @var array $byCentre @var array $byMethod
 *  @var array $outstanding @var bool $isGlobal @var bool $canReconcile @var array $runs */
?>
<div class="topbar">
  <div>
    <h1>Financial Reports</h1>
    <p><?= View::e(date('d M Y', strtotime($from))) ?> – <?= View::e(date('d M Y', strtotime($to))) ?><?= $isGlobal ? ' · all centres' : ' · your centre(s)' ?></p>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <form method="get" action="app.php" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="r" value="reports">
    <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= View::e($from) ?>"></div>
    <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= View::e($to) ?>"></div>
    <button type="submit" class="btn primary">Apply</button>
  </form>
</div>

<div class="kpi-grid">
  <div class="card kpi-card"><span class="lab">Revenue</span><span class="val"><?= View::e(Money::formatShort((int) $summary['revenue'])) ?></span><span class="cap">Successful payments</span></div>
  <div class="card kpi-card"><span class="lab">Expenses</span><span class="val"><?= View::e(Money::formatShort((int) $summary['expenses'])) ?></span><span class="cap">Approved only</span></div>
  <div class="card kpi-card"><span class="lab">Net</span><span class="val" style="color:<?= $summary['net'] >= 0 ? 'var(--success)' : 'var(--error)' ?>"><?= View::e(Money::formatShort((int) $summary['net'])) ?></span></div>
  <div class="card kpi-card"><span class="lab">Outstanding</span><span class="val"><?= View::e(Money::formatShort((int) $summary['outstanding'])) ?></span><span class="cap"><?= (int) $summary['pending_verification'] ?> awaiting verification</span></div>
</div>

<?php if ($isGlobal && $byCentre): ?>
<h2 class="sec-title">By Centre</h2>
<div class="card" style="margin-bottom:20px">
  <p class="cap" style="margin-bottom:12px">Online and head-office transactions are their own line — never folded into a physical centre (README §31).</p>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Centre</th><th>Revenue</th><th>Expenses</th><th>Net</th></tr></thead>
      <tbody>
        <?php foreach ($byCentre as $c): $net = (int) $c['revenue'] - (int) $c['expenses']; ?>
        <tr>
          <td><span class="cell-main"><?= View::e($c['name']) ?></span></td>
          <td><?= View::e(Money::format((int) $c['revenue'])) ?></td>
          <td><?= View::e(Money::format((int) $c['expenses'])) ?></td>
          <td style="color:<?= $net >= 0 ? 'var(--success)' : 'var(--error)' ?>"><?= View::e(Money::format($net)) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="row row-b">
  <div class="card">
    <div class="chead"><h3>Revenue by Method</h3></div>
    <div class="table-wrap">
      <table class="dt">
        <thead><tr><th>Method</th><th>Count</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach ($byMethod as $m): ?>
          <tr>
            <td><?= View::e(ucwords(str_replace('_', ' ', $m['method']))) ?></td>
            <td><?= (int) $m['n'] ?></td>
            <td><?= View::e(Money::format((int) $m['total'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$byMethod): ?><tr><td colspan="3" class="cap" style="padding:12px;text-align:center">No successful payments in this period.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($canReconcile): ?>
  <div class="card">
    <div class="chead"><h3>Reconciliation</h3></div>
    <form method="post" action="app.php?r=reports.reconcile">
      <?= Csrf::field() ?>
      <input type="hidden" name="from" value="<?= View::e($from) ?>">
      <input type="hidden" name="to" value="<?= View::e($to) ?>">
      <button type="submit" class="btn primary">Run for this period</button>
    </form>
    <p class="cap" style="margin-top:10px">Exceptions are listed for a person to decide on — nothing is auto-corrected.</p>
    <?php if ($runs): ?>
      <div class="rule"></div>
      <?php foreach ($runs as $run): ?>
      <div class="queue-item">
        <div class="queue-t">
          <h4><?= View::e(date('d M Y H:i', strtotime($run['created_at']))) ?></h4>
          <p><?= (int) $run['checked_count'] ?> checked · <?= (int) $run['matched_count'] ?> matched · <?= (int) $run['exception_count'] ?> exception(s)</p>
        </div>
        <span class="status-pill <?= (int) $run['exception_count'] === 0 ? 'success' : 'warning' ?>"><?= (int) $run['exception_count'] ?></span>
      </div>
      <?php if ((int) $run['exception_count'] > 0 && $run['exceptions']): ?>
        <?php foreach (array_slice(json_decode((string) $run['exceptions'], true) ?: [], 0, 5) as $x): ?>
          <p class="cap" style="padding-left:10px">• <?= View::e($x['reference'] ?? '') ?> — <?= View::e($x['issue'] ?? '') ?></p>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<h2 class="sec-title">Outstanding Invoices</h2>
<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Invoice</th><th>Billed to</th><th>Centre</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($outstanding as $o): ?>
        <tr onclick="location='app.php?r=invoices.show&id=<?= $o['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($o['number']) ?></span></td>
          <td><?= View::e($o['user_name'] ?: '—') ?></td>
          <td><?= View::e($o['centre_name'] ?? 'Online') ?></td>
          <td><?= View::e(Money::format((int) $o['total_amount'])) ?></td>
          <td><?= View::e(Money::format((int) $o['paid'])) ?></td>
          <td><?= $o['due_on'] ? View::e(date('d M Y', strtotime($o['due_on']))) : '—' ?></td>
          <td><span class="status-pill <?= $o['status'] === 'overdue' ? 'error' : 'warning' ?>"><?= View::e(ucwords(str_replace('_', ' ', $o['status']))) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$outstanding): ?><tr><td colspan="7" class="cap" style="padding:16px;text-align:center">Nothing outstanding.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
