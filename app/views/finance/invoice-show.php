<?php
/** @var array $invoice @var array $lines @var array $payments @var int $balance @var bool $isOwner
 *  @var bool $canRecordCash @var bool $canVoid @var bool $canRefund @var array $methods @var array $bank */
$statusColor = ['draft' => 'neutral', 'issued' => 'info', 'part_paid' => 'warning', 'paid' => 'success', 'overdue' => 'error', 'void' => 'neutral'];
$payColor = ['initiated' => 'neutral', 'pending_verification' => 'warning', 'successful' => 'success', 'failed' => 'error', 'reversed' => 'error'];
$settled = in_array($invoice['status'], ['paid', 'void'], true);
$back = $isOwner && !Auth::can('finance.invoice.view_any') ? 'billing' : 'invoices';
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px">
      <a href="app.php?r=<?= $back ?>" style="color:var(--text-3)"><?= $back === 'billing' ? 'My Payments' : 'Invoices' ?></a> / <?= View::e($invoice['number']) ?>
    </span>
    <h1><?= View::e($invoice['number']) ?>
      <span class="status-pill <?= $statusColor[$invoice['status']] ?>" style="margin-left:8px"><?= View::e(ucwords(str_replace('_', ' ', $invoice['status']))) ?></span>
    </h1>
    <p>
      <?= View::e($invoice['user_name'] ?: $invoice['email']) ?>
      · <?= View::e($invoice['centre_name'] ?? 'Online / global') ?>
      <?php if ($invoice['due_on']): ?> · due <?= View::e(date('d M Y', strtotime($invoice['due_on']))) ?><?php endif; ?>
    </p>
  </div>
</div>

<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
  <div class="card kpi-card"><span class="lab">Total</span><span class="val"><?= View::e(Money::format((int) $invoice['total_amount'], $invoice['currency'])) ?></span></div>
  <div class="card kpi-card"><span class="lab">Paid</span><span class="val"><?= View::e(Money::format((int) $invoice['paid_amount'], $invoice['currency'])) ?></span></div>
  <div class="card kpi-card"><span class="lab">Balance</span><span class="val"><?= View::e(Money::format($balance, $invoice['currency'])) ?></span></div>
</div>

<h2 class="sec-title">Lines</h2>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Description</th><th>Qty</th><th>Unit</th><th>Amount</th></tr></thead>
      <tbody>
        <?php foreach ($lines as $l): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($l['description']) ?></span></td>
          <td><?= (int) $l['quantity'] ?></td>
          <td><?= View::e(Money::format((int) $l['unit_amount'], $invoice['currency'])) ?></td>
          <td><?= View::e(Money::format((int) $l['line_amount'], $invoice['currency'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ((int) $invoice['discount_amount'] > 0): ?>
    <p class="cap" style="margin-top:10px">Discount: −<?= View::e(Money::format((int) $invoice['discount_amount'], $invoice['currency'])) ?></p>
  <?php endif; ?>
  <?php if ($invoice['status'] === 'void'): ?>
    <p class="cap" style="margin-top:10px;color:var(--error)">Voided: <?= View::e($invoice['void_reason'] ?: 'no reason recorded') ?></p>
  <?php endif; ?>
</div>

<?php if ($isOwner && !$settled && $balance > 0): ?>
<h2 class="sec-title">Pay this invoice</h2>
<div class="row row-b">
  <div class="card">
    <div class="chead"><h3>Pay online</h3></div>
    <?php if ($methods): ?>
    <form method="post" action="app.php?r=invoices.pay">
      <?= Csrf::field() ?>
      <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
      <div class="field">
        <label>Method</label>
        <select name="method">
          <?php foreach ($methods as $code => $label): if ($code === 'bank_transfer') continue; ?>
          <option value="<?= View::e($code) ?>"><?= View::e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn primary">Pay <?= View::e(Money::format($balance, $invoice['currency'])) ?></button>
    </form>
    <?php else: ?>
      <p class="cap">No online payment provider is configured yet. Use a bank transfer below.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="chead"><h3>Bank transfer</h3></div>
    <?php if ($bank['account_number'] !== ''): ?>
      <p class="cap" style="margin-bottom:12px">
        Pay <strong><?= View::e(Money::format($balance, $invoice['currency'])) ?></strong> to:<br>
        <?= View::e($bank['account_name']) ?><br>
        <?= View::e($bank['bank']) ?> · <?= View::e($bank['account_number']) ?><br>
        Quote <strong><?= View::e($invoice['number']) ?></strong> as your narration.
      </p>
    <?php else: ?>
      <p class="cap" style="margin-bottom:12px">Bank details have not been configured yet — ask an administrator to set them in Settings.</p>
    <?php endif; ?>
    <form method="post" action="app.php?r=invoices.pay" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
      <input type="hidden" name="method" value="bank_transfer">
      <div class="field"><label>Your bank reference</label><input type="text" name="bank_reference" required></div>
      <div class="field"><label>Proof of payment</label><input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png"><span class="cap">PDF or image, max 5 MB.</span></div>
      <button type="submit" class="btn">Submit for verification</button>
    </form>
    <p class="cap" style="margin-top:10px">Nothing is credited until finance confirms the money arrived.</p>
  </div>
</div>
<?php endif; ?>

<?php if ($canRecordCash && !$settled && $balance > 0): ?>
<h2 class="sec-title">Cashier</h2>
<div class="card" style="margin-bottom:20px">
  <form method="post" action="app.php?r=payments.cash">
    <?= Csrf::field() ?>
    <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
    <div class="field-row">
      <div class="field"><label>Cash received (₦)</label><input type="text" name="amount" value="<?= View::e(Money::toMajorString($balance)) ?>" required></div>
      <div class="field" style="justify-content:flex-end"><button type="submit" class="btn primary">Record Cash &amp; Issue Receipt</button></div>
    </div>
    <p class="cap">Recorded against you and reconciled at close of day.</p>
  </form>
</div>
<?php endif; ?>

<h2 class="sec-title">Payments</h2>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Method</th><th>Amount</th><th>Status</th><th>Receipt</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($p['reference']) ?></span><span class="cell-sub"><?= View::e(date('d M Y H:i', strtotime($p['created_at']))) ?></span></td>
          <td><?= View::e(ucwords(str_replace('_', ' ', $p['method']))) ?></td>
          <td><?= View::e(Money::format((int) $p['amount'], $p['currency'])) ?></td>
          <td><span class="status-pill <?= $payColor[$p['status']] ?>"><?= View::e(ucwords(str_replace('_', ' ', $p['status']))) ?></span></td>
          <td class="cap"><?= View::e($p['receipt_number'] ?? '—') ?></td>
          <td><a class="btn sm" href="app.php?r=payments.show&id=<?= $p['id'] ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$payments): ?><tr><td colspan="6" class="cap" style="padding:14px;text-align:center">No payments against this invoice yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canVoid && !in_array($invoice['status'], ['void', 'paid'], true)): ?>
<div class="card">
  <div class="chead"><h3>Void this invoice</h3></div>
  <form method="post" action="app.php?r=invoices.void" onsubmit="return confirm('Void this invoice? This is audited.')">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $invoice['id'] ?>">
    <div class="field"><label>Reason (required)</label><input type="text" name="reason" required></div>
    <button type="submit" class="btn">Void Invoice</button>
  </form>
  <p class="cap" style="margin-top:8px">An invoice with payments against it cannot be voided — raise a refund instead.</p>
</div>
<?php endif; ?>
