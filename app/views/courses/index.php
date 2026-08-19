<?php /** @var array $courses @var bool $canManage */
$statusColor = ['draft' => 'neutral', 'published' => 'success', 'archived' => 'neutral'];
?>
<div class="topbar">
  <div>
    <h1>Courses</h1>
    <p>Teachable content. A course becomes reachable by students once it is published <em>and</em> linked to a programme.</p>
  </div>
</div>

<?php if ($canManage): ?>
<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>New Course</h3></div>
  <form method="post" action="app.php?r=courses.store">
    <?= Csrf::field() ?>
    <div class="field"><label>Title</label><input type="text" name="title" required></div>
    <div class="field"><label>Description</label><input type="text" name="description"></div>
    <div class="field-row">
      <div class="field"><label>Objectives</label><input type="text" name="objectives" placeholder="what a learner will be able to do"></div>
      <div class="field"><label>Prerequisites</label><input type="text" name="prerequisites"></div>
    </div>
    <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2);margin-bottom:14px">
      <input type="checkbox" name="standalone" style="width:auto"> Available standalone (by subscription, without a programme)
    </label>
    <button type="submit" class="btn primary">Create Draft</button>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Course</th><th>Modules</th><th>Lessons</th><th>Duration</th><th>Standalone</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($courses as $c): ?>
        <tr onclick="location='app.php?r=courses.show&id=<?= $c['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($c['title']) ?></span><span class="cell-sub"><?= View::e($c['description'] ?: '—') ?></span></td>
          <td><?= (int) $c['module_count'] ?></td>
          <td><?= (int) $c['lesson_count'] ?></td>
          <td><?= (int) $c['estimated_minutes'] ?> min</td>
          <td><?= (int) $c['standalone'] === 1 ? 'Yes' : 'No' ?></td>
          <td><span class="status-pill <?= $statusColor[$c['status']] ?>"><?= View::e(ucfirst($c['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$courses): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">No courses yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
