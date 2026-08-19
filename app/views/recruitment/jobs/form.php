<?php
/** @var array<string,mixed>|null $job @var array<int,array<string,mixed>> $departments
 *  @var array<int,array<string,mixed>> $categories @var array<int,array<string,mixed>> $centres @var array<int,int> $selectedCentres */
$isEdit = $job !== null;
$statusColor = ['draft' => 'neutral', 'published' => 'success', 'unpublished' => 'warning', 'closed' => 'error'];
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=recruitment.jobs" style="color:var(--text-3)">Jobs</a> / <?= $isEdit ? View::e($job['title']) : 'New' ?></span>
    <h1><?= $isEdit ? View::e($job['title']) : 'New Job Posting' ?>
      <?php if ($isEdit): ?><span class="status-pill <?= $statusColor[$job['status']] ?>" style="margin-left:8px"><?= View::e(JobPosting::STATUSES[$job['status']]) ?></span><?php endif; ?>
    </h1>
  </div>
  <?php if ($isEdit): ?>
    <div style="display:flex;gap:8px">
      <a class="btn sm" href="app.php?r=recruitment.jobs.questions&id=<?= $job['id'] ?>">Questions</a>
      <?php if ($job['status'] === 'published'): ?>
        <a class="btn sm" href="<?= View::e(careers_url('app.php?r=jobs.show&slug=' . urlencode($job['slug']))) ?>" target="_blank" rel="noopener">View Live</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php if ($isEdit): ?>
<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>Status</h3></div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if ($job['status'] !== 'published'): ?>
      <form method="post" action="app.php?r=recruitment.jobs.publish"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= $job['id'] ?>"><button type="submit" class="btn primary">Publish</button></form>
    <?php else: ?>
      <form method="post" action="app.php?r=recruitment.jobs.unpublish"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= $job['id'] ?>"><button type="submit" class="btn">Unpublish</button></form>
    <?php endif; ?>
    <?php if ($job['status'] !== 'closed'): ?>
      <form method="post" action="app.php?r=recruitment.jobs.close" onsubmit="return confirm('Close this posting? No further applications will be accepted.')"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= $job['id'] ?>"><button type="submit" class="btn">Close</button></form>
    <?php endif; ?>
    <form method="post" action="app.php?r=recruitment.jobs.duplicate"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= $job['id'] ?>"><button type="submit" class="btn">Duplicate</button></form>
  </div>
</div>
<?php endif; ?>

<form method="post" action="app.php?r=recruitment.jobs.<?= $isEdit ? 'update' : 'store' ?>">
  <?= Csrf::field() ?>
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $job['id'] ?>"><?php endif; ?>

  <div class="card" style="margin-bottom:16px">
    <div class="chead"><h3>Details</h3></div>
    <div class="field"><label>Job title</label><input type="text" name="title" value="<?= View::e($job['title'] ?? '') ?>" required></div>
    <div class="field-row">
      <div class="field">
        <label>Department</label>
        <select name="department_id">
          <option value="">— None —</option>
          <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>" <?= (int) ($job['department_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>><?= View::e($d['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Job category</label>
        <select name="category_id">
          <option value="">— None —</option>
          <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (int) ($job['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field-row">
      <div class="field">
        <label>Employment type</label>
        <select name="employment_type">
          <?php foreach (JobPosting::EMPLOYMENT_TYPES as $c => $l): ?><option value="<?= $c ?>" <?= ($job['employment_type'] ?? 'full_time') === $c ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Work mode</label>
        <select name="work_mode">
          <?php foreach (JobPosting::WORK_MODES as $c => $l): ?><option value="<?= $c ?>" <?= ($job['work_mode'] ?? 'onsite') === $c ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field-row">
      <div class="field">
        <label>Location type</label>
        <select name="location_type">
          <?php foreach (['centre' => 'Specific centre(s)', 'multiple_centres' => 'Multiple centres', 'remote' => 'Remote', 'head_office' => 'Head Office', 'other' => 'Other'] as $c => $l): ?>
            <option value="<?= $c ?>" <?= ($job['location_type'] ?? 'centre') === $c ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Location label (shown when not centre-based)</label><input type="text" name="location_label" value="<?= View::e($job['location_label'] ?? '') ?>"></div>
    </div>
    <div class="field">
      <label>Centres</label>
      <div style="display:flex;gap:14px;flex-wrap:wrap;padding:6px 0">
        <?php foreach ($centres as $c): ?>
          <label style="display:flex;align-items:center;gap:6px;font-weight:400"><input type="checkbox" name="centre_ids[]" value="<?= $c['id'] ?>" <?= in_array((int) $c['id'], $selectedCentres, true) ? 'checked' : '' ?> style="width:auto"><?= View::e($c['name']) ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field"><label>Application deadline</label><input type="datetime-local" name="application_deadline" value="<?= !empty($job['application_deadline']) ? date('Y-m-d\TH:i', strtotime($job['application_deadline'])) : '' ?>"></div>
  </div>

  <div class="card" style="margin-bottom:16px">
    <div class="chead"><h3>Description</h3></div>
    <div class="field"><label>Summary</label><textarea name="summary" rows="3"><?= View::e($job['summary'] ?? '') ?></textarea></div>
    <div class="field"><label>Responsibilities (one per line)</label><textarea name="responsibilities" rows="4"><?= View::e($job['responsibilities'] ?? '') ?></textarea></div>
    <div class="field"><label>Requirements (one per line)</label><textarea name="requirements" rows="4"><?= View::e($job['requirements'] ?? '') ?></textarea></div>
    <div class="field"><label>Qualifications</label><textarea name="qualifications" rows="2"><?= View::e($job['qualifications'] ?? '') ?></textarea></div>
    <div class="field"><label>Skills</label><textarea name="skills" rows="2"><?= View::e($job['skills'] ?? '') ?></textarea></div>
    <div class="field"><label>Experience requirements</label><textarea name="experience_requirements" rows="2"><?= View::e($job['experience_requirements'] ?? '') ?></textarea></div>
    <div class="field"><label>Benefits (optional)</label><textarea name="benefits" rows="2"><?= View::e($job['benefits'] ?? '') ?></textarea></div>
  </div>

  <button type="submit" class="btn primary"><?= $isEdit ? 'Save Changes' : 'Create Draft' ?></button>
</form>
