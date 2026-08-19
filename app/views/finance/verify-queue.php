<?php /** @var array $payments */ ?>
<div class="topbar">
  <div>
    <h1>Verify Transfers</h1>
    <p><?= count($payments) ?> bank transfer(s) awaiting verification. Check the bank statement before approving — approving credits the invoice immediately.</p>
  </div>
</div>

<div class="card" style="margin-bottom:16px;border-color:var(--warning)">
  <p class="cap">
    <strong>Separation of duties:</strong> you cannot verify a payment you submitted yourself,
    and a cashier cannot verify bank transfers at all. Both are enforced in the service, not
    just hidden in this page.
  </p>
</div>

<?php if (!$payments): ?>
  <div class="empty-card"><b>Nothing awaiting verification</b><p>Submitted bank transfers appear here.</p></div>
<?php else: ?>
  <?php foreach ($payments as $p): ?>
  <div class="card" style="margin-bottom:14px">
    <div class="chead">
      <h3><?= View::e($p['user_name'] ?: $p['email']) ?> — <?= View::e(Money::format((int) $p['amount'], $p['currency'])) ?></h3>
      <span class="status-pill warning">Pending verification</span>
    </div>
    <div class="prog-meta" style="flex-direction:column;gap:8px;align-items:flex-start;font-size:12.5px;margin-bottom:12px">
      <span><strong>Invoice:</strong>&nbsp;<a href="app.php?r=invoices.show&id=<?= $p['invoice_id'] ?>"><?= View::e($p['invoice_number']) ?></a></span>
      <span><strong>Bank reference:</strong>&nbsp;<?= View::e($p['bank_reference'] ?: '—') ?></span>
      <span><strong>Submitted:</strong>&nbsp;<?= View::e(date('d M Y H:i', strtotime($p['created_at']))) ?></span>
      <span><strong>Centre:</strong>&nbsp;<?= View::e($p['centre_name'] ?? 'Online / global') ?></span>
    </div>
    <a class="btn sm" href="app.php?r=payments.show&id=<?= $p['id'] ?>">Open &amp; review proof</a>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
