<?php
/** @var array $applications @var array $counts @var string $status */
$statusColor = [
    'draft' => 'neutral', 'submitted' => 'info', 'under_review' => 'warning',
    'approved' => 'success', 'rejected' => 'error', 'withdrawn' => 'neutral',
];
$total = array_sum($counts);
?>
<div class="topbar">
  <div>
    <h1>Applications</h1>
    <p><?= (int) ($counts['submitted'] ?? 0) ?> new · <?= (int) ($counts['under_review'] ?? 0) ?> under review · <?= $total ?> total</p>
  </div>
</div>

<div class="filters">
  <?php foreach (['' => 'All', 'submitted' => 'New', 'under_review' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn'] as $val => $label): ?>
    <a class="chip <?= $status === $val ? 'active' : '' ?>" href="app.php?r=applications<?= $val ? '&status=' . $val : '' ?>">
      <?= $label ?><?= $val && isset($counts[$val]) ? ' (' . $counts[$val] . ')' : '' ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Applicant</th><th>Programme</th><th>Centre</th><th>Submitted</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($applications as $a): ?>
        <tr onclick="location='app.php?r=applications.show&id=<?= $a['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($a['reference']) ?></span></td>
          <td><span class="cell-main"><?= View::e($a['applicant_name'] ?: '—') ?></span><span class="cell-sub"><?= View::e($a['email']) ?></span></td>
          <td><?= View::e($a['programme_title']) ?></td>
          <td><?= View::e($a['centre_name'] ?? 'Online') ?></td>
          <td><?= $a['submitted_at'] ? View::e(date('d M Y', strtotime($a['submitted_at']))) : '—' ?></td>
          <td><span class="status-pill <?= $statusColor[$a['status']] ?>"><?= View::e(ucwords(str_replace('_', ' ', $a['status']))) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$applications): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">Nothing in this view.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
