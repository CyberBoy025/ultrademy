<?php
/** @var array $invoices @var string $status @var bool $canCreate @var array $users @var array $centres */
$statusColor = ['draft' => 'neutral', 'issued' => 'info', 'part_paid' => 'warning', 'paid' => 'success', 'overdue' => 'error', 'void' => 'neutral'];
?>
<div class="topbar">
  <div>
    <h1>Invoices</h1>
    <p>Money owed. An invoice is never deleted — voiding is audited and a paid invoice must be refunded instead.</p>
  </div>
</div>

<div class="filters">
  <?php foreach (['' => 'All', 'issued' => 'Issued', 'part_paid' => 'Part Paid', 'overdue' => 'Overdue', 'paid' => 'Paid', 'void' => 'Void'] as $val => $label): ?>
    <a class="chip <?= $status === $val ? 'active' : '' ?>" href="app.php?r=invoices<?= $val ? '&status=' . $val : '' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if ($canCreate): ?>
<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>New Invoice</h3></div>
  <form method="post" action="app.php?r=invoices.store">
    <?= Csrf::field() ?>
    <div class="field-row">
      <div class="field"><label>Bill to</label>
        <select name="user_id" required>
          <option value="">— Choose a user —</option>
          <?php foreach ($users as $u): ?>
          <option value="<?= $u['id'] ?>"><?= View::e(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: $u['email']) ?> — <?= View::e($u['email']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Centre</label>
        <select name="centre_id">
          <option value="">Online / global</option>
          <?php foreach ($centres as $c): ?><option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?>
        </select>
        <span class="cap">Blank keeps it out of any physical centre's books.</span>
      </div>
    </div>
    <div class="field"><label>Description</label><input type="text" name="description" placeholder="e.g. Web Development — programme fee" required></div>
    <div class="field-row">
      <div class="field"><label>Amount (₦)</label><input type="text" name="amount" placeholder="45000.00" required></div>
      <div class="field"><label>Due date</label><input type="date" name="due_on"></div>
    </div>
    <div class="field-row">
      <div class="field"><label>For</label>
        <select name="payable_type">
          <option value="other">Other</option>
          <option value="enrolment">Enrolment</option>
          <option value="subscription">Subscription</option>
          <option value="application_fee">Application fee</option>
        </select>
        <span class="cap">Paying an enrolment or subscription invoice in full activates it automatically.</span>
      </div>
      <div class="field"><label>Record id (optional)</label><input type="number" name="payable_id" min="1" placeholder="enrolment / subscription id"></div>
    </div>
    <button type="submit" class="btn primary">Issue Invoice</button>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Number</th><th>Billed to</th><th>Centre</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($invoices as $i): ?>
        <tr onclick="location='app.php?r=invoices.show&id=<?= $i['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($i['number']) ?></span></td>
          <td><span class="cell-main"><?= View::e($i['user_name'] ?: '—') ?></span><span class="cell-sub"><?= View::e($i['email']) ?></span></td>
          <td><?= View::e($i['centre_name'] ?? 'Online') ?></td>
          <td><?= View::e(Money::format((int) $i['total_amount'], $i['currency'])) ?></td>
          <td><?= View::e(Money::format((int) $i['paid_amount'], $i['currency'])) ?></td>
          <td><?= $i['due_on'] ? View::e(date('d M Y', strtotime($i['due_on']))) : '—' ?></td>
          <td><span class="status-pill <?= $statusColor[$i['status']] ?>"><?= View::e(ucwords(str_replace('_', ' ', $i['status']))) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$invoices): ?><tr><td colspan="7" class="cap" style="padding:16px;text-align:center">No invoices in this view.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
