<?php
/** @var array $course @var array $outline @var bool $canManage @var array $assignments
 *  @var array $programmes @var array $allProgrammes */
$statusColor = ['draft' => 'neutral', 'published' => 'success', 'archived' => 'neutral'];
$linkedIds = array_map('intval', array_column($programmes, 'id'));
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=courses" style="color:var(--text-3)">Courses</a> / <?= View::e($course['title']) ?></span>
    <h1><?= View::e($course['title']) ?> <span class="status-pill <?= $statusColor[$course['status']] ?>" style="margin-left:8px"><?= View::e(ucfirst($course['status'])) ?></span></h1>
    <p><?= count($outline) ?> module(s) · <?= (int) $course['estimated_minutes'] ?> min estimated</p>
  </div>
  <div class="actions"><a class="btn" href="app.php?r=learn.course&id=<?= $course['id'] ?>">Preview as Learner</a></div>
</div>

<?php if (!$programmes): ?>
<div class="card" style="margin-bottom:20px;border-color:var(--warning)">
  <p class="cap"><strong>Not linked to any programme.</strong> Students reach a course through their enrolment, so until this is linked below, nobody can open it.</p>
</div>
<?php endif; ?>

<?php if ($canManage): ?>
<div class="row row-b">
  <div class="card">
    <div class="chead"><h3>Details</h3></div>
    <form method="post" action="app.php?r=courses.update">
      <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $course['id'] ?>">
      <div class="field"><label>Title</label><input type="text" name="title" value="<?= View::e($course['title']) ?>" required></div>
      <div class="field"><label>Description</label><input type="text" name="description" value="<?= View::e($course['description']) ?>"></div>
      <div class="field"><label>Objectives</label><input type="text" name="objectives" value="<?= View::e($course['objectives']) ?>"></div>
      <div class="field"><label>Prerequisites</label><input type="text" name="prerequisites" value="<?= View::e($course['prerequisites']) ?>"></div>
      <div class="field"><label>Status</label>
        <select name="status">
          <?php foreach (['draft', 'published', 'archived'] as $s): ?>
          <option value="<?= $s ?>" <?= $course['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2);margin-bottom:14px">
        <input type="checkbox" name="standalone" style="width:auto" <?= (int) $course['standalone'] === 1 ? 'checked' : '' ?>> Available standalone
      </label>
      <button type="submit" class="btn primary">Save</button>
    </form>
  </div>

  <div class="card">
    <div class="chead"><h3>Programmes</h3></div>
    <form method="post" action="app.php?r=courses.link">
      <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $course['id'] ?>">
      <div class="field">
        <label>Included in</label>
        <select name="programme_ids[]" multiple style="height:150px">
          <?php foreach ($allProgrammes as $p): ?>
          <option value="<?= $p['id'] ?>" <?= in_array((int) $p['id'], $linkedIds, true) ? 'selected' : '' ?>><?= View::e($p['title']) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="cap">Ctrl/Cmd-click to select several.</span>
      </div>
      <button type="submit" class="btn primary">Save Links</button>
    </form>
  </div>
</div>
<?php endif; ?>

<h2 class="sec-title">Outline</h2>
<?php foreach ($outline as $m): ?>
<div class="card" style="margin-bottom:14px">
  <div class="chead">
    <h3><?= View::e($m['title']) ?></h3>
    <?php if ($canManage): ?>
    <form method="post" action="app.php?r=modules.delete" onsubmit="return confirm('Delete this module and all its lessons?')">
      <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>">
      <button type="submit" class="btn sm">Delete Module</button>
    </form>
    <?php endif; ?>
  </div>
  <?php if ($m['summary']): ?><p class="cap" style="margin-bottom:10px"><?= View::e($m['summary']) ?></p><?php endif; ?>

  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Lesson</th><th>Type</th><th>Duration</th><th>Materials</th><th>Preview</th><?php if ($canManage): ?><th></th><?php endif; ?></tr></thead>
      <tbody>
        <?php foreach ($m['lessons'] as $l): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($l['title']) ?></span></td>
          <td><span class="status-pill neutral"><?= View::e($l['content_type']) ?></span></td>
          <td><?= (int) $l['duration_minutes'] ?> min</td>
          <td><?= count($l['materials']) ?></td>
          <td><?= (int) $l['is_preview'] === 1 ? '<span class="status-pill info">Preview</span>' : '—' ?></td>
          <?php if ($canManage): ?>
          <td><a class="btn sm" href="app.php?r=lessons.edit&id=<?= $l['id'] ?>">Edit</a></td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (!$m['lessons']): ?><tr><td colspan="<?= $canManage ? 6 : 5 ?>" class="cap" style="padding:12px;text-align:center">No lessons in this module yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($canManage): ?>
  <form method="post" action="app.php?r=lessons.store" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
    <?= Csrf::field() ?><input type="hidden" name="module_id" value="<?= $m['id'] ?>">
    <div class="field-row">
      <div class="field"><label>New lesson title</label><input type="text" name="title" required></div>
      <div class="field"><label>Type</label>
        <select name="content_type">
          <option value="text">Text</option><option value="video">Video</option>
          <option value="document">Document</option><option value="link">Link</option>
        </select>
      </div>
    </div>
    <div class="field-row">
      <div class="field"><label>Duration (min)</label><input type="number" name="duration_minutes" min="0" value="10"></div>
      <div class="field" style="justify-content:flex-end">
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2)">
          <input type="checkbox" name="is_preview" style="width:auto"> Free preview
        </label>
      </div>
    </div>
    <button type="submit" class="btn sm primary">Add Lesson</button>
  </form>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if ($canManage): ?>
<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>New Module</h3></div>
  <form method="post" action="app.php?r=modules.store">
    <?= Csrf::field() ?><input type="hidden" name="course_id" value="<?= $course['id'] ?>">
    <div class="field-row">
      <div class="field"><label>Title</label><input type="text" name="title" required></div>
      <div class="field"><label>Summary</label><input type="text" name="summary"></div>
    </div>
    <button type="submit" class="btn primary">Add Module</button>
  </form>
</div>
<?php endif; ?>

<?php if (Auth::can('education.assessment.manage')): ?>
<h2 class="sec-title">Assessments</h2>
<div class="card" style="margin-bottom:14px">
  <p class="cap" style="margin-bottom:12px">
    Quizzes and examinations for this course — questions, timing, attempt limits and marking.
  </p>
  <a class="btn primary btn-sm" href="app.php?r=assessments.manage&course=<?= (int) $course['id'] ?>">Manage assessments</a>
</div>
<?php endif; ?>

<h2 class="sec-title">Assignments</h2>
<div class="card" style="margin-bottom:14px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Title</th><th>Due</th><th>Max</th><th>Submissions</th><th>Awaiting Grade</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($assignments as $a): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($a['title']) ?></span></td>
          <td><?= $a['due_at'] ? View::e(date('d M Y H:i', strtotime($a['due_at']))) : '—' ?></td>
          <td><?= (int) $a['max_score'] ?></td>
          <td><?= (int) $a['submission_count'] ?></td>
          <td><?= (int) $a['ungraded_count'] ?></td>
          <td>
            <?php if ($canManage): ?>
            <form method="post" action="app.php?r=assignments.status">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $a['id'] ?>">
              <select name="status" onchange="this.form.submit()" class="btn sm">
                <?php foreach (['draft', 'published', 'closed'] as $s): ?>
                <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
            <?php else: ?><span class="status-pill neutral"><?= View::e(ucfirst($a['status'])) ?></span><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$assignments): ?><tr><td colspan="6" class="cap" style="padding:12px;text-align:center">No assignments yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canManage): ?>
<div class="card">
  <div class="chead"><h3>New Assignment</h3></div>
  <form method="post" action="app.php?r=assignments.store">
    <?= Csrf::field() ?><input type="hidden" name="course_id" value="<?= $course['id'] ?>">
    <div class="field"><label>Title</label><input type="text" name="title" required></div>
    <div class="field"><label>Instructions</label><input type="text" name="instructions"></div>
    <div class="field-row">
      <div class="field"><label>Due</label><input type="datetime-local" name="due_at"></div>
      <div class="field"><label>Max score</label><input type="number" name="max_score" min="1" value="100"></div>
    </div>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:14px">
      <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2)"><input type="checkbox" name="allows_text" style="width:auto" checked> Text submission</label>
      <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2)"><input type="checkbox" name="allows_file" style="width:auto" checked> File submission</label>
      <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2)"><input type="checkbox" name="allows_resubmission" style="width:auto"> Allow resubmission</label>
    </div>
    <button type="submit" class="btn primary">Publish Assignment</button>
  </form>
</div>
<?php endif; ?>
