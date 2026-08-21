<?php /** @var array $assessment @var array $attempts */
$graded = array_filter($attempts, fn($a) => $a['status'] === 'graded');
$avg = $graded ? array_sum(array_map(fn($a) => (float) $a['score_percent'], $graded)) / count($graded) : null;
$passCount = count(array_filter($graded, fn($a) => (int) $a['passed'] === 1)); ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=assessments.edit&id=<?= (int) $assessment['id'] ?>" style="color:var(--text-3)"><?= View::e($assessment['title']) ?></a> / Results</span>
    <h1>Results</h1>
    <p><?= count($attempts) ?> attempt(s) · <?= View::e($assessment['course_title']) ?></p>
  </div>
</div>

<div class="row row-b" style="margin-bottom:20px">
  <div class="card">
    <div class="chead"><h3>Average</h3></div>
    <span class="pct"><?= $avg === null ? '—' : round($avg, 1) . '%' ?></span>
    <span class="cap">across <?= count($graded) ?> graded attempt(s)</span>
  </div>
  <div class="card">
    <div class="chead"><h3>Pass rate</h3></div>
    <span class="pct"><?= $graded ? round($passCount * 100 / count($graded)) . '%' : '—' ?></span>
    <span class="cap"><?= $passCount ?> of <?= count($graded) ?> at or above <?= (int) $assessment['pass_mark'] ?>%</span>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Candidate</th><th>Attempt</th><th>Submitted</th><th>Score</th><th>Outcome</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($attempts as $a): ?>
        <tr>
          <td>
            <span class="cell-main"><?= View::e($a['student_name'] ?: $a['email']) ?></span>
            <?php if ($a['student_no']): ?><span class="cap" style="display:block"><?= View::e($a['student_no']) ?></span><?php endif; ?>
          </td>
          <td class="cap">#<?= (int) $a['attempt_no'] ?></td>
          <td class="cap"><?= $a['submitted_at'] ? View::e(date('d M Y H:i', strtotime((string) $a['submitted_at']))) : '—' ?></td>
          <td>
            <?php if ($a['status'] === 'graded'): ?>
              <span class="cell-main"><?= rtrim(rtrim((string) $a['score_percent'], '0'), '.') ?>%</span>
              <span class="cap"><?= (int) $a['score_points'] ?>/<?= (int) $a['max_points'] ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if ($a['status'] === 'graded'): ?>
              <span class="status-pill <?= (int) $a['passed'] === 1 ? 'success' : 'error' ?>"><?= (int) $a['passed'] === 1 ? 'Passed' : 'Not passed' ?></span>
            <?php elseif ((int) $a['needs_manual_grade'] === 1): ?>
              <span class="status-pill warning">Awaiting marking</span>
            <?php else: ?>
              <span class="status-pill neutral"><?= View::e(ucfirst($a['status'])) ?></span>
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap">
            <?php if ((int) $a['needs_manual_grade'] === 1 && Auth::can('education.assessment.grade')): ?>
              <a class="btn sm primary" href="app.php?r=assessments.mark&id=<?= (int) $a['id'] ?>">Mark</a>
            <?php else: ?>
              <a class="btn sm" href="app.php?r=assessments.result&id=<?= (int) $a['id'] ?>">View</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$attempts): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">Nobody has sat this assessment yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
