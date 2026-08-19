<?php /** @var array $invoices @var array $payments */
$statusColor = ['draft' => 'neutral', 'issued' => 'info', 'part_paid' => 'warning', 'paid' => 'success', 'overdue' => 'error', 'void' => 'neutral'];
$payColor = ['initiated' => 'neutral', 'pending_verification' => 'warning', 'successful' => 'success', 'failed' => 'error', 'reversed' => 'error'];
$due = 0;
foreach ($invoices as $i) {
    if (!in_array($i['status'], ['paid', 'void'], true)) {
        $due += max(0, ((int) $i['total_amount']) - ((int) $i['paid_amount']));
    }
}
?>
<div class="topbar">
  <div><h1>My Payments</h1><p>Your invoices, what you've paid, and your receipts.</p></div>
</div>

<?php if ($due > 0): ?>
<div class="card" style="margin-bottom:20px;border-color:var(--warning)">
  <div style="display:flex;align-items:baseline;gap:6px"><span class="pct"><?= View::e(Money::format($due)) ?></span><span class="cap">outstanding</span></div>
</div>
<?php endif; ?>

<h2 class="sec-title">Invoices</h2>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Invoice</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($invoices as $i): ?>
        <tr onclick="location='app.php?r=invoices.show&id=<?= $i['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($i['number']) ?></span><span class="cell-sub"><?= View::e(date('d M Y', strtotime($i['created_at']))) ?></span></td>
          <td><?= View::e(Money::format((int) $i['total_amount'], $i['currency'])) ?></td>
          <td><?= View::e(Money::format((int) $i['paid_amount'], $i['currency'])) ?></td>
          <td><?= $i['due_on'] ? View::e(date('d M Y', strtotime($i['due_on']))) : '—' ?></td>
          <td><span class="status-pill <?= $statusColor[$i['status']] ?>"><?= View::e(ucwords(str_replace('_', ' ', $i['status']))) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$invoices): ?><tr><td colspan="5" class="cap" style="padding:16px;text-align:center">You have no invoices.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<h2 class="sec-title">Payments &amp; Receipts</h2>
<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Invoice</th><th>Method</th><th>Amount</th><th>Receipt</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr onclick="location='app.php?r=payments.show&id=<?= $p['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($p['reference']) ?></span><span class="cell-sub"><?= View::e(date('d M Y H:i', strtotime($p['created_at']))) ?></span></td>
          <td><?= View::e($p['invoice_number']) ?></td>
          <td><?= View::e(ucwords(str_replace('_', ' ', $p['method']))) ?></td>
          <td><?= View::e(Money::format((int) $p['amount'], $p['currency'])) ?></td>
          <td class="cap"><?= View::e($p['receipt_number'] ?? '—') ?></td>
          <td><span class="status-pill <?= $payColor[$p['status']] ?>"><?= View::e(ucwords(str_replace('_', ' ', $p['status']))) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$payments): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">No payments yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
