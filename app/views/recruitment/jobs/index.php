<?php
/** @var array<int,array<string,mixed>> $jobs @var string $status */
$statusColor = ['draft' => 'neutral', 'published' => 'success', 'unpublished' => 'warning', 'closed' => 'error'];
?>
<div class="topbar">
  <div>
    <h1>Recruitment — Jobs</h1>
    <p><?= count($jobs) ?> posting<?= count($jobs) === 1 ? '' : 's' ?></p>
  </div>
  <a class="btn primary" href="app.php?r=recruitment.jobs.create">New Job Posting</a>
</div>

<div class="filters">
  <?php foreach (['' => 'All'] + JobPosting::STATUSES as $val => $label): ?>
    <a class="chip <?= $status === $val ? 'active' : '' ?>" href="app.php?r=recruitment.jobs<?= $val ? '&status=' . $val : '' ?>"><?= $label ?></a>
  <?php endforeach; ?>
  <span style="flex:1"></span>
  <a class="chip" href="app.php?r=recruitment.departments">Departments</a>
  <a class="chip" href="app.php?r=recruitment.categories">Job Categories</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Title</th><th>Department</th><th>Type</th><th>Deadline</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($jobs as $j): ?>
        <tr onclick="location='app.php?r=recruitment.jobs.edit&id=<?= $j['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($j['title']) ?></span><span class="cell-sub"><?= View::e($j['category_name'] ?? '') ?></span></td>
          <td><?= View::e($j['department_name'] ?? '—') ?></td>
          <td><?= View::e(JobPosting::EMPLOYMENT_TYPES[$j['employment_type']] ?? $j['employment_type']) ?></td>
          <td><?= $j['application_deadline'] ? View::e(date('d M Y', strtotime($j['application_deadline']))) : '—' ?></td>
          <td><span class="status-pill <?= $statusColor[$j['status']] ?>"><?= View::e(JobPosting::STATUSES[$j['status']]) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$jobs): ?><tr><td colspan="5" class="cap" style="padding:16px;text-align:center">No job postings yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
