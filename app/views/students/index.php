<?php
/** @var array $students @var string $status @var bool $canTransfer @var bool $showContact */
$statusColor = ['pending_payment' => 'warning', 'active' => 'success', 'suspended' => 'error', 'withdrawn' => 'neutral', 'completed' => 'info'];
?>
<div class="topbar">
  <div>
    <h1>Students</h1>
    <p><?= count($students) ?> enrolment record<?= count($students) === 1 ? '' : 's' ?> in your scope. A transfer keeps the old record as history.</p>
  </div>
</div>

<div class="filters">
  <?php foreach (['' => 'All', 'pending_payment' => 'Awaiting Payment', 'active' => 'Active', 'suspended' => 'Suspended', 'completed' => 'Completed', 'withdrawn' => 'Withdrawn'] as $val => $label): ?>
    <a class="chip <?= $status === $val ? 'active' : '' ?>" href="app.php?r=students<?= $val ? '&status=' . $val : '' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$showContact): ?>
<div class="card" style="margin-bottom:16px">
  <p class="cap">Contact details are hidden for your role — you see names, programmes and progress.</p>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead>
        <tr>
          <th>Student No.</th><th>Name</th>
          <?php if ($showContact): ?><th>Email</th><?php endif; ?>
          <th>Programme</th><th>Cohort</th><th>Centre</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr onclick="location='app.php?r=students.show&id=<?= $s['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($s['student_no']) ?></span></td>
          <td><?= View::e($s['student_name'] ?: '—') ?></td>
          <?php if ($showContact): ?><td class="cap"><?= View::e($s['email']) ?></td><?php endif; ?>
          <td><?= View::e($s['programme_title']) ?></td>
          <td><?= View::e($s['cohort_name']) ?></td>
          <td><?= View::e($s['centre_name'] ?? 'Online') ?></td>
          <td><span class="status-pill <?= $statusColor[$s['status']] ?>"><?= View::e(ucwords(str_replace('_', ' ', $s['status']))) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$students): ?><tr><td colspan="<?= $showContact ? 7 : 6 ?>" class="cap" style="padding:16px;text-align:center">No students in this view.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
