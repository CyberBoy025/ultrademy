<?php /** @var array $cohorts */
$statusColor = ['planned' => 'neutral', 'open' => 'info', 'running' => 'success', 'completed' => 'neutral', 'cancelled' => 'error'];
?>
<div class="topbar">
  <div><h1>Cohorts</h1><p>Across every programme. New cohorts are added from a programme's page.</p></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Cohort</th><th>Programme</th><th>Centre</th><th>Starts</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($cohorts as $co): ?>
        <tr onclick="location='app.php?r=cohorts.show&id=<?= $co['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($co['name']) ?></span><span class="cell-sub"><?= View::e($co['code']) ?></span></td>
          <td><?= View::e($co['programme_title']) ?></td>
          <td><?= View::e($co['centre_name'] ?? 'Online') ?></td>
          <td><?= $co['starts_on'] ? View::e(date('d M Y', strtotime($co['starts_on']))) : '—' ?></td>
          <td><span class="status-pill <?= $statusColor[$co['status']] ?>"><?= View::e(ucfirst($co['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$cohorts): ?><tr><td colspan="5" class="cap" style="padding:16px;text-align:center">No cohorts visible.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
