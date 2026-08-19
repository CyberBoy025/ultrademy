<?php /** @var array $refunds @var string $status @var bool $canApprove */
$statusColor = ['requested' => 'warning', 'approved' => 'success', 'rejected' => 'error', 'processed' => 'info'];
?>
<div class="topbar">
  <div><h1>Refunds</h1><p>Raised by finance, approved by management. The same person can never do both.</p></div>
</div>

<div class="filters">
  <?php foreach (['' => 'All', 'requested' => 'Awaiting Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label): ?>
    <a class="chip <?= $status === $val ? 'active' : '' ?>" href="app.php?r=refunds<?= $val ? '&status=' . $val : '' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Payment</th><th>Payer</th><th>Amount</th><th>Reason</th><th>Raised by</th><th>Status</th><?php if ($canApprove): ?><th></th><?php endif; ?></tr></thead>
      <tbody>
        <?php foreach ($refunds as $r): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($r['payment_reference']) ?></span><span class="cell-sub"><?= View::e($r['invoice_number']) ?></span></td>
          <td><?= View::e($r['user_name'] ?: '—') ?></td>
          <td><?= View::e(Money::format((int) $r['amount'], $r['currency'])) ?></td>
          <td class="cap"><?= View::e($r['reason']) ?></td>
          <td class="cap"><?= View::e($r['requester_name'] ?: '—') ?></td>
          <td><span class="status-pill <?= $statusColor[$r['status']] ?>"><?= View::e(ucfirst($r['status'])) ?></span>
            <?php if ($r['approver_name']): ?><span class="cell-sub">by <?= View::e($r['approver_name']) ?></span><?php endif; ?>
          </td>
          <?php if ($canApprove): ?>
          <td>
            <?php if ($r['status'] === 'requested'): ?>
            <form method="post" action="app.php?r=refunds.decide" style="display:flex;gap:6px">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button type="submit" name="decision" value="reject" class="btn sm">Reject</button>
              <button type="submit" name="decision" value="approve" class="btn sm primary">Approve</button>
            </form>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (!$refunds): ?><tr><td colspan="<?= $canApprove ? 7 : 6 ?>" class="cap" style="padding:16px;text-align:center">No refunds. They are raised from a successful payment's page.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
