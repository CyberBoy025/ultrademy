<?php /** @var array $expenses @var string $status @var array $centres @var bool $canApprove */
$statusColor = ['draft' => 'neutral', 'submitted' => 'warning', 'approved' => 'success', 'rejected' => 'error'];
?>
<div class="topbar">
  <div><h1>Expenses</h1><p>Recording and approving an expense are separate permissions — the person who spends does not sign it off.</p></div>
</div>

<div class="filters">
  <?php foreach (['' => 'All', 'submitted' => 'Awaiting Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label): ?>
    <a class="chip <?= $status === $val ? 'active' : '' ?>" href="app.php?r=expenses<?= $val ? '&status=' . $val : '' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>Record an Expense</h3></div>
  <form method="post" action="app.php?r=expenses.store">
    <?= Csrf::field() ?>
    <div class="field-row">
      <div class="field"><label>Category</label><input type="text" name="category" placeholder="e.g. Utilities, Equipment, Salaries" required></div>
      <div class="field"><label>Amount (₦)</label><input type="text" name="amount" placeholder="25000.00" required></div>
    </div>
    <div class="field-row">
      <div class="field"><label>Centre</label>
        <select name="centre_id">
          <option value="">Head office / global</option>
          <?php foreach ($centres as $c): ?><option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Incurred on</label><input type="date" name="incurred_on" value="<?= date('Y-m-d') ?>"></div>
    </div>
    <div class="field"><label>Description</label><input type="text" name="description"></div>
    <button type="submit" class="btn primary">Submit Expense</button>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Date</th><th>Category</th><th>Centre</th><th>Amount</th><th>Recorded by</th><th>Status</th><?php if ($canApprove): ?><th></th><?php endif; ?></tr></thead>
      <tbody>
        <?php foreach ($expenses as $e): ?>
        <tr>
          <td><?= View::e(date('d M Y', strtotime($e['incurred_on']))) ?></td>
          <td><span class="cell-main"><?= View::e($e['category']) ?></span><span class="cell-sub"><?= View::e($e['description'] ?: '') ?></span></td>
          <td><?= View::e($e['centre_name'] ?? 'Head office') ?></td>
          <td><?= View::e(Money::format((int) $e['amount'], $e['currency'])) ?></td>
          <td class="cap"><?= View::e($e['recorder_name'] ?: '—') ?></td>
          <td><span class="status-pill <?= $statusColor[$e['status']] ?>"><?= View::e(ucfirst($e['status'])) ?></span></td>
          <?php if ($canApprove): ?>
          <td>
            <?php if ($e['status'] === 'submitted'): ?>
            <form method="post" action="app.php?r=expenses.decide" style="display:flex;gap:6px">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $e['id'] ?>">
              <button type="submit" name="decision" value="reject" class="btn sm">Reject</button>
              <button type="submit" name="decision" value="approve" class="btn sm primary">Approve</button>
            </form>
            <?php else: ?><span class="cap"><?= View::e($e['approver_name'] ?: '') ?></span><?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (!$expenses): ?><tr><td colspan="<?= $canApprove ? 7 : 6 ?>" class="cap" style="padding:16px;text-align:center">No expenses in this view.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
