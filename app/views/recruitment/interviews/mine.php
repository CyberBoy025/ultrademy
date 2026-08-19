<?php /** @var array<int,array<string,mixed>> $interviews */ ?>
<div class="topbar"><div><h1>My Interviews</h1><p>Interviews you're on the panel for.</p></div></div>

<div class="card">
  <?php if (!$interviews): ?>
    <p class="cap" style="padding:16px;text-align:center">No interviews assigned to you.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Candidate Ref.</th><th>Position</th><th>When</th><th>Type</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($interviews as $iv): ?>
        <tr>
          <td><?= View::e($iv['reference']) ?></td>
          <td><?= View::e($iv['job_title']) ?></td>
          <td><?= $iv['scheduled_at'] ? View::e(date('d M Y H:i', strtotime($iv['scheduled_at']))) : 'Not yet scheduled' ?></td>
          <td><?= View::e(Interview::TYPES[$iv['type']] ?? $iv['type']) ?></td>
          <td><a class="btn sm" href="app.php?r=recruitment.interviews.feedback&id=<?= $iv['id'] ?>">Give Feedback</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
