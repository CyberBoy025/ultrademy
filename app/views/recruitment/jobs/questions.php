<?php /** @var array<string,mixed> $job @var array<int,array<string,mixed>> $questions */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=recruitment.jobs" style="color:var(--text-3)">Jobs</a> / <a href="app.php?r=recruitment.jobs.edit&id=<?= $job['id'] ?>" style="color:var(--text-3)"><?= View::e($job['title']) ?></a> / Questions</span>
    <h1>Application Questions</h1>
    <p>Shown to every applicant during the apply flow, in order.</p>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <?php if (!$questions): ?>
    <p class="cap" style="padding:6px 0">No questions yet — applicants only go through documents and review.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>#</th><th>Question</th><th>Type</th><th>Required</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($questions as $i => $q): ?>
        <tr>
          <td class="cap"><?= $i + 1 ?></td>
          <td><?= View::e($q['label']) ?></td>
          <td><?= View::e(JobQuestion::TYPES[$q['type']] ?? $q['type']) ?></td>
          <td><?= $q['is_required'] ? 'Yes' : 'No' ?></td>
          <td>
            <form method="post" action="app.php?r=recruitment.jobs.questions.delete" onsubmit="return confirm('Remove this question?')">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $q['id'] ?>"><input type="hidden" name="job_posting_id" value="<?= $job['id'] ?>">
              <button type="submit" class="btn sm">Remove</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="chead"><h3>Add a Question</h3></div>
  <form method="post" action="app.php?r=recruitment.jobs.questions.store">
    <?= Csrf::field() ?><input type="hidden" name="job_posting_id" value="<?= $job['id'] ?>">
    <div class="field"><label>Question</label><input type="text" name="label" required></div>
    <div class="field-row">
      <div class="field">
        <label>Answer type</label>
        <select name="type">
          <?php foreach (JobQuestion::TYPES as $c => $l): ?><option value="<?= $c ?>"><?= $l ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="align-self:end"><label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="is_required" checked style="width:auto"> Required</label></div>
    </div>
    <div class="field"><label>Options (multiple choice only — one per line)</label><textarea name="options" rows="3"></textarea></div>
    <button type="submit" class="btn primary">Add Question</button>
  </form>
</div>
