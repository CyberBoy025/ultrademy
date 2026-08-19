<?php /** @var array $courses @var bool $entitled @var array $certificates @var array $results */ ?>
<div class="topbar">
  <div>
    <h1>My Learning</h1>
    <p>Courses from the programmes you're enrolled in.</p>
  </div>
  <?php if ($certificates): ?>
  <div class="actions"><a class="btn" href="app.php?r=learn.certificates">My Certificates</a></div>
  <?php endif; ?>
</div>

<?php if (!$entitled): ?>
<div class="card" style="margin-bottom:20px;border-color:var(--warning)">
  <p class="cap">
    Online learning isn't included in your current package, so lesson content is locked.
    <a href="app.php?r=subscription" style="color:var(--brand-cyan-text);font-weight:600">View packages</a>.
  </p>
</div>
<?php endif; ?>

<?php if (!$courses): ?>
  <div class="empty-card">
    <b>No courses yet</b>
    <p>Courses appear here once you're enrolled in a programme that includes them.</p>
  </div>
<?php else: ?>
<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px">
  <?php foreach ($courses as $c): ?>
  <div class="card" style="display:flex;flex-direction:column">
    <div class="chead"><h3><?= View::e($c['title']) ?></h3></div>
    <p class="cap" style="margin-bottom:12px"><?= View::e($c['programme_title']) ?></p>
    <p class="cap" style="flex:1;margin-bottom:12px"><?= View::e($c['description'] ?: '') ?></p>
    <div style="display:flex;align-items:baseline;gap:6px"><span class="pct" style="font-size:22px"><?= (int) $c['percent'] ?>%</span><span class="cap">complete</span></div>
    <div class="bar" style="margin-bottom:14px"><span style="width:<?= (int) $c['percent'] ?>%"></span></div>
    <a class="btn primary" href="app.php?r=learn.course&id=<?= $c['id'] ?>" style="width:100%;justify-content:center">
      <?= (int) $c['percent'] > 0 ? 'Continue' : 'Start' ?>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($results): ?>
<h2 class="sec-title">My Results</h2>
<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Assignment</th><th>Course</th><th>Submitted</th><th>Score</th><th>Feedback</th></tr></thead>
      <tbody>
        <?php foreach ($results as $r): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($r['assignment_title']) ?></span></td>
          <td><?= View::e($r['course_title']) ?></td>
          <td class="cap"><?= View::e(date('d M Y', strtotime($r['submitted_at']))) ?></td>
          <td>
            <?php if ($r['status'] === 'graded'): ?>
              <span class="status-pill success"><?= (int) $r['score'] ?>/<?= (int) $r['max_score'] ?></span>
            <?php else: ?>
              <span class="status-pill warning">Awaiting grade</span>
            <?php endif; ?>
          </td>
          <td class="cap"><?= View::e($r['feedback'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
