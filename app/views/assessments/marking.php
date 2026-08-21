<?php /** @var array $attempts */ ?>
<div class="topbar">
  <div>
    <h1>Assessment Marking</h1>
    <p><?= count($attempts) ?> attempt(s) with written answers awaiting a mark.</p>
  </div>
  <a class="btn sm" href="app.php?r=grading">Assignment grading</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Candidate</th><th>Assessment</th><th>Course</th><th>Submitted</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($attempts as $a): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($a['student_name'] ?: '—') ?></span></td>
          <td><?= View::e($a['assessment_title']) ?> <span class="cap">#<?= (int) $a['attempt_no'] ?></span></td>
          <td><?= View::e($a['course_title']) ?></td>
          <td class="cap"><?= $a['submitted_at'] ? View::e(date('d M Y H:i', strtotime((string) $a['submitted_at']))) : '—' ?></td>
          <td><a class="btn sm primary" href="app.php?r=assessments.mark&id=<?= (int) $a['id'] ?>">Mark</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$attempts): ?><tr><td colspan="5" class="cap" style="padding:16px;text-align:center">Nothing awaiting a mark.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
