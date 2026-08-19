<?php
/** @var array $enrolment @var bool $canTransfer @var bool $showContact @var array $cohorts @var array $history */
$statusColor = ['pending_payment' => 'warning', 'active' => 'success', 'suspended' => 'error', 'withdrawn' => 'neutral', 'completed' => 'info'];
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=students" style="color:var(--text-3)">Students</a> / <?= View::e($enrolment['student_no']) ?></span>
    <h1><?= View::e($enrolment['student_name'] ?: $enrolment['student_no']) ?>
      <span class="status-pill <?= $statusColor[$enrolment['status']] ?>" style="margin-left:8px"><?= View::e(ucwords(str_replace('_', ' ', $enrolment['status']))) ?></span>
    </h1>
    <p><?= View::e($enrolment['student_no']) ?> · <?= View::e($enrolment['programme_title']) ?> · <?= View::e($enrolment['centre_name'] ?? 'Online') ?></p>
  </div>
</div>

<div class="row row-b">
  <div class="card">
    <div class="chead"><h3>Enrolment</h3></div>
    <div class="prog-meta" style="flex-direction:column;gap:10px;align-items:flex-start;font-size:12.5px">
      <span><strong>Programme:</strong>&nbsp;<?= View::e($enrolment['programme_title']) ?></span>
      <span><strong>Cohort:</strong>&nbsp;<?= View::e($enrolment['cohort_name']) ?></span>
      <span><strong>Centre:</strong>&nbsp;<?= View::e($enrolment['centre_name'] ?? 'Online') ?></span>
      <span><strong>Enrolled:</strong>&nbsp;<?= View::e(date('d M Y', strtotime($enrolment['enrolled_at']))) ?></span>
      <?php if ($enrolment['application_reference']): ?>
      <span><strong>From application:</strong>&nbsp;<?= View::e($enrolment['application_reference']) ?></span>
      <?php else: ?>
      <span><strong>Source:</strong>&nbsp;Direct enrolment (no online application)</span>
      <?php endif; ?>
      <?php if ($showContact): ?>
        <span><strong>Email:</strong>&nbsp;<?= View::e($enrolment['email']) ?></span>
        <?php if ($enrolment['phone']): ?><span><strong>Phone:</strong>&nbsp;<?= View::e($enrolment['phone']) ?></span><?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="chead"><h3>Manage</h3></div>
    <div class="stack">
      <form method="post" action="app.php?r=students.status">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $enrolment['id'] ?>">
        <div class="field">
          <label>Enrolment status</label>
          <select name="status">
            <?php foreach (['pending_payment' => 'Awaiting Payment', 'active' => 'Active', 'suspended' => 'Suspended', 'completed' => 'Completed', 'withdrawn' => 'Withdrawn'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $enrolment['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn primary" style="width:100%;justify-content:center">Update Status</button>
      </form>
      <?php if ($enrolment['status'] === 'pending_payment'): ?>
        <p class="cap">Marking this active is manual for now — Phase 9 will do it automatically when the invoice is paid.</p>
      <?php endif; ?>

      <?php if ($canTransfer): ?>
      <div class="divider" style="height:1px;background:var(--border);margin:6px 0"></div>
      <form method="post" action="app.php?r=students.transfer" onsubmit="return confirm('Transfer this student? The current enrolment is closed and kept as history.')">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $enrolment['id'] ?>">
        <div class="field">
          <label>Transfer to cohort</label>
          <select name="cohort_id" required>
            <?php foreach ($cohorts as $c): ?>
              <?php if ((int) $c['id'] === (int) $enrolment['cohort_id']) continue; ?>
              <option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?> — <?= View::e($c['centre_name'] ?? 'Online') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn" style="width:100%;justify-content:center">Transfer</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if (count($history) > 1): ?>
<h2 class="sec-title">Enrolment History</h2>
<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Student No.</th><th>Programme</th><th>Cohort</th><th>Centre</th><th>Status</th><th>Enrolled</th></tr></thead>
      <tbody>
        <?php foreach ($history as $h): ?>
        <tr <?= (int) $h['id'] === (int) $enrolment['id'] ? 'style="background:var(--surface-muted)"' : '' ?>>
          <td><span class="cell-main"><?= View::e($h['student_no']) ?></span></td>
          <td><?= View::e($h['programme_title']) ?></td>
          <td><?= View::e($h['cohort_name']) ?></td>
          <td><?= View::e($h['centre_name'] ?? 'Online') ?></td>
          <td><span class="status-pill <?= $statusColor[$h['status']] ?>"><?= View::e(ucwords(str_replace('_', ' ', $h['status']))) ?></span></td>
          <td><?= View::e(date('d M Y', strtotime($h['enrolled_at']))) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
