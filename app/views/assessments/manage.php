<?php /** @var array $course @var array $assessments @var array $modules */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=courses" style="color:var(--text-3)">Courses</a> / <a href="app.php?r=courses.show&id=<?= (int) $course['id'] ?>" style="color:var(--text-3)"><?= View::e($course['title']) ?></a> / Assessments</span>
    <h1>Assessments</h1>
    <p><?= count($assessments) ?> assessment(s) on this course.</p>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Title</th><th>Type</th><th>Questions</th><th>Marks</th><th>Attempts</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($assessments as $a): ?>
        <tr>
          <td>
            <span class="cell-main"><?= View::e($a['title']) ?></span>
            <?php if ((int) $a['awaiting_marking'] > 0): ?>
              <span class="status-pill warning" style="margin-left:6px"><?= (int) $a['awaiting_marking'] ?> to mark</span>
            <?php endif; ?>
          </td>
          <td class="cap"><?= View::e(Assessment::TYPES[$a['type']] ?? $a['type']) ?></td>
          <td class="cap"><?= (int) $a['question_count'] ?></td>
          <td class="cap"><?= (int) $a['max_points'] ?></td>
          <td class="cap"><?= (int) $a['attempt_count'] ?></td>
          <td>
            <span class="status-pill <?= $a['status'] === 'published' ? 'success' : ($a['status'] === 'closed' ? 'neutral' : 'warning') ?>"><?= View::e(ucfirst($a['status'])) ?></span>
          </td>
          <td style="white-space:nowrap">
            <a class="btn sm" href="app.php?r=assessments.edit&id=<?= (int) $a['id'] ?>">Edit</a>
            <?php if ((int) $a['attempt_count'] > 0): ?>
              <a class="btn sm" href="app.php?r=assessments.attempts&id=<?= (int) $a['id'] ?>">Results</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$assessments): ?>
          <tr><td colspan="7" class="cap" style="padding:16px;text-align:center">No assessments yet. Create the first one below.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="chead"><h3>New assessment</h3></div>
  <form method="post" action="app.php?r=assessments.store">
    <?= Csrf::field() ?><input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
    <div class="row row-b">
      <div class="field"><label>Title</label><input type="text" name="title" required maxlength="200" placeholder="End of Module 1 Quiz"></div>
      <div class="field">
        <label>Type</label>
        <select name="type">
          <?php foreach (Assessment::TYPES as $k => $label): ?><option value="<?= $k ?>"><?= View::e($label) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label>Instructions <span class="cap">(shown before the candidate starts)</span></label>
      <textarea name="instructions" rows="3" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"></textarea>
    </div>
    <div class="row row-b">
      <div class="field">
        <label>Module <span class="cap">(optional)</span></label>
        <select name="module_id">
          <option value="">Whole course</option>
          <?php foreach ($modules as $m): ?><option value="<?= (int) $m['id'] ?>"><?= View::e($m['title']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Time limit (minutes) <span class="cap">— blank for untimed</span></label><input type="number" name="duration_minutes" min="1" max="600"></div>
    </div>
    <div class="row row-b">
      <div class="field"><label>Opens <span class="cap">(optional)</span></label><input type="datetime-local" name="opens_at"></div>
      <div class="field"><label>Closes <span class="cap">(optional)</span></label><input type="datetime-local" name="closes_at"></div>
    </div>
    <div class="row row-b">
      <div class="field"><label>Attempts allowed <span class="cap">— 0 for unlimited</span></label><input type="number" name="max_attempts" min="0" max="20" value="1"></div>
      <div class="field"><label>Pass mark (%)</label><input type="number" name="pass_mark" min="0" max="100" value="50"></div>
    </div>
    <div class="row row-b">
      <div class="field">
        <label>Show results</label>
        <select name="show_results">
          <option value="immediately">Immediately after submitting</option>
          <option value="after_close">Only after the assessment closes</option>
          <option value="never">Never show the candidate their mark</option>
        </select>
      </div>
      <div class="field" style="justify-content:flex-end">
        <label style="display:flex;align-items:center;gap:8px;font-weight:500">
          <input type="checkbox" name="shuffle_questions" value="1" style="width:auto"> Shuffle question order
        </label>
      </div>
    </div>
    <button type="submit" class="btn primary">Create assessment</button>
  </form>
</div>
