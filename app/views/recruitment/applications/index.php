<?php
/** @var array<int,array<string,mixed>> $applications @var array<string,int> $counts
 *  @var array<int,array<string,mixed>> $jobs @var array<string,mixed> $filters */
$statusColor = [
    'submitted' => 'info', 'received' => 'info', 'under_review' => 'warning', 'shortlisted' => 'warning',
    'interview' => 'warning', 'assessment' => 'warning', 'final_review' => 'warning',
    'selected' => 'success', 'rejected' => 'error', 'withdrawn' => 'neutral', 'closed' => 'neutral',
];
$total = array_sum($counts);
?>
<div class="topbar">
  <div>
    <h1>Recruitment — Applications</h1>
    <p><?= (int) ($counts['submitted'] ?? 0) + (int) ($counts['received'] ?? 0) ?> new · <?= (int) ($counts['under_review'] ?? 0) + (int) ($counts['shortlisted'] ?? 0) ?> in review · <?= $total ?> total</p>
  </div>
</div>

<div class="filters">
  <?php foreach (['' => 'All'] + JobApplication::STATUS_LABELS as $val => $label): ?>
    <?php if ($val === 'draft') { continue; } ?>
    <a class="chip <?= ($filters['status'] ?? '') === $val ? 'active' : '' ?>" href="app.php?r=recruitment.applications<?= $val ? '&status=' . $val : '' ?><?= !empty($filters['job_posting_id']) ? '&job=' . $filters['job_posting_id'] : '' ?>">
      <?= $label ?><?= $val && isset($counts[$val]) ? ' (' . $counts[$val] . ')' : '' ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="card" style="margin-bottom:16px">
  <form method="get" action="app.php" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
    <input type="hidden" name="r" value="recruitment.applications">
    <?php if (!empty($filters['status'])): ?><input type="hidden" name="status" value="<?= View::e($filters['status']) ?>"><?php endif; ?>
    <div class="field" style="margin:0;min-width:240px">
      <label>Job posting</label>
      <select name="job" onchange="this.form.submit()">
        <option value="">All postings</option>
        <?php foreach ($jobs as $j): ?><option value="<?= $j['id'] ?>" <?= (int) ($filters['job_posting_id'] ?? 0) === (int) $j['id'] ? 'selected' : '' ?>><?= View::e($j['title']) ?></option><?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Applicant</th><th>Position</th><th>Submitted</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($applications as $a): ?>
        <tr onclick="location='app.php?r=recruitment.applications.show&id=<?= $a['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($a['reference']) ?></span></td>
          <td><span class="cell-main"><?= View::e($a['applicant_name'] ?: '—') ?></span><span class="cell-sub"><?= View::e($a['email']) ?></span></td>
          <td><?= View::e($a['job_title']) ?></td>
          <td><?= $a['submitted_at'] ? View::e(date('d M Y', strtotime($a['submitted_at']))) : '—' ?></td>
          <td><span class="status-pill <?= $statusColor[$a['status']] ?? 'neutral' ?>"><?= View::e(JobApplication::STATUS_LABELS[$a['status']] ?? $a['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$applications): ?><tr><td colspan="5" class="cap" style="padding:16px;text-align:center">Nothing in this view.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
