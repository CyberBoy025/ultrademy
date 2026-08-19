<?php
/** @var array $applications @var array $enrolments */
$statusColor = [
    'draft' => 'neutral', 'submitted' => 'info', 'under_review' => 'warning',
    'approved' => 'success', 'rejected' => 'error', 'withdrawn' => 'neutral',
];
$enrolColor = ['pending_payment' => 'warning', 'active' => 'success', 'suspended' => 'error', 'withdrawn' => 'neutral', 'completed' => 'info'];
?>
<div class="topbar">
  <div>
    <h1>My Applications</h1>
    <p>Track where each application has got to.</p>
  </div>
  <div class="actions"><a class="btn primary" href="app.php?r=apply">Apply for a Programme</a></div>
</div>

<?php if ($enrolments): ?>
<h2 class="sec-title">My Enrolments</h2>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Student No.</th><th>Programme</th><th>Cohort</th><th>Centre</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($enrolments as $e): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($e['student_no']) ?></span></td>
          <td><?= View::e($e['programme_title']) ?></td>
          <td><?= View::e($e['cohort_name']) ?></td>
          <td><?= View::e($e['centre_name'] ?? 'Online') ?></td>
          <td><span class="status-pill <?= $enrolColor[$e['status']] ?>"><?= View::e(ucwords(str_replace('_', ' ', $e['status']))) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<h2 class="sec-title">Applications</h2>
<?php if (!$applications): ?>
  <div class="empty-card">
    <b>No applications yet</b>
    <p>When you apply for a programme it will appear here, along with its status and any documents we still need.</p>
  </div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Programme</th><th>Centre</th><th>Submitted</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($applications as $a): ?>
        <tr onclick="location='app.php?r=applications.show&id=<?= $a['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($a['reference']) ?></span></td>
          <td><?= View::e($a['programme_title']) ?></td>
          <td><?= View::e($a['centre_name'] ?? 'Online') ?></td>
          <td><?= $a['submitted_at'] ? View::e(date('d M Y', strtotime($a['submitted_at']))) : '—' ?></td>
          <td><span class="status-pill <?= $statusColor[$a['status']] ?>"><?= View::e(ucwords(str_replace('_', ' ', $a['status']))) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
