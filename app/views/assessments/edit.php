<?php /** @var array $assessment @var array $questions @var array $modules */
$blocker = Assessment::publishBlocker((int) $assessment['id']); ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=assessments.manage&course=<?= (int) $assessment['course_id'] ?>" style="color:var(--text-3)">Assessments</a> / <?= View::e($assessment['title']) ?></span>
    <h1><?= View::e($assessment['title']) ?></h1>
    <p><?= View::e($assessment['course_title']) ?> · <?= (int) $assessment['question_count'] ?> question(s) · <?= (int) $assessment['max_points'] ?> mark(s) available</p>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <span class="status-pill <?= $assessment['status'] === 'published' ? 'success' : ($assessment['status'] === 'closed' ? 'neutral' : 'warning') ?>"><?= View::e(ucfirst($assessment['status'])) ?></span>
    <?php if ($assessment['status'] !== 'published'): ?>
      <form method="post" action="app.php?r=assessments.status" style="display:inline">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $assessment['id'] ?>"><input type="hidden" name="status" value="published">
        <button type="submit" class="btn sm primary" <?= $blocker ? 'disabled title="' . View::e($blocker) . '"' : '' ?>>Publish</button>
      </form>
    <?php else: ?>
      <form method="post" action="app.php?r=assessments.status" style="display:inline">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $assessment['id'] ?>"><input type="hidden" name="status" value="closed">
        <button type="submit" class="btn sm">Close</button>
      </form>
    <?php endif; ?>
    <a class="btn sm" href="app.php?r=assessments.attempts&id=<?= (int) $assessment['id'] ?>">Results</a>
  </div>
</div>

<?php if ($blocker && $assessment['status'] !== 'published'): ?>
  <div class="card" style="margin-bottom:20px;border-left:3px solid var(--warning)">
    <p class="cap" style="margin:0"><strong>Not ready to publish.</strong> <?= View::e($blocker) ?></p>
  </div>
<?php endif; ?>

<h2 class="sec-title">Questions</h2>
<?php foreach ($questions as $i => $q): ?>
<div class="card" style="margin-bottom:12px">
  <div class="chead">
    <h3><?= $i + 1 ?>. <?= View::e(mb_strimwidth(strip_tags($q['prompt']), 0, 90, '…')) ?></h3>
    <div style="display:flex;gap:8px;align-items:center">
      <span class="status-pill neutral"><?= View::e(Assessment::QUESTION_TYPES[$q['type']] ?? $q['type']) ?></span>
      <span class="cap"><?= (int) $q['points'] ?> mark<?= (int) $q['points'] === 1 ? '' : 's' ?></span>
      <form method="post" action="app.php?r=assessments.question.delete" style="display:inline"
            onsubmit="return confirm('Delete this question? Answers already given to it are deleted too.')">
        <?= Csrf::field() ?>
        <input type="hidden" name="question_id" value="<?= (int) $q['id'] ?>">
        <input type="hidden" name="assessment_id" value="<?= (int) $assessment['id'] ?>">
        <button type="submit" class="btn sm">Delete</button>
      </form>
    </div>
  </div>
  <p style="font-size:13px;color:var(--text-2);margin-bottom:10px"><?= nl2br(View::e($q['prompt'])) ?></p>

  <?php if ($q['options']): ?>
    <div class="queue">
      <?php foreach ($q['options'] as $o): ?>
      <div class="queue-item">
        <div class="queue-ico" style="<?= (int) $o['is_correct'] === 1 ? 'background:var(--cyan-50);color:var(--brand-cyan-text)' : '' ?>">
          <?php if ((int) $o['is_correct'] === 1): ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
          <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="9"/></svg>
          <?php endif; ?>
        </div>
        <div class="queue-t"><h4><?= View::e($o['label']) ?></h4><p><?= (int) $o['is_correct'] === 1 ? 'Correct answer' : 'Distractor' ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php elseif ($q['type'] === 'short_text'): ?>
    <p class="cap">Accepted answer(s): <strong><?= View::e((string) $q['expected_answer']) ?></strong> — compared case-insensitively; separate alternatives with <code>|</code>.</p>
  <?php elseif ($q['type'] === 'essay'): ?>
    <p class="cap">Marked by hand. Attempts containing this question wait in the marking queue.</p>
  <?php endif; ?>

  <?php if ($q['explanation']): ?>
    <p class="cap" style="margin-top:8px">Explanation shown in review: <?= View::e($q['explanation']) ?></p>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (!$questions): ?>
  <div class="card" style="margin-bottom:20px"><p class="cap" style="margin:0">No questions yet. Add the first one below.</p></div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>Add a question</h3></div>
  <form method="post" action="app.php?r=assessments.question.store" id="qform">
    <?= Csrf::field() ?><input type="hidden" name="assessment_id" value="<?= (int) $assessment['id'] ?>">
    <div class="row row-b">
      <div class="field">
        <label>Type</label>
        <select name="type" id="qtype">
          <?php foreach (Assessment::QUESTION_TYPES as $k => $label): ?><option value="<?= $k ?>"><?= View::e($label) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Marks</label><input type="number" name="points" min="0" max="100" value="1"></div>
    </div>
    <div class="field">
      <label>Question</label>
      <textarea name="prompt" rows="3" required style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"></textarea>
    </div>

    <div id="opts" style="display:none">
      <p class="cap" style="margin-bottom:8px">Tick every correct option. Leave unused rows blank.</p>
      <?php for ($i = 0; $i < 5; $i++): ?>
      <div class="field" style="flex-direction:row;align-items:center;gap:10px">
        <input type="checkbox" name="option_correct[]" value="<?= $i ?>" style="width:auto;flex:none" aria-label="Option <?= $i + 1 ?> is correct">
        <input type="text" name="option_label[]" maxlength="500" placeholder="Option <?= $i + 1 ?>">
      </div>
      <?php endfor; ?>
    </div>

    <div id="tf" style="display:none">
      <div class="field">
        <label>Correct answer</label>
        <select name="expected_answer_tf" id="tfsel"><option value="true">True</option><option value="false">False</option></select>
      </div>
    </div>

    <div id="short" style="display:none">
      <div class="field">
        <label>Accepted answer(s)</label>
        <input type="text" name="expected_answer_text" id="shorttxt" maxlength="500" placeholder="HTTP|Hypertext Transfer Protocol">
        <span class="cap">Case and surrounding spaces are ignored. Separate alternatives with a pipe.</span>
      </div>
    </div>

    <input type="hidden" name="expected_answer" id="expected">

    <div class="field">
      <label>Explanation <span class="cap">(optional — shown when the candidate reviews their result)</span></label>
      <input type="text" name="explanation" maxlength="500">
    </div>
    <button type="submit" class="btn primary">Add question</button>
  </form>
</div>

<div class="card">
  <div class="chead"><h3>Settings</h3></div>
  <form method="post" action="app.php?r=assessments.update">
    <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $assessment['id'] ?>">
    <div class="row row-b">
      <div class="field"><label>Title</label><input type="text" name="title" required maxlength="200" value="<?= View::e($assessment['title']) ?>"></div>
      <div class="field">
        <label>Type</label>
        <select name="type">
          <?php foreach (Assessment::TYPES as $k => $label): ?>
            <option value="<?= $k ?>" <?= $assessment['type'] === $k ? 'selected' : '' ?>><?= View::e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label>Instructions</label>
      <textarea name="instructions" rows="3" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"><?= View::e((string) $assessment['instructions']) ?></textarea>
    </div>
    <div class="row row-b">
      <div class="field">
        <label>Module</label>
        <select name="module_id">
          <option value="">Whole course</option>
          <?php foreach ($modules as $m): ?>
            <option value="<?= (int) $m['id'] ?>" <?= (int) $assessment['module_id'] === (int) $m['id'] ? 'selected' : '' ?>><?= View::e($m['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Time limit (minutes)</label><input type="number" name="duration_minutes" min="1" max="600" value="<?= $assessment['duration_minutes'] !== null ? (int) $assessment['duration_minutes'] : '' ?>"></div>
    </div>
    <div class="row row-b">
      <div class="field"><label>Opens</label><input type="datetime-local" name="opens_at" value="<?= $assessment['opens_at'] ? View::e(date('Y-m-d\TH:i', strtotime((string) $assessment['opens_at']))) : '' ?>"></div>
      <div class="field"><label>Closes</label><input type="datetime-local" name="closes_at" value="<?= $assessment['closes_at'] ? View::e(date('Y-m-d\TH:i', strtotime((string) $assessment['closes_at']))) : '' ?>"></div>
    </div>
    <div class="row row-b">
      <div class="field"><label>Attempts allowed</label><input type="number" name="max_attempts" min="0" max="20" value="<?= (int) $assessment['max_attempts'] ?>"></div>
      <div class="field"><label>Pass mark (%)</label><input type="number" name="pass_mark" min="0" max="100" value="<?= (int) $assessment['pass_mark'] ?>"></div>
    </div>
    <div class="row row-b">
      <div class="field">
        <label>Show results</label>
        <select name="show_results">
          <?php foreach (['immediately' => 'Immediately after submitting', 'after_close' => 'Only after the assessment closes', 'never' => 'Never show the candidate their mark'] as $k => $label): ?>
            <option value="<?= $k ?>" <?= $assessment['show_results'] === $k ? 'selected' : '' ?>><?= View::e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="justify-content:flex-end">
        <label style="display:flex;align-items:center;gap:8px;font-weight:500">
          <input type="checkbox" name="shuffle_questions" value="1" style="width:auto" <?= (int) $assessment['shuffle_questions'] === 1 ? 'checked' : '' ?>> Shuffle question order
        </label>
      </div>
    </div>
    <button type="submit" class="btn primary">Save settings</button>
  </form>
</div>

<script>
(function () {
  var type = document.getElementById('qtype'),
      opts = document.getElementById('opts'),
      tf = document.getElementById('tf'),
      short = document.getElementById('short'),
      expected = document.getElementById('expected'),
      form = document.getElementById('qform');

  function sync() {
    var v = type.value;
    opts.style.display  = (v === 'single_choice' || v === 'multi_choice') ? '' : 'none';
    tf.style.display    = (v === 'true_false') ? '' : 'none';
    short.style.display = (v === 'short_text') ? '' : 'none';
  }
  type.addEventListener('change', sync);
  sync();

  // expected_answer carries different things per type; resolve it at submit so the
  // server receives one field rather than three it has to disambiguate.
  form.addEventListener('submit', function () {
    var v = type.value;
    expected.value = v === 'true_false'  ? document.getElementById('tfsel').value
                   : v === 'short_text'  ? document.getElementById('shorttxt').value
                   : '';
  });
})();
</script>
