<?php /** @var array $payments @var string $status @var string $method */
$payColor = ['initiated' => 'neutral', 'pending_verification' => 'warning', 'successful' => 'success', 'failed' => 'error', 'reversed' => 'error'];
?>
<div class="topbar">
  <div><h1>Payments</h1><p>Every payment attempt, whatever its outcome. Rows are never edited — corrections are new rows.</p></div>
</div>

<div class="filters">
  <?php foreach (['' => 'All', 'initiated' => 'Initiated', 'pending_verification' => 'Awaiting Verification', 'successful' => 'Successful', 'failed' => 'Failed', 'reversed' => 'Reversed'] as $val => $label): ?>
    <a class="chip <?= $status === $val ? 'active' : '' ?>" href="app.php?r=payments<?= $val ? '&status=' . $val : '' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Payer</th><th>Invoice</th><th>Method</th><th>Amount</th><th>Centre</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr onclick="location='app.php?r=payments.show&id=<?= $p['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($p['reference']) ?></span><span class="cell-sub"><?= View::e(date('d M Y H:i', strtotime($p['created_at']))) ?></span></td>
          <td><?= View::e($p['user_name'] ?: $p['email']) ?></td>
          <td><?= View::e($p['invoice_number']) ?></td>
          <td><?= View::e(ucwords(str_replace('_', ' ', $p['method']))) ?></td>
          <td><?= View::e(Money::format((int) $p['amount'], $p['currency'])) ?></td>
          <td><?= View::e($p['centre_name'] ?? 'Online') ?></td>
          <td><span class="status-pill <?= $payColor[$p['status']] ?>"><?= View::e(ucwords(str_replace('_', ' ', $p['status']))) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$payments): ?><tr><td colspan="7" class="cap" style="padding:16px;text-align:center">No payments in this view.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
