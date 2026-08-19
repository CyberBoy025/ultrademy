<?php
/** @var array $payment @var array $proofs @var bool $canVerify @var bool $isOwnPayment
 *  @var array|null $duplicate @var bool $canRefund */
$payColor = ['initiated' => 'neutral', 'pending_verification' => 'warning', 'successful' => 'success', 'failed' => 'error', 'reversed' => 'error'];
$selfVerify = (int) $payment['user_id'] === (int) Auth::id();
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px">
      <a href="app.php?r=invoices.show&id=<?= $payment['invoice_id'] ?>" style="color:var(--text-3)"><?= View::e($payment['invoice_number']) ?></a> / <?= View::e($payment['reference']) ?>
    </span>
    <h1><?= View::e(Money::format((int) $payment['amount'], $payment['currency'])) ?>
      <span class="status-pill <?= $payColor[$payment['status']] ?>" style="margin-left:8px"><?= View::e(ucwords(str_replace('_', ' ', $payment['status']))) ?></span>
    </h1>
    <p><?= View::e(ucwords(str_replace('_', ' ', $payment['method']))) ?> · <?= View::e($payment['user_name'] ?: $payment['email']) ?></p>
  </div>
</div>

<div class="row row-b">
  <div class="card">
    <div class="chead"><h3>Details</h3></div>
    <div class="prog-meta" style="flex-direction:column;gap:9px;align-items:flex-start;font-size:12.5px">
      <span><strong>Our reference:</strong>&nbsp;<?= View::e($payment['reference']) ?></span>
      <?php if ($payment['bank_reference']): ?><span><strong>Bank reference:</strong>&nbsp;<?= View::e($payment['bank_reference']) ?></span><?php endif; ?>
      <?php if ($payment['gateway_reference']): ?><span><strong>Gateway reference:</strong>&nbsp;<?= View::e($payment['gateway_reference']) ?></span><?php endif; ?>
      <span><strong>Centre:</strong>&nbsp;<?= View::e($payment['centre_name'] ?? 'Online / global') ?></span>
      <span><strong>Created:</strong>&nbsp;<?= View::e(date('d M Y H:i', strtotime($payment['created_at']))) ?></span>
      <?php if ($payment['paid_at']): ?><span><strong>Paid:</strong>&nbsp;<?= View::e(date('d M Y H:i', strtotime($payment['paid_at']))) ?></span><?php endif; ?>
      <?php if ($payment['verifier_name']): ?><span><strong>Verified by:</strong>&nbsp;<?= View::e($payment['verifier_name']) ?></span><?php endif; ?>
      <?php if ($payment['receipt_number']): ?><span><strong>Receipt:</strong>&nbsp;<?= View::e($payment['receipt_number']) ?></span><?php endif; ?>
      <?php if ($payment['failure_reason']): ?><span style="color:var(--error)"><strong>Reason:</strong>&nbsp;<?= View::e($payment['failure_reason']) ?></span><?php endif; ?>
    </div>

    <?php if ($proofs): ?>
      <div class="rule"></div>
      <p class="cap" style="margin-bottom:8px">Proof of payment</p>
      <?php foreach ($proofs as $pr): ?>
        <a class="btn sm" href="app.php?r=proofs.download&id=<?= $pr['id'] ?>"><?= View::e($pr['original_name']) ?> (<?= View::e(Upload::humanSize((int) $pr['size_bytes'])) ?>)</a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="chead"><h3>Decision</h3></div>

    <?php if ($duplicate): ?>
      <div class="card" style="border-color:var(--warning);margin-bottom:12px">
        <p class="cap"><strong>Duplicate reference.</strong> Bank reference
          <?= View::e($payment['bank_reference']) ?> was also submitted on payment
          <a href="app.php?r=payments.show&id=<?= $duplicate['id'] ?>"><?= View::e($duplicate['reference']) ?></a>
          (<?= View::e($duplicate['status']) ?>). Check the statement carefully.</p>
      </div>
    <?php endif; ?>

    <?php if ($payment['status'] !== 'pending_verification'): ?>
      <p class="cap">This payment is <?= View::e(str_replace('_', ' ', $payment['status'])) ?> — no decision is pending.</p>
    <?php elseif (!$canVerify): ?>
      <p class="cap">Bank transfers are verified by finance. A cashier records cash but does not confirm transfers.</p>
    <?php elseif ($selfVerify): ?>
      <p class="cap" style="color:var(--error)">This is your own payment. You cannot verify it — someone else in finance must.</p>
    <?php else: ?>
      <form method="post" action="app.php?r=payments.verify">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= $payment['id'] ?>">
        <div class="field"><label>Note</label><input type="text" name="note" placeholder="e.g. matched statement line 14 Aug"></div>
        <div style="display:flex;gap:8px">
          <button type="submit" name="decision" value="reject" class="btn" style="flex:1;justify-content:center">Reject</button>
          <button type="submit" name="decision" value="approve" class="btn primary" style="flex:1;justify-content:center">Approve</button>
        </div>
      </form>
      <p class="cap" style="margin-top:10px">Approving credits the invoice and issues a receipt immediately.</p>
    <?php endif; ?>

    <?php if ($canRefund && $payment['status'] === 'successful'): ?>
      <div class="rule"></div>
      <form method="post" action="app.php?r=refunds.store">
        <?= Csrf::field() ?>
        <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
        <div class="field"><label>Refund amount (₦)</label><input type="text" name="amount" value="<?= View::e(Money::toMajorString((int) $payment['amount'])) ?>" required></div>
        <div class="field"><label>Reason</label><input type="text" name="reason" required></div>
        <button type="submit" class="btn">Raise Refund</button>
        <p class="cap" style="margin-top:8px">A refund you raise must be approved by management.</p>
      </form>
    <?php endif; ?>
  </div>
</div>
