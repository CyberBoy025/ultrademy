<?php /** @var array $submissions @var bool $scoped */ ?>
<div class="topbar">
  <div>
    <h1>Grading</h1>
    <p><?= count($submissions) ?> submission(s) awaiting a grade<?= $scoped ? ' in the courses you teach' : '' ?>.</p>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Student</th><th>Assignment</th><th>Course</th><th>Submitted</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($submissions as $s): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($s['student_name'] ?: '—') ?></span></td>
          <td><?= View::e($s['assignment_title']) ?></td>
          <td><?= View::e($s['course_title']) ?></td>
          <td class="cap"><?= View::e(date('d M Y H:i', strtotime($s['submitted_at']))) ?></td>
          <td><a class="btn sm primary" href="app.php?r=grading.show&id=<?= $s['id'] ?>">Grade</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$submissions): ?><tr><td colspan="5" class="cap" style="padding:16px;text-align:center">Nothing awaiting a grade.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
