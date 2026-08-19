<?php
/** @var array<int,array<string,mixed>> $jobs @var array<string,mixed> $filters
 *  @var array<int,array<string,mixed>> $departments @var array<int,array<string,mixed>> $categories
 *  @var array<int,array<string,mixed>> $centres @var array<int,int> $savedIds */
?>
<div class="cw-narrow cw-listing-head">
  <h1>Open Positions</h1>
  <p style="color:var(--cw-ink-soft);margin-top:8px">Browse current vacancies across every UltrAdemy department and location.</p>
  <form class="cw-search-form" method="get" action="app.php">
    <input type="hidden" name="r" value="jobs">
    <input class="cw-search-input" type="text" name="q" placeholder="Search by title or keyword…" value="<?= View::e($filters['keyword']) ?>">
    <button class="cw-btn cw-btn-primary" type="submit">Search</button>
  </form>
</div>

<div class="cw-wrap cw-listing-layout">
  <aside class="cw-filters">
    <form method="get" action="app.php" id="filterForm">
      <input type="hidden" name="r" value="jobs">
      <input type="hidden" name="q" value="<?= View::e($filters['keyword']) ?>">

      <div class="cw-filter-group" style="margin-bottom:16px">
        <label class="cw-filter-label" for="f-dept">Department</label>
        <select id="f-dept" name="department_id" onchange="document.getElementById('filterForm').submit()">
          <option value="">All departments</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= (int) $d['id'] ?>" <?= (string) $filters['department_id'] === (string) $d['id'] ? 'selected' : '' ?>><?= View::e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="cw-filter-group" style="margin-bottom:16px">
        <label class="cw-filter-label" for="f-cat">Category</label>
        <select id="f-cat" name="category_id" onchange="document.getElementById('filterForm').submit()">
          <option value="">All categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string) $filters['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="cw-filter-group" style="margin-bottom:16px">
        <label class="cw-filter-label" for="f-emp">Employment Type</label>
        <select id="f-emp" name="employment_type" onchange="document.getElementById('filterForm').submit()">
          <option value="">All types</option>
          <?php foreach (JobPosting::EMPLOYMENT_TYPES as $code => $label): ?>
            <option value="<?= View::e($code) ?>" <?= $filters['employment_type'] === $code ? 'selected' : '' ?>><?= View::e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="cw-filter-group" style="margin-bottom:16px">
        <label class="cw-filter-label" for="f-mode">Work Mode</label>
        <select id="f-mode" name="work_mode" onchange="document.getElementById('filterForm').submit()">
          <option value="">Any mode</option>
          <?php foreach (JobPosting::WORK_MODES as $code => $label): ?>
            <option value="<?= View::e($code) ?>" <?= $filters['work_mode'] === $code ? 'selected' : '' ?>><?= View::e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="cw-filter-group">
        <label class="cw-filter-label" for="f-centre">Location</label>
        <select id="f-centre" name="centre_id" onchange="document.getElementById('filterForm').submit()">
          <option value="">Any location</option>
          <?php foreach ($centres as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string) $filters['centre_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if ($filters['department_id'] || $filters['category_id'] || $filters['employment_type'] || $filters['work_mode'] || $filters['centre_id'] || $filters['keyword']): ?>
        <p style="margin-top:16px"><a href="app.php?r=jobs" style="font-size:0.82rem;color:var(--cw-ink-faint)">Clear all filters</a></p>
      <?php endif; ?>
    </form>
  </aside>

  <div>
    <p class="cw-results-count"><?= count($jobs) ?> open position<?= count($jobs) === 1 ? '' : 's' ?></p>

    <?php if (!$jobs): ?>
      <div class="cw-empty">
        <p>No open positions match those filters right now.</p>
        <p><a href="app.php?r=jobs">Clear filters and see everything open</a></p>
      </div>
    <?php else: ?>
      <div class="cw-jobs-grid">
        <?php foreach ($jobs as $job): ?>
          <article class="cw-job-card">
            <div class="cw-job-card-dept"><?= View::e($job['department_name'] ?? 'UltrAdemy') ?></div>
            <h3><a href="app.php?r=jobs.show&slug=<?= urlencode($job['slug']) ?>"><?= View::e($job['title']) ?></a></h3>
            <div class="cw-tag-row">
              <span class="cw-tag cw-tag-accent"><?= View::e(JobPosting::EMPLOYMENT_TYPES[$job['employment_type']] ?? $job['employment_type']) ?></span>
              <span class="cw-tag"><?= View::e(JobPosting::WORK_MODES[$job['work_mode']] ?? $job['work_mode']) ?></span>
              <?php if (in_array((int) $job['id'], $savedIds, true)): ?><span class="cw-tag">Saved</span><?php endif; ?>
            </div>
            <div class="cw-job-card-foot">
              <span><?= View::e($job['_location']) ?></span>
              <?php if ($job['application_deadline']): ?><span>Closes <?= date('M j', strtotime($job['application_deadline'])) ?></span><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
